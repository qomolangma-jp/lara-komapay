<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NewsController extends Controller
{
    /**
     * お知らせ一覧を取得
     */
    public function index(Request $request)
    {
        $user = auth('sanctum')->user();

        $query = News::with('author')->orderBy('created_at', 'desc');

        // 販売者は自分の投稿のみ表示、公開画面は公開ニュースのみ表示
        if ($request->is('api/seller/*')) {
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => '認証が必要です',
                ], Response::HTTP_UNAUTHORIZED);
            }
            $query->where('user_id', $user->id);
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

        $news = News::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'is_published' => $validated['is_published'] ?? true,
            'user_id' => $authorId,
        ]);
        $news->load('author');

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
        $news->load('author');

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

    private function formatNewsResponse(News $news): array
    {
        $item = $news->toArray();

        $author = $news->author;
        $authorName = trim((string) optional($author)->name_2nd . ' ' . (string) optional($author)->name_1st);
        if ($authorName === '') {
            $authorName = (string) (optional($author)->shop_name ?? '未設定');
        }

        $item['author'] = [
            'id' => optional($author)->id,
            'name' => $authorName,
            'shop_name' => optional($author)->shop_name,
        ];

        return $item;
    }
}
