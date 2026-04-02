<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
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

        if ($request->hasFile('image') && !Schema::hasColumn('news', 'image_url')) {
            return response()->json([
                'success' => false,
                'message' => '画像カラム(image_url)が未作成です。マイグレーションを実行してください。',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (Schema::hasColumn('news', 'image_url')) {
            $createData['image_url'] = $this->storeNewsImage($request);
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'remove_image' => 'nullable|boolean',
        ]);

        $updateData = [
            'title' => $validated['title'],
            'content' => $validated['content'],
            'is_published' => $validated['is_published'] ?? $news->is_published,
        ];

        if (($request->hasFile('image') || $request->filled('remove_image')) && !Schema::hasColumn('news', 'image_url')) {
            return response()->json([
                'success' => false,
                'message' => '画像カラム(image_url)が未作成です。マイグレーションを実行してください。',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (Schema::hasColumn('news', 'image_url')) {
            $removeImage = filter_var($request->input('remove_image', false), FILTER_VALIDATE_BOOLEAN);
            $newImagePath = $this->storeNewsImage($request);

            if ($newImagePath !== null) {
                $this->deleteLocalNewsImage((string) ($news->image_url ?? ''));
                $updateData['image_url'] = $newImagePath;
            } elseif ($removeImage) {
                $this->deleteLocalNewsImage((string) ($news->image_url ?? ''));
                $updateData['image_url'] = null;
            }
        }

        $news->update($updateData);
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

        if (Schema::hasColumn('news', 'image_url')) {
            $this->deleteLocalNewsImage((string) ($news->image_url ?? ''));
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

        if (array_key_exists('image_url', $item)) {
            $item['image_url'] = $this->normalizeNewsImageUrl((string) ($item['image_url'] ?? ''));
        }

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

    private function storeNewsImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $image = $request->file('image');
        $extension = strtolower((string) $image->getClientOriginalExtension());
        $filename = time() . '_news_' . uniqid() . ($extension !== '' ? ('.' . $extension) : '');

        $storageImagesDir = storage_path('app/public/images');
        if (!is_dir($storageImagesDir)) {
            mkdir($storageImagesDir, 0755, true);
        }

        $image->move($storageImagesDir, $filename);

        $storagePublicDir = storage_path('app/public');
        $publicStorageDir = public_path('storage');

        if (!is_link($publicStorageDir) && !is_dir($publicStorageDir)) {
            @symlink($storagePublicDir, $publicStorageDir);
        }

        $urlPath = '/storage/images/' . $filename;
        if (!is_link($publicStorageDir) && !is_dir($publicStorageDir)) {
            $publicImagesDir = public_path('images');
            if (!is_dir($publicImagesDir)) {
                mkdir($publicImagesDir, 0755, true);
            }

            @copy($storageImagesDir . DIRECTORY_SEPARATOR . $filename, $publicImagesDir . DIRECTORY_SEPARATOR . $filename);
            $urlPath = '/images/' . $filename;
        }

        return $urlPath;
    }

    private function normalizeNewsImageUrl(string $imageUrl): string
    {
        $imageUrl = trim($imageUrl);
        if ($imageUrl === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $imageUrl)) {
            return $imageUrl;
        }

        if (str_starts_with($imageUrl, '/')) {
            return URL::to($imageUrl);
        }

        return URL::to('/' . ltrim($imageUrl, '/'));
    }

    private function deleteLocalNewsImage(string $imageUrl): void
    {
        $imageUrl = trim($imageUrl);
        if ($imageUrl === '') {
            return;
        }

        if (preg_match('/^https?:\/\//i', $imageUrl)) {
            $parsed = parse_url($imageUrl);
            $imageUrl = $parsed['path'] ?? '';
        }

        if ($imageUrl === '') {
            return;
        }

        $filename = basename($imageUrl);
        if ($filename === '' || str_contains($filename, '..')) {
            return;
        }

        $candidates = [
            storage_path('app/public/images/' . $filename),
            public_path('images/' . $filename),
            public_path('storage/images/' . $filename),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
