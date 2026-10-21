<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChargeRequest;
use App\Models\PointTransaction;
use App\Services\PointLedgerService;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\PayPayService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChargeController extends Controller
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

    public function apply(Request $request)
    {
        $user = $this->resolveApiUser($request);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'ログインが必要です',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $validated = $request->validate([
            'amount' => 'required|integer|min:100|max:49999',
            'method' => 'sometimes|required|string|in:cash,bank_transfer',
            'description' => 'nullable|string|max:255',
        ], [
            'amount.required' => '申請ポイント数を入力してください。',
            'amount.integer' => '申請ポイント数は整数で入力してください。',
            'amount.min' => '申請ポイント数は 100 ポイント以上で入力してください。',
            'amount.max' => '申請ポイント数は 50,000 ポイント未満で入力してください。',
        ]);

        try {
            $method = $validated['method'] ?? 'cash';
            if ($method === 'bank_transfer') {
                $instructions = config('services.charge.transfer_instructions', []);
                foreach ([
                    'bank_name',
                    'branch_name',
                    'account_type',
                    'account_number',
                    'account_holder',
                    'deadline',
                ] as $key) {
                    if (empty($instructions[$key])) {
                        return response()->json([
                            'success' => false,
                            'message' => '振込先情報が設定されていないため、銀行振込申請を受け付けられません。',
                        ], Response::HTTP_SERVICE_UNAVAILABLE);
                    }
                }
            }

            $chargeRequest = DB::transaction(function () use ($user, $validated, $method) {
                return ChargeRequest::create([
                    'user_id' => $user->id,
                    'amount' => (int) $validated['amount'],
                    'method' => $method,
                    'status' => 'pending',
                    'payment_fee' => 0,
                    'payment_total' => (int) $validated['amount'],
                    'description' => $validated['description'] ?? null,
                ]);
            });

            AuditLogService::record(
                $request,
                'charge_request.created',
                'charge_request',
                (int) $chargeRequest->id,
                [],
                [
                    'amount' => (int) $chargeRequest->amount,
                    'method' => $chargeRequest->method,
                    'status' => 'pending',
                ],
                ['requester_user_id' => (int) $user->id]
            );

            $data = [
                'id' => (string) $chargeRequest->id,
                'amount' => (int) $chargeRequest->amount,
                'method' => $chargeRequest->method,
                'status' => $chargeRequest->status,
            ];
            if ($method === 'bank_transfer') {
                $data['transfer_instructions'] = array_merge(
                    $instructions,
                    ['reference' => 'CHG-' . $chargeRequest->id]
                );
            }

            return response()->json([
                'success' => true,
                'message' => $method === 'bank_transfer'
                    ? '申請を受け付けました。入金確認後にポイントを付与します。'
                    : '申請を受け付けました。管理者の承認後にポイントを付与します。',
                'data' => $data,
            ], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            Log::error('Charge apply error', ['user_id' => $user->id]);

            return response()->json([
                'success' => false,
                'message' => 'ポイントチャージ申請の受付に失敗しました。',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function charge(Request $request)
    {
        return $this->apply($request);
    }

    public function paypay(Request $request, PayPayService $payPayService)
    {
        $user = $this->resolveApiUser($request);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'ログインが必要です',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $validated = $request->validate([
            'amount' => 'required|integer|min:100|max:49999',
            'return_url' => 'required|string|max:2048',
        ]);

        if (! $this->isAllowedPayPayReturnUrl($validated['return_url'])) {
            return response()->json([
                'success' => false,
                'message' => 'return_url が許可されていません。',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $amount = (int) $validated['amount'];
        $feeRate = (float) config('services.charge.paypay_fee_rate', 0.05);
        if (! is_finite($feeRate) || $feeRate < 0 || $feeRate > 1) {
            Log::error('Invalid PayPay charge fee configuration');

            return response()->json([
                'success' => false,
                'message' => '決済設定に問題があるため、チャージを開始できません。',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $fee = (int) round($amount * $feeRate, 0, PHP_ROUND_HALF_UP);
        $total = $amount + $fee;

        $chargeRequest = ChargeRequest::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'method' => 'paypay',
            'status' => 'pending',
            'payment_fee' => $fee,
            'payment_total' => $total,
            'description' => 'PayPayポイントチャージ',
        ]);
        $merchantPaymentId = 'charge_' . $chargeRequest->id;
        $chargeRequest->update(['paypay_payment_id' => $merchantPaymentId]);
        AuditLogService::record(
            $request,
            'charge_request.paypay_started',
            'charge_request',
            (int) $chargeRequest->id,
            [],
            [
                'amount' => $amount,
                'payment_fee' => $fee,
                'payment_total' => $total,
                'status' => 'pending',
            ],
            ['requester_user_id' => (int) $user->id]
        );
        $redirectUrl = $validated['return_url'] . '?' . http_build_query([
            'merchantPaymentId' => $merchantPaymentId,
        ]);

        try {
            $paymentData = $payPayService->createQrCodePayment(
                $merchantPaymentId,
                $total,
                'ポイントチャージ',
                $redirectUrl
            );
            if (($paymentData['payment_id'] ?? null) !== $merchantPaymentId || empty($paymentData['payment_url'])) {
                throw new \RuntimeException('PayPay did not return a valid payment URL.');
            }

            AuditLogService::record(
                $request,
                'charge_request.paypay_order_created',
                'charge_request',
                (int) $chargeRequest->id,
                [],
                [
                    'amount' => $amount,
                    'payment_fee' => $fee,
                    'payment_total' => $total,
                    'status' => 'pending',
                ],
                ['requester_user_id' => (int) $user->id]
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'charge_request_id' => (string) $chargeRequest->id,
                    'payment_url' => $paymentData['payment_url'],
                ],
            ], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            $chargeRequest->update(['status' => 'failed']);
            AuditLogService::record(
                $request,
                'charge_request.paypay_creation_failed',
                'charge_request',
                (int) $chargeRequest->id,
                ['status' => 'pending'],
                ['status' => 'failed'],
                ['requester_user_id' => (int) $user->id]
            );
            Log::error('PayPay charge creation failed', [
                'charge_request_id' => $chargeRequest->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'PayPay決済の開始に失敗しました。',
            ], Response::HTTP_BAD_GATEWAY);
        }
    }

    public function confirmPayPay(Request $request, PayPayService $payPayService)
    {
        $user = $this->resolveApiUser($request);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'ログインが必要です',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $validated = $request->validate([
            'merchantPaymentId' => 'required|string|max:100',
            'transaction_id' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:30',
        ]);

        $chargeRequest = ChargeRequest::query()
            ->where('paypay_payment_id', $validated['merchantPaymentId'])
            ->where('user_id', $user->id)
            ->first();
        if (! $chargeRequest) {
            return response()->json([
                'success' => false,
                'message' => 'チャージ申請が見つかりません。',
            ], Response::HTTP_NOT_FOUND);
        }
        if ($chargeRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'このチャージ申請は既に処理されています。',
            ], Response::HTTP_CONFLICT);
        }

        try {
            $paymentDetails = $payPayService->getPaymentDetails($validated['merchantPaymentId']);
        } catch (\Throwable $e) {
            Log::error('PayPay charge verification failed', [
                'charge_request_id' => $chargeRequest->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'PayPay決済状況の確認に失敗しました。',
            ], Response::HTTP_BAD_GATEWAY);
        }

        $payment = data_get($paymentDetails, 'data', $paymentDetails);
        $providerStatus = data_get($payment, 'status');
        $providerMerchantPaymentId = data_get($payment, 'merchantPaymentId');
        $providerTransactionId = data_get($payment, 'transactionId')
            ?? data_get($payment, 'transaction_id')
            ?? data_get($payment, 'paymentId');
        $providerAmount = data_get($payment, 'amount.amount')
            ?? data_get($payment, 'requestedAmount.amount');
        $providerCurrency = data_get($payment, 'amount.currency')
            ?? data_get($payment, 'requestedAmount.currency');

        // ブラウザー由来の transaction_id / status は判定に使わず、PayPay照会結果のみを根拠にする
        if ($providerStatus !== 'COMPLETED') {
            return response()->json([
                'success' => false,
                'message' => 'PayPay決済が完了していません。',
                'data' => ['status' => $providerStatus],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (
            $providerMerchantPaymentId !== $validated['merchantPaymentId']
            || ! is_numeric($providerAmount)
            || (int) $providerAmount !== (int) $chargeRequest->payment_total
            || $providerCurrency !== 'JPY'
            || ! is_string($providerTransactionId)
            || $providerTransactionId === ''
        ) {
            return response()->json([
                'success' => false,
                'message' => 'PayPay決済情報が申請内容と一致しません。',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $result = DB::transaction(function () use ($chargeRequest, $user, $providerTransactionId) {
                $lockedRequest = ChargeRequest::query()
                    ->lockForUpdate()
                    ->findOrFail($chargeRequest->id);
                if ($lockedRequest->status !== 'pending') {
                    return null;
                }

                $entry = PointLedgerService::apply($user->id, (int) $lockedRequest->amount, 'charge', [
                    'method' => 'paypay',
                    'transaction_type' => 'charge_paypay',
                    'charge_request_id' => $lockedRequest->id,
                    'description' => 'PayPayチャージ',
                ]);
                $balanceAfter = (int) $entry->balance_after;
                $lockedRequest->update([
                    'status' => 'completed',
                    'paypay_transaction_id' => $providerTransactionId,
                    'paypay_paid_at' => now(),
                    'approved_by_name' => 'Auto',
                    'approved_at' => now(),
                ]);

                return ['amount' => (int) $lockedRequest->amount, 'balance' => $balanceAfter];
            });

            if ($result === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'このチャージ申請は既に処理されています。',
                ], Response::HTTP_CONFLICT);
            }

            AuditLogService::record(
                $request,
                'charge_request.paypay_completed',
                'charge_request',
                (int) $chargeRequest->id,
                ['status' => 'pending'],
                [
                    'status' => 'completed',
                    'amount' => $result['amount'],
                    'paypay_transaction_id' => $providerTransactionId,
                ],
                ['requester_user_id' => (int) $user->id]
            );

            return response()->json([
                'success' => true,
                'message' => 'PayPay決済が確認され、ポイントが付与されました。',
                'data' => ['amount' => $result['amount'], 'balance' => $result['balance']],
            ]);
        } catch (\Throwable $e) {
            Log::error('PayPay charge finalization failed', [
                'charge_request_id' => $chargeRequest->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ポイントチャージの確定に失敗しました。',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function isAllowedPayPayReturnUrl(string $returnUrl): bool
    {
        $parts = parse_url($returnUrl);
        if (
            ! is_array($parts)
            || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])
            || ($parts['path'] ?? '') !== '/charge/paypay/return'
        ) {
            return false;
        }

        $origin = strtolower($parts['scheme']) . '://' . strtolower($parts['host']);
        if (isset($parts['port'])) {
            $origin .= ':' . $parts['port'];
        }

        foreach (config('services.charge.frontend_allowed_origins', []) as $allowedOrigin) {
            $allowed = parse_url((string) $allowedOrigin);
            if (
                ! is_array($allowed)
                || empty($allowed['scheme'])
                || empty($allowed['host'])
                || (isset($allowed['path']) && $allowed['path'] !== '/' && $allowed['path'] !== '')
                || isset($allowed['query'])
                || isset($allowed['fragment'])
                || isset($allowed['user'])
                || isset($allowed['pass'])
            ) {
                continue;
            }

            $normalizedAllowed = strtolower($allowed['scheme']) . '://' . strtolower($allowed['host']);
            if (isset($allowed['port'])) {
                $normalizedAllowed .= ':' . $allowed['port'];
            }
            if ($origin === $normalizedAllowed) {
                return true;
            }
        }

        return false;
    }

    public function history(Request $request)
    {
        $user = $this->resolveApiUser($request);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'ログインが必要です',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $paginator = PointTransaction::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereNotNull('type')
            ->orderByDesc('id')
            ->paginate((int) ($validated['per_page'] ?? 20), ['*'], 'page', (int) ($validated['page'] ?? 1));

        $rows = $paginator->getCollection()->map(function (PointTransaction $tx) {
            $row = [
                'id' => (int) $tx->id,
                'type' => $tx->type,
            ];
            if ($tx->type === 'charge' && $tx->method) {
                $row['method'] = $tx->method;
            }

            return $row + [
                'status' => $tx->status,
                'amount' => (int) $tx->balance_after - (int) $tx->balance_before,
                'balance_after' => (int) $tx->balance_after,
                'description' => $tx->description,
                'created_at' => $tx->created_at?->toIso8601String(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $rows,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function userStatus(Request $request)
    {
        $user = $this->resolveApiUser($request);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'ログインが必要です',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $latestPendingRequest = ChargeRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'current_points' => (int) ($user->points_balance ?? 0),
                'pending_request' => $latestPendingRequest ? [
                    'id' => $latestPendingRequest->id,
                    'amount' => (int) $latestPendingRequest->amount,
                    'status' => $latestPendingRequest->status,
                    'created_at' => $latestPendingRequest->created_at?->toIso8601String(),
                ] : null,
                'has_pending_request' => (bool) $latestPendingRequest,
            ],
        ]);
    }
}
