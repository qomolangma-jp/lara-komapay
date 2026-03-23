<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class ProductController extends Controller
{
    /**
     * 全商品を取得
     */
    public function index(Request $request)
    {
        $query = Product::with('seller');
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

        $products = $query->get()->map(function ($product) use ($useListThumbnail) {
            return $this->normalizeProductResponse($product, $useListThumbnail);
        });

        return response()->json([
            'success' => true,
            'data' => $products,
            'count' => $products->count(),
        ]);
    }

    /**
     * 商品詳細を取得
     */
    public function show(Product $product)
    {
        $product->load('seller');
        $product = $this->normalizeProductResponse($product);
        
        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
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

            $validated = $this->sanitizeForSave($validated);
            $validated = $this->processImageForSave($validated);

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
        if ($path && str_starts_with($path, '/images/')) {
            $data['image_url'] = $path;
            return $data;
        }

        if (!preg_match('/^https?:\/\//i', $imageUrl)) {
            return $data;
        }

        $data['image_url'] = $this->convertExternalImageTo43($imageUrl);
        return $data;
    }

    private function convertExternalImageTo43(string $imageUrl): string
    {
        if (!function_exists('imagecreatefromstring')) {
            throw new \RuntimeException('GDライブラリが有効化されていないため画像加工できません');
        }

        $response = Http::timeout(20)
            ->withHeaders(['User-Agent' => 'KomaPayImageProcessor/1.0'])
            ->get($imageUrl);

        if (!$response->successful()) {
            throw new \RuntimeException('画像URLの取得に失敗しました');
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        if (!str_starts_with($contentType, 'image/')) {
            throw new \RuntimeException('指定URLは画像ではありません');
        }

        $sourceImage = imagecreatefromstring($response->body());
        if (!$sourceImage) {
            throw new \RuntimeException('画像データの読み込みに失敗しました');
        }

        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);

        $targetWidth = 1200;
        $targetHeight = 900;
        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

        imagecopyresampled(
            $targetImage,
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

        $imagesDir = public_path('images');
        if (!is_dir($imagesDir)) {
            mkdir($imagesDir, 0755, true);
        }

        $filename = 'processed_' . now()->format('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.jpg';
        $savePath = $imagesDir . DIRECTORY_SEPARATOR . $filename;
        $saved = imagejpeg($targetImage, $savePath, 90);

        imagedestroy($sourceImage);
        imagedestroy($targetImage);

        if (!$saved) {
            throw new \RuntimeException('加工画像の保存に失敗しました');
        }

        return '/images/' . $filename;
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

        $data['category_name'] = !empty($data['category']) ? $data['category'] : '未入力';
        $data['category_id'] = $data['category_id'] ?? null;

        if (empty($data['label'])) {
            $data['label'] = '未入力';
        }

        if (empty($data['allergens'])) {
            $data['allergens'] = '未入力';
        }

        $seller = $data['seller'] ?? null;
        if (is_array($seller)) {
            $data['seller_name'] = $seller['display_name']
                ?? $seller['shop_name']
                ?? trim(($seller['name_2nd'] ?? '') . ' ' . ($seller['name_1st'] ?? ''))
                ?: '未入力';
        } else {
            $data['seller_name'] = '未入力';
        }
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

        $host = request()->getSchemeAndHttpHost();
        if (str_starts_with($imageUrl, '/')) {
            return $host . $imageUrl;
        }

        return $host . '/' . ltrim($imageUrl, '/');
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
            if (str_starts_with($path, '/images/')) {
                $localPath = $path;
            }
        } elseif (str_starts_with($imageUrl, '/images/')) {
            $localPath = $imageUrl;
        }

        if ($localPath === '') {
            return '';
        }

        $sourceAbsolutePath = public_path(ltrim($localPath, '/'));
        if (!is_file($sourceAbsolutePath) || !function_exists('imagecreatefromstring')) {
            return '';
        }

        $pathInfo = pathinfo($sourceAbsolutePath);
        $thumbFilename = 'thumb_43_' . ($pathInfo['filename'] ?? 'image') . '.jpg';
        $thumbRelativePath = '/images/' . $thumbFilename;
        $thumbAbsolutePath = public_path(ltrim($thumbRelativePath, '/'));

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
