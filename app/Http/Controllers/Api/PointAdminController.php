<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChargeRequest;
use App\Models\PointTransaction;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
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

    public function historyUsers(Request $request)
    {
        $validated = $request->validate([
            'keyword' => 'nullable|string|max:100',
            'limit' => 'nullable|integer|min:5|max:100',
        ]);

        $keyword = trim((string) ($validated['keyword'] ?? ''));
        $limit = (int) ($validated['limit'] ?? 30);

        $query = PointTransaction::query()
            ->select('user_id')
            ->selectRaw('COUNT(*) as tx_count')
            ->selectRaw('SUM(amount) as total_amount')
            ->selectRaw('MAX(created_at) as last_transaction_at')
            ->with(['user:id,username,name_1st,name_2nd,shop_name,status'])
            ->groupBy('user_id')
            ->orderByDesc('last_transaction_at');

        if ($keyword !== '') {
            $query->whereHas('user', function ($userQuery) use ($keyword) {
                $userQuery->where(function ($inner) use ($keyword) {
                    $inner->where('username', 'like', '%' . $keyword . '%')
                        ->orWhere('name_1st', 'like', '%' . $keyword . '%')
                        ->orWhere('name_2nd', 'like', '%' . $keyword . '%')
                        ->orWhere('shop_name', 'like', '%' . $keyword . '%');
                });
            });
        }

        $rows = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $rows->map(function (PointTransaction $row) {
                return [
                    'user_id' => (int) $row->user_id,
                    'tx_count' => (int) ($row->tx_count ?? 0),
                    'total_amount' => (int) ($row->total_amount ?? 0),
                    'last_transaction_at' => $row->last_transaction_at
                        ? Carbon::parse((string) $row->last_transaction_at)->toIso8601String()
                        : null,
                    'user' => $row->user ? [
                        'id' => (int) $row->user->id,
                        'username' => (string) $row->user->username,
                        'display_name' => (string) $row->user->display_name,
                        'status' => (string) $row->user->status,
                    ] : null,
                ];
            }),
        ]);
    }

    public function personalHistory(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'limit' => 'nullable|integer|min:1|max:500',
        ]);

        $query = PointTransaction::query()
            ->where('user_id', (int) $validated['user_id'])
            ->with(['user:id,username,name_1st,name_2nd,shop_name,status']);

        if (! empty($validated['start_date'])) {
            $query->whereDate('created_at', '>=', $validated['start_date']);
        }

        if (! empty($validated['end_date'])) {
            $query->whereDate('created_at', '<=', $validated['end_date']);
        }

        $limit = (int) ($validated['limit'] ?? 100);
        $transactions = $query->orderByDesc('created_at')->limit($limit)->get();

        $user = User::query()->findOrFail((int) $validated['user_id']);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => (int) $user->id,
                    'username' => (string) $user->username,
                    'display_name' => (string) $user->display_name,
                    'status' => (string) $user->status,
                    'current_points' => (int) ($user->points_balance ?? 0),
                ],
                'period' => [
                    'start_date' => $validated['start_date'] ?? null,
                    'end_date' => $validated['end_date'] ?? null,
                ],
                'summary' => [
                    'tx_count' => $transactions->count(),
                    'total_amount' => (int) $transactions->sum('amount'),
                ],
                'transactions' => $transactions->map(function (PointTransaction $transaction) {
                    return [
                        'id' => (int) $transaction->id,
                        'transaction_type' => (string) $transaction->transaction_type,
                        'amount' => (int) $transaction->amount,
                        'balance_before' => (int) $transaction->balance_before,
                        'balance_after' => (int) $transaction->balance_after,
                        'status' => (string) $transaction->status,
                        'description' => $transaction->description,
                        'created_at' => $transaction->created_at?->toIso8601String(),
                    ];
                }),
            ],
        ]);
    }

    public function periodHistory(Request $request)
    {
        $validated = $request->validate([
            'mode' => 'required|string|in:daily,monthly',
            'date' => 'nullable|date_format:Y-m-d',
            'month' => ['nullable', 'regex:/^\d{4}-\d{2}$/'],
            'limit' => 'nullable|integer|min:1|max:500',
        ]);

        $mode = (string) $validated['mode'];
        $limit = (int) ($validated['limit'] ?? 200);

        if ($mode === 'daily') {
            if (empty($validated['date'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'daily モードでは date が必要です。',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $day = Carbon::createFromFormat('Y-m-d', (string) $validated['date']);
            $startAt = $day->copy()->startOfDay();
            $endAt = $day->copy()->endOfDay();
            $label = $day->format('Y-m-d');
        } else {
            if (empty($validated['month'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'monthly モードでは month が必要です。',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $month = Carbon::createFromFormat('Y-m', (string) $validated['month']);
            $startAt = $month->copy()->startOfMonth();
            $endAt = $month->copy()->endOfMonth();
            $label = $month->format('Y-m');
        }

        $summaryByType = PointTransaction::query()
            ->select('transaction_type')
            ->selectRaw('COUNT(*) as tx_count')
            ->selectRaw('SUM(amount) as total_amount')
            ->whereBetween('created_at', [$startAt, $endAt])
            ->groupBy('transaction_type')
            ->orderByDesc('total_amount')
            ->get();

        $summaryByUser = PointTransaction::query()
            ->select('point_transactions.user_id', 'users.username', 'users.name_1st', 'users.name_2nd', 'users.shop_name', 'users.status')
            ->selectRaw('COUNT(point_transactions.id) as tx_count')
            ->selectRaw('SUM(point_transactions.amount) as total_amount')
            ->join('users', 'users.id', '=', 'point_transactions.user_id')
            ->whereBetween('point_transactions.created_at', [$startAt, $endAt])
            ->groupBy('point_transactions.user_id', 'users.username', 'users.name_1st', 'users.name_2nd', 'users.shop_name', 'users.status')
            ->orderByDesc('total_amount')
            ->limit(100)
            ->get();

        $transactions = PointTransaction::query()
            ->with(['user:id,username,name_1st,name_2nd,shop_name,status'])
            ->whereBetween('created_at', [$startAt, $endAt])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $dailyBreakdown = PointTransaction::query()
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as tx_count')
            ->selectRaw('SUM(amount) as total_amount')
            ->whereBetween('created_at', [$startAt, $endAt])
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'mode' => $mode,
                'label' => $label,
                'period' => [
                    'start_at' => $startAt->toIso8601String(),
                    'end_at' => $endAt->toIso8601String(),
                ],
                'summary' => [
                    'tx_count' => (int) $summaryByType->sum('tx_count'),
                    'total_amount' => (int) $summaryByType->sum('total_amount'),
                ],
                'by_type' => $summaryByType->map(function ($row) {
                    return [
                        'transaction_type' => (string) $row->transaction_type,
                        'tx_count' => (int) $row->tx_count,
                        'total_amount' => (int) $row->total_amount,
                    ];
                }),
                'by_user' => $summaryByUser->map(function ($row) {
                    $displayName = trim((string) ($row->name_2nd ?? '') . ' ' . (string) ($row->name_1st ?? ''));
                    if ($displayName === '') {
                        $displayName = (string) ($row->shop_name ?? '');
                    }
                    if ($displayName === '') {
                        $displayName = (string) ($row->username ?? '');
                    }

                    return [
                        'user_id' => (int) $row->user_id,
                        'username' => (string) $row->username,
                        'display_name' => $displayName,
                        'status' => (string) $row->status,
                        'tx_count' => (int) $row->tx_count,
                        'total_amount' => (int) $row->total_amount,
                    ];
                }),
                'daily_breakdown' => $dailyBreakdown->map(function ($row) {
                    return [
                        'date' => (string) $row->date,
                        'tx_count' => (int) $row->tx_count,
                        'total_amount' => (int) $row->total_amount,
                    ];
                }),
                'transactions' => $transactions->map(function (PointTransaction $transaction) {
                    return [
                        'id' => (int) $transaction->id,
                        'user_id' => (int) $transaction->user_id,
                        'user_display_name' => $transaction->user?->display_name,
                        'user_username' => $transaction->user?->username,
                        'transaction_type' => (string) $transaction->transaction_type,
                        'amount' => (int) $transaction->amount,
                        'balance_before' => (int) $transaction->balance_before,
                        'balance_after' => (int) $transaction->balance_after,
                        'status' => (string) $transaction->status,
                        'description' => $transaction->description,
                        'created_at' => $transaction->created_at?->toIso8601String(),
                    ];
                }),
            ],
        ]);
    }
}
