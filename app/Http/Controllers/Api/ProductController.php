<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    /**
     * 全商品を取得
     */
    public function index(Request $request)
    {
        $query = Product::with('seller');

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

        $products = $query->get()->map(function ($product) {
            return $this->normalizeProductResponse($product);
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

            $product->update($validated);
            $product->load('seller');
            $product = $this->normalizeProductResponse($product);

            \Log::info('Product updated successfully', ['product_id' => $product->id]);

            return response()->json([
                'success' => true,
                'message' => '商品を更新しました',
                'data' => $product,
            ]);
        } catch (\Exception $e) {
            \Log::error('Product update error', [
                'product_id' => $product->id,
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

    private function normalizeProductResponse(Product $product): array
    {
        $data = $product->toArray();

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
}
