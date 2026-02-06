<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CartController extends Controller
{
    /**
     * カートの中身を取得
     */
    public function index(Request $request)
    {
        $cartItems = CartItem::where('user_id', $request->user()->id)
            ->with('product')
            ->get();

        $total = $cartItems->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $cartItems,
                'total' => $total,
                'count' => $cartItems->sum('quantity'),
            ],
        ]);
    }

    /**
     * カートに商品を追加
     */
    public function add(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'integer|min:1|max:100',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $quantity = $validated['quantity'] ?? 1;

        // 在庫チェック
        if ($product->stock < $quantity) {
            return response()->json([
                'success' => false,
                'message' => '在庫が不足しています',
            ], Response::HTTP_BAD_REQUEST);
        }

        // カートに既に存在するか確認
        $cartItem = CartItem::where('user_id', $request->user()->id)
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($cartItem) {
            // 既存の場合は数量を加算
            $cartItem->quantity += $quantity;
            $cartItem->save();
        } else {
            // 新規作成
            $cartItem = CartItem::create([
                'user_id' => $request->user()->id,
                'product_id' => $validated['product_id'],
                'quantity' => $quantity,
            ]);
        }

        // リレーションを読み込んで返す
        $cartItem->load('product');

        return response()->json([
            'success' => true,
            'message' => 'カートに追加しました',
            'data' => $cartItem,
        ]);
    }

    /**
     * カートの商品数量を更新
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        $cartItem = CartItem::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        // 在庫チェック
        if ($cartItem->product->stock < $validated['quantity']) {
            return response()->json([
                'success' => false,
                'message' => '在庫が不足しています',
            ], Response::HTTP_BAD_REQUEST);
        }

        $cartItem->update(['quantity' => $validated['quantity']]);
        $cartItem->load('product');

        return response()->json([
            'success' => true,
            'message' => 'カートを更新しました',
            'data' => $cartItem,
        ]);
    }

    /**
     * カートから商品を削除
     */
    public function remove(Request $request, $id)
    {
        $cartItem = CartItem::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'カートから削除しました',
        ]);
    }

    /**
     * カートを空にする
     */
    public function clear(Request $request)
    {
        CartItem::where('user_id', $request->user()->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'カートを空にしました',
        ]);
    }

    /**
     * 全カート情報を取得（管理者用）
     */
    public function getAllCarts()
    {
        $cartItems = CartItem::with(['user', 'product'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'carts' => $cartItems,
        ]);
    }

    /**
     * カートアイテムを削除（管理者用）
     */
    public function adminRemove($id)
    {
        $cartItem = CartItem::findOrFail($id);
        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'カートアイテムを削除しました',
        ]);
    }
}
