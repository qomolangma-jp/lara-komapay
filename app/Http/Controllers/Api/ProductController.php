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
        $query = Product::query();

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

        $products = $query->get();

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
            'description' => 'nullable|string',
            'image_url' => 'nullable|string|max:200',
        ]);

        $product = Product::create($validated);

        return response()->json([
            'success' => true,
            'message' => '商品を作成しました',
            'data' => $product,
        ], Response::HTTP_CREATED);
    }

    /**
     * 商品を更新（管理者のみ）
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'price' => 'sometimes|integer|min:0',
            'stock' => 'sometimes|integer|min:0',
            'category' => 'sometimes|string|max:50',
            'description' => 'sometimes|string',
            'image_url' => 'sometimes|string|max:200',
        ]);

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => '商品を更新しました',
            'data' => $product,
        ]);
    }

    /**
     * 商品を削除（管理者のみ）
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => '商品を削除しました',
        ]);
    }

    /**
     * 在庫数を更新（管理者のみ）
     */
    public function updateStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'stock' => 'required|integer|min:0',
        ]);

        $product->update(['stock' => $validated['stock']]);

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
}
