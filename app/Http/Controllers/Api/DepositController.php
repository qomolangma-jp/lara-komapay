<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\PayPayService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DepositController extends Controller
{
    private function resolveApiUser(Request $request): ?User
    {
        $user = auth('sanctum')->user();
        if ($user) {
            return $user;
        }

        $sessionUserId = session('user_id');
        if ($sessionUserId) {
            return User::find($sessionUserId);
        }

        $webUser = $request->user();
        if ($webUser instanceof User) {
            return $webUser;
        }

        return null;
    }

    public function show(Request $request)
    {
        $user = $this->resolveApiUser($request);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'ログインが必要です',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $transactions = WalletTransaction::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => (int) $user->wallet_balance,
                'transactions' => $transactions,
            ],
        ]);
    }

    public function create(Request $request, PayPayService $payPayService)
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:100',
            'description' => 'nullable|string|max:255',
        ]);

        $user = $this->resolveApiUser($request);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'ログインが必要です',
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            DB::beginTransaction();

            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'transaction_type' => 'deposit',
                'amount' => (int) $validated['amount'],
                'balance_before' => (int) $user->wallet_balance,
                'balance_after' => (int) $user->wallet_balance,
                'status' => 'pending',
                'description' => $validated['description'] ?? '残高チャージ',
            ]);

            $merchantPaymentId = 'deposit_' . $transaction->id;
            $baseUrl = config('services.paypay.redirect_url');
            $redirectUrl = $baseUrl . (strpos($baseUrl, '?') !== false ? '&' : '?') . http_build_query([
                'merchantPaymentId' => $merchantPaymentId,
            ]);

            $paymentData = $payPayService->createQrCodePayment(
                $merchantPaymentId,
                (int) $validated['amount'],
                $validated['description'] ?? '残高チャージ',
                $redirectUrl
            );

            $transaction->update([
                'paypay_payment_id' => $paymentData['payment_id'] ?? $merchantPaymentId,
                'metadata' => $paymentData['raw_response'] ?? [],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '入金用のPayPay決済を作成しました',
                'data' => [
                    'transaction' => $transaction->fresh(),
                    'payment_url' => $paymentData['payment_url'] ?? null,
                ],
            ], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Deposit create error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => '残高チャージの初期化に失敗しました',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function confirm(Request $request, PayPayService $payPayService)
    {
        $merchantPaymentId = $request->input('merchantPaymentId')
            ?? $request->input('merchant_payment_id')
            ?? $request->input('paymentId')
            ?? $request->query('merchantPaymentId');

        if (! $merchantPaymentId) {
            return response()->json([
                'success' => false,
                'message' => 'merchantPaymentId がリクエストに含まれていません',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $transaction = WalletTransaction::where('paypay_payment_id', $merchantPaymentId)
            ->orWhere('id', str_replace('deposit_', '', $merchantPaymentId))
            ->first();

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => '入金情報が見つかりませんでした',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $paymentDetails = $payPayService->getPaymentDetails($merchantPaymentId);
            $status = data_get($paymentDetails, 'data.status');

            if ($status === 'COMPLETED') {
                $completedTransaction = $this->finalizeDepositTransaction($transaction);

                return response()->json([
                    'success' => true,
                    'message' => '残高チャージが完了しました',
                    'data' => [
                        'transaction' => $completedTransaction,
                        'balance' => (int) $completedTransaction->user->wallet_balance,
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => '入金はまだ完了していません',
                'data' => [
                    'status' => $status,
                    'transaction' => $transaction,
                    'balance' => (int) $transaction->user->wallet_balance,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Deposit confirm error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => '残高チャージ状況の確認に失敗しました',
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

        $transaction = WalletTransaction::where('paypay_payment_id', $merchantPaymentId)->first();
        if (! $transaction) {
            return response()->json(['success' => false, 'message' => '入金情報が見つかりませんでした'], Response::HTTP_NOT_FOUND);
        }

        $this->finalizeDepositTransaction($transaction);

        return response()->json(['success' => true]);
    }

    private function finalizeDepositTransaction(WalletTransaction $transaction): WalletTransaction
    {
        return DB::transaction(function () use ($transaction) {
            $transaction = WalletTransaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if ($transaction->status === 'completed') {
                return $transaction->load('user');
            }

            $user = User::query()->lockForUpdate()->findOrFail($transaction->user_id);
            $balanceBefore = (int) $user->wallet_balance;
            $balanceAfter = $balanceBefore + (int) $transaction->amount;

            $user->update(['wallet_balance' => $balanceAfter]);

            $transaction->update([
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            return $transaction->load('user');
        });
    }
}