<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;

class NewsController extends Controller
{
    /**
     * お知らせ一覧を取得
     */
    public function index(Request $request)
    {
        try {
            $user = auth('sanctum')->user();

            $query = News::with(['seller'])->orderBy('created_at', 'desc');

            // 販売者画面は自分の投稿のみ表示、公開画面は公開ニュースのみ表示
            if ($request->is('api/seller/*')) {
                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => '認証が必要です',
                    ], Response::HTTP_UNAUTHORIZED);
                }

                if (Schema::hasColumn('news', 'user_id')) {
                    $query->where('user_id', $user->id);
                }
            } elseif (!$request->is('api/master/*')) {
                $query->where('is_published', true);
            }

            $news = $query->get()->map(function (News $item) {
                return $this->formatNewsResponse($item);
            })->values();

            return response()->json([
                'success' => true,
                'data' => $news,
            ]);
        } catch (\Throwable $e) {
            \Log::error('News index error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * お知らせを投稿（管理者のみ）
     */
    public function store(Request $request)
    {
        $user = auth('sanctum')->user();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_published' => 'boolean',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        $authorId = $validated['user_id'] ?? null;
        if ($user) {
            $authorId = $user->id;
        }

        $createData = [
            'title' => $validated['title'],
            'content' => $validated['content'],
            'is_published' => $validated['is_published'] ?? true,
        ];
        if (Schema::hasColumn('news', 'user_id')) {
            $createData['user_id'] = $authorId;
        }

        $news = News::create($createData);
        $news->load('seller');

        return response()->json([
            'success' => true,
            'message' => 'お知らせを投稿しました',
            'data' => $this->formatNewsResponse($news),
        ], Response::HTTP_CREATED);
    }

    /**
     * お知らせを更新（管理者のみ）
     */
    public function update(Request $request, News $news)
    {
        $user = auth('sanctum')->user();
        if ($user && !$user->isAdmin() && (int) $news->user_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => '自分が投稿したニュースのみ編集できます',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_published' => 'boolean',
        ]);

        $news->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'is_published' => $validated['is_published'] ?? $news->is_published,
        ]);
        $news->load('seller');

        return response()->json([
            'success' => true,
            'message' => 'お知らせを更新しました',
            'data' => $this->formatNewsResponse($news),
        ]);
    }

    /**
     * お知らせを削除（管理者のみ）
     */
    public function destroy(News $news)
    {
        $user = auth('sanctum')->user();
        if ($user && !$user->isAdmin() && (int) $news->user_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => '自分が投稿したニュースのみ削除できます',
            ], Response::HTTP_FORBIDDEN);
        }

        $news->delete();

        return response()->json([
            'success' => true,
            'message' => 'お知らせを削除しました',
        ]);
    }

    /**
     * お知らせ詳細を取得
     */
    public function show(News $news)
    {
        try {
            // 公開ニュースはすべて表示可能、未公開は管理者と作成者のみ
            $user = auth('sanctum')->user();
            
            if (!$news->is_published && $user === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'ニュースが見つかりません',
                ], Response::HTTP_NOT_FOUND);
            }
            
            if (!$news->is_published && $user && !$user->isAdmin() && (int) $news->user_id !== (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'アクセス権限がありません',
                ], Response::HTTP_FORBIDDEN);
            }

            $news->load(['seller']);

            return response()->json([
                'success' => true,
                'data' => $this->formatNewsResponse($news),
            ]);
        } catch (\Throwable $e) {
            \Log::error('News show error', [
                'news_id' => $news->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function formatNewsResponse(News $news): array
    {
        $item = $news->toArray();

        $seller = $news->seller;
        $sellerName = $seller->name ?? null;
        if (!$sellerName) {
            $sellerName = trim(((string) ($seller->name_2nd ?? '')) . ' ' . ((string) ($seller->name_1st ?? '')));
        }
        if ($sellerName === '') {
            $sellerName = $seller->shop_name ?? '管理者';
        }

        $item['author'] = [
            'id' => $seller->id ?? null,
            'name' => $sellerName ?? '管理者',
            'shop_name' => $seller->shop_name ?? null,
        ];

        return $item;
    }
}
