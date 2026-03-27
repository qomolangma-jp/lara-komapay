<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;

class ProductController extends Controller
{
    /**
     * 全商品を取得
     */
    public function index(Request $request)
    {
        try {
            $relations = ['vendor'];
            if (method_exists(Product::class, 'seller')) {
                $relations[] = 'seller';
            }

            if (method_exists(Product::class, 'category')) {
                $relations[] = 'category';
            }

            $query = Product::with($relations);
            $useListThumbnail = !$request->is('api/master/*');

            // カテゴリでフィルタリング
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            // 在庫がある商品のみ
            if ($request->get('available') === 'true') {
                $query->where('stock', '>', 0);
            }

            // キーワード検索
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
                });
            }
            $products = $query->get()
                ->filter(function ($item) {
                    return $item instanceof Product;
                })
                ->map(function (Product $item) use ($useListThumbnail) {
                    try {
                        $normalized = $this->normalizeProductResponse($item, $useListThumbnail);

                        // category/vendor が null でも落ちないように明示的にフォールバック
                        $normalized['category_name'] = data_get($item, 'category.name')
                            ?? ($normalized['category'] ?? '未設定');
                        if ($normalized['category_name'] === '') {
                            $normalized['category_name'] = '未設定';
                        }

                        $normalized['vendor_name'] = data_get($item, 'vendor.shop_name')
                            ?? data_get($item, 'vendor.display_name')
                            ?? $normalized['seller_name']
                            ?? '未設定';

                        return $normalized;
                    } catch (\Throwable $itemError) {
                        \Log::warning('Product normalize skipped', [
                            'product_id' => $item->id ?? null,
                            'error' => $itemError->getMessage(),
                        ]);
                        // 画像整形エラーがあっても商品本体は返す
                        return $this->buildFallbackProductResponse($item);
                    }
                })
                ->filter(function ($item) {
                    return is_array($item);
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => $products,
                'count' => $products->count(),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Product index error', [
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

    /**
     * 商品詳細を取得
     */
    public function show(int $id)
    {
        try {
            $relations = ['vendor'];
            if (method_exists(Product::class, 'seller')) {
                $relations[] = 'seller';
            }
            if (method_exists(Product::class, 'category')) {
                $relations[] = 'category';
            }

            $product = Product::with($relations)->find($id);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => '商品が見つかりません',
                ], Response::HTTP_NOT_FOUND);
            }

            $normalized = $this->normalizeProductResponse($product);

            return response()->json([
                'success' => true,
                'data' => $normalized,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Product show error', [
                'product_id' => $id,
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

    /**
     * 商品を作成（管理者のみ）
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'price' => 'required|integer|min:0',
            'stock' => 'required|integer|min:0',
            'category' => 'nullable|string|max:50',
            'seller_id' => 'nullable|exists:users,id',
            'label' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'image_url' => 'nullable|string|max:500',
            'allergens' => 'nullable|string',
        ]);

        $validated = $this->sanitizeForSave($validated);
        $validated = $this->processImageForSave($validated);

        $product = Product::create($validated);
        $product->load('seller');
        $product = $this->normalizeProductResponse($product);

        return response()->json([
            'success' => true,
            'message' => '商品を作成しました',
            'data' => $product,
        ], Response::HTTP_CREATED);
    }

    /**
     * 商品を更新（管理者または販売者）
     */
    public function update(Request $request, Product $product)
    {
        try {
            \Log::info('Product update request', ['product_id' => $product->id, 'data' => $request->all()]);
            
            $user = auth('sanctum')->user();
            
            // 認証ユーザーがいる場合のみチェック（/api/master/* は認証不要）
            if ($user && !$user->isAdmin() && $product->seller_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => '自分の商品のみ編集できます',
                ], Response::HTTP_FORBIDDEN);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:100',
                'price' => 'sometimes|integer|min:0',
                'stock' => 'sometimes|integer|min:0',
                'category' => 'sometimes|string|max:50',
                'seller_id' => 'nullable|exists:users,id',
                'label' => 'nullable|string|max:50',
                'description' => 'sometimes|string',
                'image_url' => 'sometimes|string|max:500',
                'allergens' => 'nullable|string',
            ]);

            \Log::info('Validated data', $validated);

            $oldImageUrl = (string) ($product->image_url ?? '');

            $validated = $this->sanitizeForSave($validated);
            $validated = $this->processImageForSave($validated);

            if (array_key_exists('image_url', $validated)) {
                $newImageUrl = (string) ($validated['image_url'] ?? '');
                if ($oldImageUrl !== '' && $oldImageUrl !== $newImageUrl) {
                    $this->deleteImageFileIfLocal($oldImageUrl);
                }
            }

            $product->update($validated);
            $product->load('seller');
            $productId = $product->id;
            $productData = $this->normalizeProductResponse($product);

            \Log::info('Product updated successfully', ['product_id' => $productId]);

            return response()->json([
                'success' => true,
                'message' => '商品を更新しました',
                'data' => $productData,
            ]);
        } catch (\Exception $e) {
            \Log::error('Product update error', [
                'product_id' => isset($productId) ? $productId : null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '商品の更新に失敗しました: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 商品を削除（管理者または販売者）
     */
    public function destroy(Product $product)
    {
        $user = auth('sanctum')->user();
        
        // 認証ユーザーがいる場合のみチェック（/api/master/* は認証不要）
        if ($user && !$user->isAdmin() && $product->seller_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => '自分の商品のみ削除できます',
            ], Response::HTTP_FORBIDDEN);
        }

        $oldImageUrl = (string) ($product->image_url ?? '');
        if ($oldImageUrl !== '') {
            $this->deleteImageFileIfLocal($oldImageUrl);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => '商品を削除しました',
        ]);
    }

    /**
     * 在庫数を更新（管理者または販売者）
     */
    public function updateStock(Request $request, Product $product)
    {
        $user = auth('sanctum')->user();
        
        // 認証ユーザーがいる場合のみチェック（/api/master/* は認証不要）
        if ($user && !$user->isAdmin() && $product->seller_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => '自分の商品のみ更新できます',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'stock' => 'required|integer|min:0',
        ]);

        $product->update(['stock' => $validated['stock']]);
        $product->load('seller');

        return response()->json([
            'success' => true,
            'message' => '在庫を更新しました',
            'data' => $product,
        ]);
    }

    /**
     * カテゴリ一覧を取得
     */
    public function categories()
    {
        $categories = Product::distinct()->pluck('category')->filter();

        return response()->json([
            'success' => true,
            'data' => $categories->values(),
        ]);
    }

    private function sanitizeForSave(array $data): array
    {
        // 文字列フィールド: null → 空文字
        foreach (['category', 'label', 'description', 'image_url', 'allergens'] as $field) {
            if (array_key_exists($field, $data) && is_null($data[$field])) {
                $data[$field] = '';
            }
        }
        // 数値フィールド: null → 0
        foreach (['price', 'stock'] as $field) {
            if (array_key_exists($field, $data) && is_null($data[$field])) {
                $data[$field] = 0;
            }
        }
        return $data;
    }

    private function buildFallbackProductResponse(Product $product): array
    {
        $data = $product->toArray();

        $data['image_url'] = '';
        $data['thumbnail_url'] = '';
        $data['image_original_url'] = '';

        $category = $data['category'] ?? '';
        $data['category_name'] = $category !== '' ? $category : '未設定';
        $data['category_id'] = $data['category_id'] ?? null;

        $seller = $product->vendor ?? $product->seller;
        $sellerName = optional($seller)->display_name
            ?? optional($seller)->shop_name
            ?? trim((optional($seller)->name_2nd ?? '') . ' ' . (optional($seller)->name_1st ?? ''));
        $data['seller_name'] = $sellerName !== '' ? $sellerName : '未設定';
        $data['vendor_id'] = $data['seller_id'] ?? null;
        $data['vendor_name'] = $data['seller_name'];

        if (empty($data['label'])) {
            $data['label'] = '未入力';
        }
        if (empty($data['allergens'])) {
            $data['allergens'] = '未入力';
        }

        unset($data['seller']);

        return $data;
    }

    private function processImageForSave(array $data): array
    {
        if (!array_key_exists('image_url', $data)) {
            return $data;
        }

        $imageUrl = trim((string) $data['image_url']);
        if ($imageUrl === '') {
            $data['image_url'] = '';
            return $data;
        }

        $parsedUrl = parse_url($imageUrl);
        $path = $parsedUrl['path'] ?? '';
        if ($path && (str_starts_with($path, '/images/') || str_starts_with($path, '/storage/images/'))) {
            $data['image_url'] = $path;
            return $data;
        }

        if (!str_contains($imageUrl, '/') && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $imageUrl)) {
            $data['image_url'] = '/storage/images/' . ltrim($imageUrl, '/');
            return $data;
        }

        // 外部URL入力は廃止。画像はアップロード済みのパスのみ許可する
        if (preg_match('/^https?:\/\//i', $imageUrl)) {
            throw new \RuntimeException('画像URL入力は使用できません。画像ファイルをアップロードしてください。');
        }

        throw new \RuntimeException('画像の保存形式が不正です。画像ファイルをアップロードしてください。');
        return $data;
    }

    private function deleteImageFileIfLocal(string $imageUrl): void
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
            storage_path('app/public/images/thumb_43_' . pathinfo($filename, PATHINFO_FILENAME) . '.jpg'),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function normalizeProductResponse(Product $product, bool $useListThumbnail = false): array
    {
        $data = $product->toArray();

        $rawImageUrl = (string) ($data['image_url'] ?? '');
        $data['image_url'] = $this->normalizeImageUrlForResponse($rawImageUrl);
        if ($useListThumbnail) {
            $thumbnailUrl = $this->buildThumbnailUrlForResponse($rawImageUrl);
            $data['thumbnail_url'] = $thumbnailUrl !== '' ? $thumbnailUrl : $data['image_url'];
            $data['image_original_url'] = $data['image_url'];
            $data['image_url'] = $data['thumbnail_url'];
        }

        $categoryRelation = null;
        if (method_exists($product, 'category') && $product->relationLoaded('category')) {
            $categoryRelation = $product->getRelation('category');
        }
        $categoryName = optional($categoryRelation)->name ?? ($data['category'] ?? '未設定');
        if ($categoryName === null || $categoryName === '') {
            $categoryName = '未設定';
        }
        $data['category_name'] = $categoryName;
        $data['category_id'] = $data['category_id'] ?? optional($categoryRelation)->id ?? null;

        if (empty($data['label'])) {
            $data['label'] = '未入力';
        }

        if (empty($data['allergens'])) {
            $data['allergens'] = '未入力';
        }

        $seller = $product->vendor ?? $product->seller;
        $sellerName = optional($seller)->display_name
            ?? optional($seller)->shop_name
            ?? trim((optional($seller)->name_2nd ?? '') . ' ' . (optional($seller)->name_1st ?? ''));
        $data['seller_name'] = $sellerName !== '' ? $sellerName : '未設定';
        $data['vendor_id'] = $data['seller_id'] ?? null;
        $data['vendor_name'] = $data['seller_name'];
        // Reactがオブジェクトを直接レンダリングしてクラッシュするのを防ぐため
        // sellerオブジェクト全体は除去し、seller_id（整数）とseller_name（文字列）でアクセスさせる
        unset($data['seller']);

        return $data;
    }

    private function normalizeImageUrlForResponse(string $imageUrl): string
    {
        $imageUrl = trim($imageUrl);
        if ($imageUrl === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $imageUrl)) {
            return $imageUrl;
        }

        if (!str_contains($imageUrl, '/') && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $imageUrl)) {
            $imageUrl = '/storage/images/' . $imageUrl;
        }

        if (str_starts_with($imageUrl, 'images/')) {
            $imageUrl = '/' . $imageUrl;
        }

        if (str_starts_with($imageUrl, 'storage/')) {
            $imageUrl = '/' . $imageUrl;
        }

        if (str_starts_with($imageUrl, '/')) {
            return URL::to($imageUrl);
        }

        return URL::to('/' . ltrim($imageUrl, '/'));
    }

    private function buildThumbnailUrlForResponse(string $imageUrl): string
    {
        $imageUrl = trim($imageUrl);
        if ($imageUrl === '') {
            return '';
        }

        $localPath = '';

        if (preg_match('/^https?:\/\//i', $imageUrl)) {
            $parsed = parse_url($imageUrl);
            $path = $parsed['path'] ?? '';
            if (str_starts_with($path, '/images/') || str_starts_with($path, '/storage/images/')) {
                $localPath = $path;
            }
        } elseif (str_starts_with($imageUrl, '/images/') || str_starts_with($imageUrl, '/storage/images/')) {
            $localPath = $imageUrl;
        } elseif (!str_contains($imageUrl, '/') && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $imageUrl)) {
            $localPath = '/storage/images/' . $imageUrl;
        }

        if ($localPath === '') {
            return '';
        }

        $sourceAbsolutePath = str_starts_with($localPath, '/storage/images/')
            ? storage_path('app/public/images/' . basename($localPath))
            : public_path(ltrim($localPath, '/'));
        if (!is_file($sourceAbsolutePath) || !function_exists('imagecreatefromstring')) {
            return '';
        }

        $pathInfo = pathinfo($sourceAbsolutePath);
        $thumbFilename = 'thumb_43_' . ($pathInfo['filename'] ?? 'image') . '.jpg';
        $thumbRelativePath = '/storage/images/' . $thumbFilename;
        $thumbAbsolutePath = storage_path('app/public/images/' . $thumbFilename);

        if (!is_file($thumbAbsolutePath)) {
            $imageBinary = @file_get_contents($sourceAbsolutePath);
            if ($imageBinary === false) {
                return '';
            }

            $sourceImage = @imagecreatefromstring($imageBinary);
            if (!$sourceImage) {
                return '';
            }

            $sourceWidth = imagesx($sourceImage);
            $sourceHeight = imagesy($sourceImage);

            $targetWidth = 800;
            $targetHeight = 600;
            $thumbImage = imagecreatetruecolor($targetWidth, $targetHeight);
            imagecopyresampled(
                $thumbImage,
                $sourceImage,
                0,
                0,
                0,
                0,
                $targetWidth,
                $targetHeight,
                $sourceWidth,
                $sourceHeight
            );

            imagejpeg($thumbImage, $thumbAbsolutePath, 88);
            imagedestroy($sourceImage);
            imagedestroy($thumbImage);
        }

        return $this->normalizeImageUrlForResponse($thumbRelativePath);
    }
}
