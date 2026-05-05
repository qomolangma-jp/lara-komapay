<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderWindow;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderWindowController extends Controller
{
    public function index(Request $request)
    {
        if (!Schema::hasTable('order_windows')) {
            return response()->json([
                'success' => true,
                'data' => [],
                'warning' => 'order_windows テーブルが未作成です。マイグレーションを実行してください。',
            ]);
        }

        $validated = $request->validate([
            'month' => 'nullable|date_format:Y-m',
        ]);

        $month = $validated['month'] ?? now()->format('Y-m');
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $windows = OrderWindow::query()
            ->whereBetween('target_date', [$startDate, $endDate])
            ->orderBy('target_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $windows,
        ]);
    }

    public function upsertMany(Request $request)
    {
        if (!Schema::hasTable('order_windows')) {
            return response()->json([
                'success' => false,
                'message' => 'order_windows テーブルが未作成です。先にマイグレーションを実行してください。',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $validated = $request->validate([
            'dates' => 'required|array|min:1',
            'dates.*' => 'required|date_format:Y-m-d',
            'is_closed' => 'nullable|boolean',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'note' => 'nullable|string|max:255',
        ]);

        // デバッグログ
        \Log::info('OrderWindow.upsertMany called', [
            'dates' => $validated['dates'],
            'is_closed' => $validated['is_closed'] ?? false,
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
        ]);

        $isClosed = (bool) ($validated['is_closed'] ?? false);
        $startTime = $validated['start_time'] ?? null;
        $endTime = $validated['end_time'] ?? null;

        if (!$isClosed) {
            if (!$startTime || !$endTime) {
                return response()->json([
                    'success' => false,
                    'message' => '営業日にする場合は開始時刻と終了時刻を指定してください。',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($startTime >= $endTime) {
                return response()->json([
                    'success' => false,
                    'message' => '終了時刻は開始時刻より後にしてください。',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        DB::beginTransaction();
        try {
            foreach ($validated['dates'] as $date) {
                \Log::info("OrderWindow.upsertMany saving date: {$date}");
                OrderWindow::updateOrCreate(
                    ['target_date' => $date],
                    [
                        'is_closed' => $isClosed,
                        'start_time' => $isClosed ? null : $startTime,
                        'end_time' => $isClosed ? null : $endTime,
                        'note' => $validated['note'] ?? null,
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($validated['dates']) . '日分の注文可能時間を保存しました。',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => '保存に失敗しました。',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function clearMany(Request $request)
    {
        if (!Schema::hasTable('order_windows')) {
            return response()->json([
                'success' => false,
                'message' => 'order_windows テーブルが未作成です。先にマイグレーションを実行してください。',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $validated = $request->validate([
            'dates' => 'required|array|min:1',
            'dates.*' => 'required|date_format:Y-m-d',
        ]);

        $deleted = OrderWindow::query()
            ->whereIn('target_date', $validated['dates'])
            ->delete();

        return response()->json([
            'success' => true,
            'message' => $deleted . '日分の設定を解除しました。',
        ]);
    }
}
