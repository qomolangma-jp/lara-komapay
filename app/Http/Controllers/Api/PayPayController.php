<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderDetail;
use App\Models\Order;
use App\Models\Product;
use App\Services\PayPayService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayPayController extends Controller
{
    private function resolveApiUser(Request $request)
    {
        $user = auth('sanctum')->user();
        if ($user) {
            return $user;
        }

        $sessionUserId = session('user_id');
        if ($sessionUserId) {
            return \App\Models\User::find($sessionUserId);
        }

        $webUser = $request->user();
        if ($webUser instanceof \App\Models\User) {
            return $webUser;
        }

        return null;
    }

    public function create(Request $request, PayPayService $payPayService)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|string|in:cash,paypay',
            'scheduled_at' => 'nullable|date',
        ]);

        $user = $this->resolveApiUser($request);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'ログインが必要です',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($validated['payment_method'] !== 'paypay') {
            return response()->json([
                'success' => false,
                'message' => 'このエンドポイントはPayPay決済用です',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            DB::beginTransaction();

            $limitError = $this->validateDailyPurchaseLimits($user, $validated['items']);
            if ($limitError) {
                DB::rollBack();
                return $limitError;
            }

            // 予約時間（オプション）
            $scheduledAt = null;
            if (! empty($validated['scheduled_at'])) {
                try {
                    $dt = \Illuminate\Support\Carbon::parse($validated['scheduled_at']);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => '予約時間の形式が正しくありません',
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                $now = \Illuminate\Support\Carbon::now();
                $max = $now->copy()->addWeek();
                if ($dt->lt($now) || $dt->gt($max)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => '予約時間は現在から最大1週間以内で指定してください',
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                $scheduledAt = $dt->toDateTimeString();
            }

            $order = $user->orders()->create([
                'status' => Order::STATUS_PAYMENT_PENDING,
                'total_price' => 0,
                'payment_method' => Order::PAYMENT_METHOD_PAYPAY,
                'payment_status' => Order::PAYMENT_STATUS_PENDING,
                'scheduled_at' => $scheduledAt,
            ]);

            $totalPrice = 0;
            foreach ($validated['items'] as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);
                if (! $product || ! $product->hasStock($item['quantity'])) {
                    DB::rollBack();
                    $name = $product ? $product->name : '不明な商品';
                    return response()->json([
                        'success' => false,
                        'message' => "{$name} の在庫が不足しています",
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                $subtotal = $product->price * $item['quantity'];
                $totalPrice += $subtotal;

                $order->details()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                ]);
            }

            $order->update(['total_price' => $totalPrice]);

            $description = implode(', ', $order->details->map(function ($detail) {
                return "{$detail->product->name} x{$detail->quantity}";
            })->toArray());

            $merchantPaymentId = "order_{$order->id}";
            $baseUrl = config('services.paypay.redirect_url');
            $redirectUrl = $baseUrl . (strpos($baseUrl, '?') !== false ? '&' : '?') . http_build_query([
                'merchantPaymentId' => $merchantPaymentId
            ]);

            $paymentData = $payPayService->createQrCodePayment(
                $order->id,
                $totalPrice,
                $description,
                $redirectUrl
            );

            $order->update([
                'paypay_payment_id' => $paymentData['payment_id'] ?? null,
                'paypay_redirect_url' => $paymentData['payment_url'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '注文を作成しました。PayPayで決済してください。',
                'data' => [
                    'order' => $order->load('details.product'),
                    'payment_url' => $paymentData['payment_url'] ?? null,
                ],
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PayPay create error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'PayPay決済の初期化に失敗しました。',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function webhook(Request $request)
    {
        $payload = $request->all();

        $merchantPaymentId = data_get($payload, 'merchantPaymentId') ?: data_get($payload, 'paymentId');
        $status = data_get($payload, 'status');

        if (! $merchantPaymentId || $status !== 'COMPLETED') {
            return response()->json(['success' => false, 'message' => '無効なWebhookです'], Response::HTTP_BAD_REQUEST);
        }

        $order = Order::where('paypay_payment_id', $merchantPaymentId)->first();
        if (! $order) {
            return response()->json(['success' => false, 'message' => '注文が見つかりませんでした'], Response::HTTP_NOT_FOUND);
        }

        $order->update([
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'status' => Order::STATUS_COOKING,
            'paid_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function confirm(Request $request, PayPayService $payPayService)
    {
        Log::info('PayPay confirm payload:', $request->all());

        // フロントエンドからどのようなキーで送られてきても対応できるようにする
        $merchantPaymentId = $request->input('merchantPaymentId') 
            ?? $request->input('merchant_payment_id') 
            ?? $request->input('paymentId') 
            ?? $request->query('merchantPaymentId');

        if (!$merchantPaymentId) {
            return response()->json([
                'success' => false,
                'message' => 'merchantPaymentId がリクエストに含まれていません',
                'received' => $request->all()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $order = Order::where('paypay_payment_id', $merchantPaymentId)->orWhere('id', str_replace('order_', '', $merchantPaymentId))->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => '注文が見つかりませんでした',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $paymentDetails = $payPayService->getPaymentDetails($merchantPaymentId);
            $status = data_get($paymentDetails, 'data.status');
            
            if ($status === 'COMPLETED') {
                if ($order->payment_status !== Order::PAYMENT_STATUS_PAID) {
                    $order->update([
                        'payment_status' => Order::PAYMENT_STATUS_PAID,
                        'status' => Order::STATUS_COOKING,
                        'paid_at' => now(),
                    ]);
                }
                return response()->json([
                    'success' => true,
                    'message' => '決済が完了しました',
                    'data' => ['order' => $order]
                ]);
            }

            $this->deletePendingOrder($order);
            
            return response()->json([
                'success' => true,
                'message' => '決済が完了していなかったため、注文を削除しました',
                'data' => ['status' => $status, 'deleted' => true]
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('PayPay confirm error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => '決済状況の確認に失敗しました',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function deletePendingOrder(Order $order): void
    {
        if ($order->payment_status !== Order::PAYMENT_STATUS_PENDING) {
            return;
        }

        DB::transaction(function () use ($order) {
            $order->details()->delete();
            $order->delete();
        });

        Log::info('PayPay pending order deleted', [
            'order_id' => $order->id,
            'merchant_payment_id' => $order->paypay_payment_id,
        ]);
    }

    private function validateDailyPurchaseLimits($user, array $items)
    {
        $requestedQuantities = [];
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }
            $requestedQuantities[$productId] = ($requestedQuantities[$productId] ?? 0) + $quantity;
        }

        if (empty($requestedQuantities)) {
            return null;
        }

        $products = Product::whereIn('id', array_keys($requestedQuantities))->get()->keyBy('id');
        $startOfDay = Carbon::today();
        $endOfDay = Carbon::tomorrow();

        foreach ($requestedQuantities as $productId => $requestedQuantity) {
            $product = $products->get($productId);
            if (! $product) {
                continue;
            }

            $limit = (int) ($product->daily_purchase_limit_per_user ?? 0);
            if ($limit <= 0) {
                continue;
            }

            $purchasedQuantity = OrderDetail::query()
                ->join('orders', 'order_details.order_id', '=', 'orders.id')
                ->where('orders.user_id', $user->id)
                ->where('order_details.product_id', $productId)
                ->where('orders.status', '!=', Order::STATUS_STOPPED)
                ->whereBetween('orders.created_at', [$startOfDay, $endOfDay])
                ->sum('order_details.quantity');

            if (((int) $purchasedQuantity + $requestedQuantity) > $limit) {
                return response()->json([
                    'success' => false,
                    'message' => sprintf('%s の1日の購入上限（%d個）を超えています', $product->name, $limit),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        return null;
    }
}
