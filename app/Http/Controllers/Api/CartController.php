<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartLog;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class CartController extends Controller
{
    private function resolveAuthenticatedUser(Request $request)
    {
        $user = $request->user() ?: auth('sanctum')->user();
        if ($user) {
            return $user;
        }

        $sessionUserId = session('user_id');
        if ($sessionUserId) {
            return User::find($sessionUserId);
        }

        return null;
    }

    private function unauthenticatedResponse()
    {
        return response()->json([
            'success' => false,
            'message' => '認証が必要です',
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * カートの中身を取得
     */
    public function index(Request $request)
    {
        $user = $this->resolveAuthenticatedUser($request);
        if (!$user) {
            return $this->unauthenticatedResponse();
        }

        $cartItems = CartItem::where('user_id', $user->id)
            ->with('product')
            ->get();

        $total = $cartItems->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $cartItems->map(function ($item) {
                    return $this->normalizeCartItemForResponse($item);
                })->values(),
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
            $user = $this->resolveAuthenticatedUser($request);
            if (!$user) {
                return $this->unauthenticatedResponse();
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
                'data' => $this->normalizeCartItemForResponse($cartItem),
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
        $user = $this->resolveAuthenticatedUser($request);
        if (!$user) {
            return $this->unauthenticatedResponse();
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        $cartItem = CartItem::where('user_id', $user->id)
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
            'data' => $this->normalizeCartItemForResponse($cartItem),
        ]);
    }

    /**
     * カートから商品を削除
     */
    public function remove(Request $request, $id)
    {
        $user = $this->resolveAuthenticatedUser($request);
        if (!$user) {
            return $this->unauthenticatedResponse();
        }

        $cartItem = CartItem::where('user_id', $user->id)
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
        $user = $this->resolveAuthenticatedUser($request);
        if (!$user) {
            return $this->unauthenticatedResponse();
        }

        CartItem::where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'カートを空にしました',
        ]);
    }

    /**
     * 現在ユーザーのカート情報を取得
     */
    public function getAllCarts(Request $request)
    {
        $user = $this->resolveAuthenticatedUser($request);
        if (!$user) {
            return $this->unauthenticatedResponse();
        }

        $perPage = $request->input('per_page', 50); // デフォルト50件
        $search = $request->input('search'); // 検索キーワード

        $query = CartItem::with([
                'user:id,username,name_2nd,name_1st,student_id,shop_name',
                'product:id,name,price,image_url'
            ])
            ->where('user_id', $user->id)
            ->select('id', 'user_id', 'product_id', 'quantity', 'created_at', 'updated_at');
        
        // 検索条件を追加
        if ($search) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }
        
        $cartItems = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'history_mode' => 'current_user_cart_items',
            'message' => '現在ログイン中のユーザーのカートを表示しています',
            'carts' => collect($cartItems->items())->map(function ($item) {
                $normalizedProduct = $this->normalizeProductForResponse($item->product);

                return [
                    'id' => $item->id,
                    'cart_item_id' => $item->id,
                    'user_id' => $item->user_id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'created_at' => $item->created_at,
                    'logged_at' => $item->created_at,
                    'user' => $item->user,
                    'product' => $normalizedProduct,
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
        $request = request();
        $user = $this->resolveAuthenticatedUser($request);
        if (!$user) {
            return $this->unauthenticatedResponse();
        }

        $cartItem = CartItem::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

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

    private function normalizeCartItemForResponse($item): array
    {
        $data = $item->toArray();

        if (isset($data['product']) && is_array($data['product'])) {
            $data['product'] = $this->normalizeProductForResponse($data['product']);
        }

        return $data;
    }

    private function normalizeProductForResponse($product): ?array
    {
        if ($product === null) {
            return null;
        }

        $data = is_array($product) ? $product : $product->toArray();
        $imageUrl = (string) ($data['image_url'] ?? '');
        $data['image_url'] = $this->toAbsoluteImageUrl($imageUrl);

        return $data;
    }

    private function toAbsoluteImageUrl(string $imageUrl): string
    {
        $imageUrl = trim($imageUrl);
        if ($imageUrl === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $imageUrl)) {
            return $imageUrl;
        }

        $path = str_starts_with($imageUrl, '/')
            ? $imageUrl
            : '/storage/images/' . ltrim($imageUrl, '/');

        return URL::to($path);
    }
}
