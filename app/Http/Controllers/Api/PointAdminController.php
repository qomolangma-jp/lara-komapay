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

class PointAdminController extends Controller
{
    private function normalizeStatus(?string $status): string
    {
        return strtolower(trim((string) ($status ?? '')));
    }

    public function index()
    {
        $requests = ChargeRequest::query()
            ->with('user')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests->map(function ($request) {
                return [
                    'id' => $request->id,
                    'user_id' => $request->user_id,
                    'amount' => (int) $request->amount,
                    'status' => $request->status,
                    'description' => $request->description,
                    'created_at' => $request->created_at?->toIso8601String(),
                    'user' => $request->user ? [
                        'id' => $request->user->id,
                        'username' => $request->user->username,
                        'display_name' => $request->user->display_name,
                        'name' => $request->user->display_name,
                    ] : null,
                ];
            }),
        ]);
    }

    public function approve(Request $request, ChargeRequest $chargeRequest)
    {
        if ($this->normalizeStatus($chargeRequest->status) !== 'pending') {
            return response()->json([
                'success' => true,
                'message' => 'この申請は既に処理済みです。',
                'data' => [
                    'already_processed' => true,
                    'status' => $chargeRequest->status,
                ],
            ], Response::HTTP_OK);
        }

        try {
            $result = DB::transaction(function () use ($chargeRequest) {
                $lockedRequest = ChargeRequest::query()->lockForUpdate()->findOrFail($chargeRequest->id);
                if ($lockedRequest->status !== 'pending') {
                    return null;
                }

                $user = User::query()->lockForUpdate()->findOrFail($lockedRequest->user_id);
                $balanceBefore = (int) ($user->points_balance ?? 0);
                $balanceAfter = $balanceBefore + (int) $lockedRequest->amount;

                $user->update(['points_balance' => $balanceAfter]);

                PointTransaction::create([
                    'user_id' => $user->id,
                    'transaction_type' => 'charge_approval',
                    'amount' => (int) $lockedRequest->amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'status' => 'completed',
                    'description' => '管理者承認によるポイント付与',
                ]);

                $lockedRequest->update(['status' => 'approved']);

                return [
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                ];
            });

            if ($result === null) {
                return response()->json([
                    'success' => true,
                    'message' => 'この申請は既に処理済みです。',
                    'data' => [
                        'already_processed' => true,
                        'status' => $chargeRequest->status,
                    ],
                ], Response::HTTP_OK);
            }

            return response()->json([
                'success' => true,
                'message' => '申請を承認しました。',
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('Point admin approve error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => '承認処理に失敗しました。',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function reject(Request $request, ChargeRequest $chargeRequest)
    {
        if ($this->normalizeStatus($chargeRequest->status) !== 'pending') {
            return response()->json([
                'success' => true,
                'message' => 'この申請は既に処理済みです。',
                'data' => [
                    'already_processed' => true,
                    'status' => $chargeRequest->status,
                ],
            ], Response::HTTP_OK);
        }

        try {
            $chargeRequest->update(['status' => 'rejected']);

            return response()->json([
                'success' => true,
                'message' => '申請を却下しました。',
            ]);
        } catch (\Throwable $e) {
            Log::error('Point admin reject error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => '却下処理に失敗しました。',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
