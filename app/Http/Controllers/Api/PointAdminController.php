<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChargeRequest;
use App\Models\PointTransaction;
use App\Models\User;
use App\Services\AuditLogService;
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
            ->with(['user', 'approver'])
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
                    'approved_by' => $request->approved_by,
                    'approver_name' => $request->approved_by_name,
                    'approved_at' => $request->approved_at?->toIso8601String(),
                    'user' => $request->user ? [
                        'id' => $request->user->id,
                        'username' => $request->user->username,
                        'display_name' => $request->user->display_name,
                        'name' => $request->user->display_name,
                    ] : null,
                    'approver' => $request->approver ? [
                        'id' => $request->approver->id,
                        'username' => $request->approver->username,
                        'display_name' => $request->approver->display_name,
                    ] : null,
                ];
            }),
        ]);
    }

    public function approve(Request $request, ChargeRequest $chargeRequest)
    {
        $approver = auth('sanctum')->user() ?: $request->user();
        if (! $approver) {
            return response()->json([
                'success' => false,
                'message' => 'ログインが必要です',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ((int) $chargeRequest->user_id === (int) $approver->id) {
            return response()->json([
                'success' => false,
                'message' => '申請者本人による承認はできません。',
            ], Response::HTTP_FORBIDDEN);
        }

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
            $result = DB::transaction(function () use ($chargeRequest, $approver) {
                $lockedRequest = ChargeRequest::query()->lockForUpdate()->findOrFail($chargeRequest->id);
                if ($this->normalizeStatus($lockedRequest->status) !== 'pending') {
                    return null;
                }

                if ((int) $lockedRequest->user_id === (int) $approver->id) {
                    return 'self_approval';
                }

                $user = User::query()->lockForUpdate()->findOrFail($lockedRequest->user_id);
                $balanceBefore = (int) ($user->points_balance ?? 0);
                $balanceAfter = $balanceBefore + (int) $lockedRequest->amount;

                $approverName = (string) (
                    $approver->display_name
                    ?: trim((string) ($approver->name_2nd ?? '') . ' ' . (string) ($approver->name_1st ?? ''))
                    ?: (string) ($approver->username ?? '')
                );

                $user->update(['points_balance' => $balanceAfter]);

                PointTransaction::create([
                    'user_id' => $user->id,
                    'transaction_type' => 'charge_approval',
                    'amount' => (int) $lockedRequest->amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'status' => 'completed',
                    'description' => sprintf('管理者承認によるポイント付与（承認者: %s）', $approverName !== '' ? $approverName : '不明'),
                ]);

                $lockedRequest->update([
                    'status' => 'approved',
                    'approved_by' => $approver->id,
                    'approved_by_name' => $approverName !== '' ? $approverName : null,
                    'approved_at' => now(),
                ]);

                return [
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'approved_by' => (int) $approver->id,
                    'approved_by_name' => $approverName,
                ];
            });

            if ($result === 'self_approval') {
                return response()->json([
                    'success' => false,
                    'message' => '申請者本人による承認はできません。',
                ], Response::HTTP_FORBIDDEN);
            }

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

            AuditLogService::record(
                $request,
                'charge_request.approved',
                'charge_request',
                (int) $chargeRequest->id,
                [
                    'status' => 'pending',
                    'amount' => (int) $chargeRequest->amount,
                ],
                [
                    'status' => 'approved',
                    'approved_by' => (int) $result['approved_by'],
                    'approved_by_name' => (string) $result['approved_by_name'],
                ],
                [
                    'requester_user_id' => (int) $chargeRequest->user_id,
                    'approved_amount' => (int) $chargeRequest->amount,
                ]
            );

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
        $approver = auth('sanctum')->user() ?: $request->user();
        if (! $approver) {
            return response()->json([
                'success' => false,
                'message' => 'ログインが必要です',
            ], Response::HTTP_UNAUTHORIZED);
        }

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

            AuditLogService::record(
                $request,
                'charge_request.rejected',
                'charge_request',
                (int) $chargeRequest->id,
                [
                    'status' => 'pending',
                    'amount' => (int) $chargeRequest->amount,
                ],
                [
                    'status' => 'rejected',
                ],
                [
                    'requester_user_id' => (int) $chargeRequest->user_id,
                    'rejected_by_user_id' => (int) $approver->id,
                ]
            );

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
