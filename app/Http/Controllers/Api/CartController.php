<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartLog;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => '認証ユーザーが取得できません',
                ], Response::HTTP_UNAUTHORIZED);
            }

            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'integer|min:1|max:100',
            ]);

            $product = Product::findOrFail($validated['product_id']);
            $quantity = (int) ($validated['quantity'] ?? 1);

            // 在庫チェック
            if ($product->stock < $quantity) {
                return response()->json([
                    'success' => false,
                    'message' => '在庫が不足しています',
                ], Response::HTTP_BAD_REQUEST);
            }

            // 同一ユーザー・同一商品でも毎回新規レコードとして保存
            $cartItem = CartItem::create([
                'user_id' => $user->id,
                'product_id' => $validated['product_id'],
                'quantity' => $quantity,
            ]);

            // 管理画面の履歴表示用に、加算前後に関わらず「追加イベント」を記録
            $this->writeCartLog($cartItem->id, $user->id, (int) $validated['product_id'], $quantity);

            // リレーションを読み込んで返す
            $cartItem->load('product');

            return response()->json([
                'success' => true,
                'message' => 'カートに追加しました',
                'data' => $cartItem,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Cart add error', [
                'message' => $e->getMessage(),
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

        $previousQuantity = (int) $cartItem->quantity;
        $newQuantity = (int) $validated['quantity'];

        $cartItem->update(['quantity' => $newQuantity]);

        // add 以外で数量が増えた場合も履歴として記録
        $increasedQuantity = $newQuantity - $previousQuantity;
        if ($increasedQuantity > 0) {
            $this->writeCartLog($cartItem->id, (int) $cartItem->user_id, (int) $cartItem->product_id, $increasedQuantity);
        }

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
    public function getAllCarts(Request $request)
    {
        $perPage = $request->input('per_page', 50); // デフォルト50件
        $search = $request->input('search'); // 検索キーワード

        // 本番でマイグレーション未適用時のフォールバック
        if (!Schema::hasTable('cart_logs')) {
            $fallbackQuery = CartItem::with([
                    'user:id,username,name_2nd,name_1st,student_id',
                    'product:id,name,price,image_url'
                ])
                ->select('id', 'user_id', 'product_id', 'quantity', 'created_at', 'updated_at');

            if ($search) {
                $fallbackQuery->whereHas('user', function ($q) use ($search) {
                    $q->where('username', 'like', "%{$search}%")
                      ->orWhere('name_2nd', 'like', "%{$search}%")
                      ->orWhere('name_1st', 'like', "%{$search}%")
                      ->orWhere('student_id', 'like', "%{$search}%")
                      ->orWhereRaw("CONCAT(name_2nd, name_1st) like ?", ["%{$search}%"]);
                });
            }

            $fallbackItems = $fallbackQuery->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'history_mode' => 'fallback_cart_items',
                'message' => 'cart_logs テーブル未作成のため、履歴表示ではなく現在カート表示です',
                'carts' => collect($fallbackItems->items())->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'cart_item_id' => $item->id,
                        'user_id' => $item->user_id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'created_at' => $item->created_at,
                        'logged_at' => $item->created_at,
                        'user' => $item->user,
                        'product' => $item->product,
                    ];
                })->values(),
                'pagination' => [
                    'current_page' => $fallbackItems->currentPage(),
                    'last_page' => $fallbackItems->lastPage(),
                    'per_page' => $fallbackItems->perPage(),
                    'total' => $fallbackItems->total(),
                ],
            ]);
        }
        
        $query = CartLog::with([
                'user:id,username,name_2nd,name_1st,student_id',
                'product:id,name,price,image_url'
            ])
            ->select('id', 'cart_item_id', 'user_id', 'product_id', 'quantity', 'logged_at', 'created_at', 'updated_at');
        
        // 検索条件を追加
        if ($search) {
            $query->whereHas('user', function($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('name_2nd', 'like', "%{$search}%")
                  ->orWhere('name_1st', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(name_2nd, name_1st) like ?", ["%{$search}%"]);
            });
        }
        
        $cartItems = $query->orderBy('logged_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'history_mode' => 'cart_logs',
            'carts' => collect($cartItems->items())->map(function ($item) {
                return [
                    'id' => $item->id,
                    'cart_item_id' => $item->cart_item_id,
                    'user_id' => $item->user_id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'created_at' => $item->logged_at,
                    'logged_at' => $item->logged_at,
                    'user' => $item->user,
                    'product' => $item->product,
                ];
            })->values(),
            'pagination' => [
                'current_page' => $cartItems->currentPage(),
                'last_page' => $cartItems->lastPage(),
                'per_page' => $cartItems->perPage(),
                'total' => $cartItems->total(),
            ],
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

    private function writeCartLog(int $cartItemId, int $userId, int $productId, int $quantity): void
    {
        if ($quantity <= 0 || !Schema::hasTable('cart_logs')) {
            return;
        }

        try {
            DB::table('cart_logs')->insert([
                'cart_item_id' => $cartItemId,
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'logged_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Cart log write skipped', [
                'message' => $e->getMessage(),
                'cart_item_id' => $cartItemId,
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);
        }
    }
}
