<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * 全注文を取得（管理者のみ）
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'details.product']);

        // ステータスでフィルタリング
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // 日付でフィルタリング
        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }

        // ソート
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $orders = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * 自分の注文一覧を取得
     */
    public function myOrders(Request $request)
    {
        $orders = auth('sanctum')->user()->orders()
            ->with('details.product')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * 注文詳細を取得
     */
    public function show(Order $order)
    {
        $this->authorize('view', $order);

        $order->load(['user', 'details.product']);

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * 新しい注文を作成
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $user = auth('sanctum')->user();
            $totalPrice = 0;

            // 各商品の在庫確認
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                if (!$product || !$product->hasStock($item['quantity'])) {
                    return response()->json([
                        'success' => false,
                        'message' => "{$product->name} の在庫が不足しています",
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            // 注文作成
            $order = $user->orders()->create([
                'status' => Order::STATUS_COOKING,
                'total_price' => 0, // 後で更新
            ]);

            // 注文詳細を作成し、在庫を減らす
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                $subtotal = $product->price * $item['quantity'];
                $totalPrice += $subtotal;

                $order->details()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                ]);

                $product->decrementStock($item['quantity']);
            }

            // 合計金額を更新
            $order->update(['total_price' => $totalPrice]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '注文を作成しました',
                'data' => $order->load('details.product'),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => '注文処理でエラーが発生しました',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 注文ステータスを更新（管理者のみ）
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:調理中,完了',
        ]);

        $order->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'ステータスを更新しました',
            'data' => $order,
        ]);
    }

    /**
     * 今日の統計情報を取得（管理者のみ）
     */
    public function todayStats()
    {
        $stats = [
            'total_orders' => Order::whereDate('created_at', today())->count(),
            'pending_orders' => Order::whereDate('created_at', today())
                ->where('status', Order::STATUS_COOKING)->count(),
            'completed_orders' => Order::whereDate('created_at', today())
                ->where('status', Order::STATUS_COMPLETED)->count(),
            'today_revenue' => Order::whereDate('created_at', today())
                ->sum('total_price'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * 受け取り可能（完了済み）商品一覧を取得
     */
    public function pickupList()
    {
        // 完了状態の注文を取得
        $orders = Order::where('status', Order::STATUS_COMPLETED)
            ->with(['user'])
            ->orderBy('updated_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * 受け取り完了処理（受け取りリストから削除）
     */
    public function completePickup(Order $order)
    {
        if ($order->status !== Order::STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'message' => 'この注文は受け取り可能状態ではありません',
            ], Response::HTTP_BAD_REQUEST);
        }

        $order->update(['status' => Order::STATUS_PICKED_UP]);

        return response()->json([
            'success' => true,
            'message' => '受け取り処理を完了しました',
        ]);
    }

    /**
     * 売上集計を取得（管理者のみ）
     */
    public function sales(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'type'       => 'nullable|in:daily,monthly',
        ]);

        $startDate = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate   = $validated['end_date'] ?? now()->toDateString();
        $type      = $validated['type'] ?? 'daily';
        
        $query = Order::query()
            ->whereBetween('created_at', [
                $startDate . ' 00:00:00', 
                $endDate . ' 23:59:59'
            ]);

        if ($type === 'monthly') {
            $select = DB::raw("DATE_FORMAT(created_at, '%Y-%m') as date");
            $groupBy = DB::raw("DATE_FORMAT(created_at, '%Y-%m')");
        } else {
            $select = DB::raw("DATE(created_at) as date");
            $groupBy = DB::raw("DATE(created_at)");
        }

        $sales = $query
            ->select(
                $select,
                DB::raw('sum(total_price) as total_sales'),
                DB::raw('count(*) as count')
            )
            ->groupBy($groupBy)
            ->orderBy('date', 'desc')
            ->get();

        $totalSales = $sales->sum('total_sales');
        $totalOrders = $sales->sum('count');

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_sales' => (int)$totalSales,
                    'total_orders' => (int)$totalOrders,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'type' => $type
                ],
                'details' => $sales
            ],
        ]);
    }
}
