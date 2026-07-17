<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderWindow;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            'start_day_offset' => 'nullable|integer|min:-1|max:1',
            'start_time' => 'nullable|date_format:H:i',
            'end_day_offset' => 'nullable|integer|min:-1|max:1',
            'end_time' => 'nullable|date_format:H:i',
            'note' => 'nullable|string|max:255',
        ]);

        // デバッグログ
        Log::info('OrderWindow.upsertMany called', [
            'dates' => $validated['dates'],
            'is_closed' => $validated['is_closed'] ?? false,
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
        ]);

        $isClosed = (bool) ($validated['is_closed'] ?? false);
        $startDayOffset = (int) ($validated['start_day_offset'] ?? 0);
        $startTime = $validated['start_time'] ?? null;
        $endDayOffset = (int) ($validated['end_day_offset'] ?? 0);
        $endTime = $validated['end_time'] ?? null;
        $hasDayOffsetColumns = Schema::hasColumn('order_windows', 'start_day_offset')
            && Schema::hasColumn('order_windows', 'end_day_offset');

        if (!$hasDayOffsetColumns && ($startDayOffset !== 0 || $endDayOffset !== 0)) {
            return response()->json([
                'success' => false,
                'message' => '日跨ぎ設定にはDB更新が必要です。マイグレーションを実行してください。',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if (!$isClosed) {
            if (!$startTime || !$endTime) {
                return response()->json([
                    'success' => false,
                    'message' => '営業日にする場合は開始時刻と終了時刻を指定してください。',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $anchor = Carbon::create(2000, 1, 2, 0, 0, 0);
            $startAt = $anchor->copy()
                ->addDays($startDayOffset)
                ->setTimeFromTimeString($startTime . ':00');
            $endAt = $anchor->copy()
                ->addDays($endDayOffset)
                ->setTimeFromTimeString($endTime . ':00');

            if ($endAt->lte($startAt)) {
                return response()->json([
                    'success' => false,
                    'message' => '終了日時は開始日時より後にしてください。',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        DB::beginTransaction();
        try {
            foreach ($validated['dates'] as $date) {
                Log::info("OrderWindow.upsertMany saving date: {$date}");
                $payload = [
                    'is_closed' => $isClosed,
                    'start_time' => $isClosed ? null : $startTime,
                    'end_time' => $isClosed ? null : $endTime,
                    'note' => $validated['note'] ?? null,
                ];

                if ($hasDayOffsetColumns) {
                    $payload['start_day_offset'] = $isClosed ? 0 : $startDayOffset;
                    $payload['end_day_offset'] = $isClosed ? 0 : $endDayOffset;
                }

                OrderWindow::updateOrCreate(
                    ['target_date' => $date],
                    $payload
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
