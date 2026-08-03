<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChargeRequest;
use App\Models\PointTransaction;
use App\Models\User;
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
            'amount' => 'required|integer|min:100|max:500000',
            'description' => 'nullable|string|max:255',
        ], [
            'amount.required' => '申請ポイント数を入力してください。',
            'amount.integer' => '申請ポイント数は整数で入力してください。',
            'amount.min' => '申請ポイント数は 100 ポイント以上で入力してください。',
            'amount.max' => '申請ポイント数は 500000 ポイント以下で入力してください。',
        ]);

        try {
            $chargeRequest = DB::transaction(function () use ($user, $validated) {
                return ChargeRequest::create([
                    'user_id' => $user->id,
                    'amount' => (int) $validated['amount'],
                    'status' => 'pending',
                    'description' => $validated['description'] ?? null,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'ポイントチャージ申請を受け付けました。',
                'data' => [
                    'request' => $chargeRequest,
                ],
            ], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            Log::error('Charge apply error', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ポイントチャージ申請の受付に失敗しました。',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function charge(Request $request)
    {
        $user = $this->resolveApiUser($request);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'ログインが必要です',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $validated = $request->validate([
            'amount' => 'required|integer|min:100|max:500000',
            'description' => 'nullable|string|max:255',
        ], [
            'amount.required' => 'チャージポイント数を入力してください。',
            'amount.integer' => 'チャージポイント数は整数で入力してください。',
            'amount.min' => 'チャージポイント数は 100 ポイント以上で入力してください。',
            'amount.max' => 'チャージポイント数は 500000 ポイント以下で入力してください。',
        ]);

        try {
            $data = DB::transaction(function () use ($user, $validated) {
                $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
                $balanceBefore = (int) ($lockedUser->points_balance ?? 0);
                $balanceAfter = $balanceBefore + (int) $validated['amount'];

                $lockedUser->update(['points_balance' => $balanceAfter]);

                $transaction = PointTransaction::create([
                    'user_id' => $lockedUser->id,
                    'transaction_type' => 'charge',
                    'amount' => (int) $validated['amount'],
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'status' => 'completed',
                    'description' => $validated['description'] ?? 'ポイントチャージ',
                ]);

                ChargeRequest::create([
                    'user_id' => $lockedUser->id,
                    'amount' => (int) $validated['amount'],
                    'status' => 'approved',
                    'description' => $validated['description'] ?? 'ポイントチャージ',
                ]);

                return [
                    'transaction' => $transaction,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'ポイントをチャージしました。',
                'data' => $data,
            ], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            Log::error('Points charge error', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ポイントチャージ処理に失敗しました。',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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

        $transactions = PointTransaction::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'current_points' => (int) ($user->points_balance ?? 0),
                'transactions' => $transactions,
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
