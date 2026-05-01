<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderWindow;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

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
        // 開発環境用：認証チェックを緩和
        // $this->authorize('view', $order);

        $order->load(['user', 'details.product']);

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * 販売者向け注文一覧を取得
     */
    public function sellerOrders(Request $request)
    {
        $user = auth('sanctum')->user();

        $query = Order::query()
            ->with(['user', 'details.product'])
            ->whereHas('details.product', function ($productQuery) use ($user) {
                $productQuery->where('seller_id', $user->id);
            });

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        $orders->each(function ($order) use ($user) {
            $order->setRelation(
                'details',
                $order->details->filter(function ($detail) use ($user) {
                    return (int) optional($detail->product)->seller_id === (int) $user->id;
                })->values()
            );
        });

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * 新しい注文を作成
     */
    public function store(Request $request)
    {
        $todayWindow = null;
        if (Schema::hasTable('order_windows')) {
            $todayWindow = OrderWindow::query()
                ->whereDate('target_date', now()->toDateString())
                ->first();
        }

        if ($todayWindow) {
            if ($todayWindow->is_closed) {
                return response()->json([
                    'success' => false,
                    'message' => '本日は注文受付を停止しています。',
                ], Response::HTTP_FORBIDDEN);
            }

            if (! $todayWindow->allowsAt(now())) {
                $start = $todayWindow->start_time ? substr((string) $todayWindow->start_time, 0, 5) : '--:--';
                $end = $todayWindow->end_time ? substr((string) $todayWindow->end_time, 0, 5) : '--:--';

                return response()->json([
                    'success' => false,
                    'message' => "現在は注文受付時間外です（{$start} - {$end}）。",
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $user = auth('sanctum')->user();
            $totalPrice = 0;

            // 各商品の在庫確認（SELECT ... FOR UPDATE でロック）
            foreach ($validated['items'] as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);
                if (!$product || !$product->hasStock($item['quantity'])) {
                    $name = $product ? $product->name : '不明な商品';
                    return response()->json([
                        'success' => false,
                        'message' => "{$name} の在庫が不足しています",
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
                // ロック済みレコードを取得して在庫を確実に減らす
                $product = Product::find($item['product_id']);
                $subtotal = $product->price * $item['quantity'];
                $totalPrice += $subtotal;

                $order->details()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                ]);

                $ok = $product->decrementStock($item['quantity']);
                if (! $ok) {
                    // もしここで減算が失敗した場合はロールバックしてエラーを返す
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "{$product->name} の在庫が不足しています（更新失敗）",
                    ], Response::HTTP_BAD_REQUEST);
                }
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
            'status' => 'required|string',
        ]);

        $normalizedStatus = $this->normalizeStatus((string) $validated['status']);
        if ($normalizedStatus === null) {
            return response()->json([
                'success' => false,
                'message' => 'ステータスの値が不正です',
                'allowed_statuses' => [
                    Order::STATUS_COOKING,
                    Order::STATUS_COMPLETED,
                    Order::STATUS_PICKED_UP,
                    'cooking',
                    'completed',
                    'picked_up',
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $order->update(['status' => $normalizedStatus]);

        return response()->json([
            'success' => true,
            'message' => 'ステータスを更新しました',
            'data' => $order,
        ]);
    }

    private function normalizeStatus(string $status): ?string
    {
        $normalized = strtolower(trim($status));

        $map = [
            '調理中' => Order::STATUS_COOKING,
            'cooking' => Order::STATUS_COOKING,
            'in_progress' => Order::STATUS_COOKING,

            '完了' => Order::STATUS_COMPLETED,
            '完成' => Order::STATUS_COMPLETED,
            'completed' => Order::STATUS_COMPLETED,
            'done' => Order::STATUS_COMPLETED,

            '受渡済' => Order::STATUS_PICKED_UP,
            '受取済' => Order::STATUS_PICKED_UP,
            'picked_up' => Order::STATUS_PICKED_UP,
            'pickedup' => Order::STATUS_PICKED_UP,
        ];

        return $map[$normalized] ?? null;
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

        $chartLabels = [];
        $salesSeries = [];
        $ordersSeries = [];

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

        if ($type === 'monthly') {
            $salesMap = [];
            foreach ($sales as $row) {
                $salesMap[(string) $row->date] = [
                    'total_sales' => (int) $row->total_sales,
                    'count' => (int) $row->count,
                ];
            }

            $cursor = Carbon::parse($startDate)->startOfMonth();
            $last = Carbon::parse($endDate)->startOfMonth();
            while ($cursor->lte($last)) {
                $key = $cursor->format('Y-m');
                $chartLabels[] = $cursor->format('Y/m');
                $salesSeries[] = $salesMap[$key]['total_sales'] ?? 0;
                $ordersSeries[] = $salesMap[$key]['count'] ?? 0;
                $cursor->addMonthNoOverflow();
            }
        } else {
            $dailyMap = [];
            foreach ($sales as $row) {
                $dailyMap[(string) $row->date] = [
                    'total_sales' => (int) $row->total_sales,
                    'count' => (int) $row->count,
                ];
            }

            $cursor = Carbon::parse($startDate)->startOfDay();
            $last = Carbon::parse($endDate)->startOfDay();
            while ($cursor->lte($last)) {
                $key = $cursor->format('Y-m-d');
                $chartLabels[] = $cursor->format('m/d');
                $salesSeries[] = $dailyMap[$key]['total_sales'] ?? 0;
                $ordersSeries[] = $dailyMap[$key]['count'] ?? 0;
                $cursor->addDay();
            }
        }

        $totalSales = $sales->sum('total_sales');
        $totalOrders = $sales->sum('count');
        
        // ユーザー数と商品数を取得
        $totalUsers = \App\Models\User::count();
        $totalProducts = \App\Models\Product::count();
        
        // 人気商品TOP5を取得（order_detailsにはcreated_at/priceが無いためJOINで集計）
        $topProducts = OrderDetail::query()
            ->join('orders', 'order_details.order_id', '=', 'orders.id')
            ->join('products', 'order_details.product_id', '=', 'products.id')
            ->whereBetween('orders.created_at', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59'
            ])
            ->select(
                'order_details.product_id',
                'products.name',
                DB::raw('SUM(order_details.quantity) as quantity'),
                DB::raw('SUM(products.price * order_details.quantity) as sales')
            )
            ->groupBy('order_details.product_id', 'products.name')
            ->orderByDesc('quantity')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name ?? '不明',
                    'quantity' => (int) $item->quantity,
                    'sales' => (int) $item->sales,
                ];
            });
        
        // ステータス別集計
        $statusCounts = Order::whereBetween('created_at', [
                $startDate . ' 00:00:00', 
                $endDate . ' 23:59:59'
            ])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        $recentOrders = Order::query()
            ->with('user')
            ->whereBetween('created_at', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59',
            ])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_sales' => (int)$totalSales,
                'total_orders' => (int)$totalOrders,
                'total_users' => $totalUsers,
                'total_products' => $totalProducts,
                'top_products' => $topProducts,
                'status_counts' => $statusCounts,
                'chart_labels' => $chartLabels,
                'sales_series' => $salesSeries,
                'orders_series' => $ordersSeries,
                'recent_orders' => $recentOrders,
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

    /**
     * 販売者向け売上・注文履歴を取得
     */
    public function sellerReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => [
                'nullable',
                Rule::in([
                    'all',
                    Order::STATUS_COOKING,
                    Order::STATUS_COMPLETED,
                    Order::STATUS_PICKED_UP,
                    'cooking',
                    'completed',
                    'picked_up',
                ]),
            ],
        ]);

        $user = auth('sanctum')->user();
        $startDate = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $validated['end_date'] ?? now()->toDateString();
        $status = $validated['status'] ?? 'all';

        $rows = $this->getSellerReportRows((int) $user->id, $startDate, $endDate, $status);
        $orders = $this->groupSellerReportOrders($rows);

        return response()->json([
            'success' => true,
            'data' => [
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => $status,
                ],
                'summary' => $this->buildSellerReportSummary($orders, $rows),
                'orders' => $orders,
            ],
        ]);
    }

    /**
     * 販売者向け売上・注文履歴をCSVで出力
     */
    public function sellerReportExport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => [
                'nullable',
                Rule::in([
                    'all',
                    Order::STATUS_COOKING,
                    Order::STATUS_COMPLETED,
                    Order::STATUS_PICKED_UP,
                    'cooking',
                    'completed',
                    'picked_up',
                ]),
            ],
        ]);

        $user = auth('sanctum')->user();
        $startDate = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $validated['end_date'] ?? now()->toDateString();
        $status = $validated['status'] ?? 'all';

        $rows = $this->getSellerReportRows((int) $user->id, $startDate, $endDate, $status);
        $downloadName = sprintf('seller_report_%s_to_%s.csv', $startDate, $endDate);

        return response()->streamDownload(function () use ($rows) {
            echo "\xEF\xBB\xBF";

            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['注文ID', '注文日時', 'ステータス', '顧客', '商品名', '単価', '数量', '小計']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['order_id'],
                    $row['order_created_at'],
                    $row['status'],
                    $row['customer_name'],
                    $row['product_name'],
                    $row['unit_price'],
                    $row['quantity'],
                    $row['subtotal'],
                ]);
            }

            fclose($handle);
        }, $downloadName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function getSellerReportRows(int $sellerId, string $startDate, string $endDate, string $status = 'all')
    {
        $query = OrderDetail::query()
            ->join('orders', 'order_details.order_id', '=', 'orders.id')
            ->join('products', 'order_details.product_id', '=', 'products.id')
            ->leftJoin('users as customers', 'orders.user_id', '=', 'customers.id')
            ->where('products.seller_id', $sellerId)
            ->whereBetween('orders.created_at', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59',
            ])
            ->select([
                'orders.id as order_id',
                'orders.status',
                'orders.created_at as order_created_at',
                'orders.updated_at as order_updated_at',
                'orders.user_id as customer_id',
                'orders.total_price as order_total_price',
                'products.name as product_name',
                'products.price as unit_price',
                'order_details.quantity as quantity',
                'customers.username as customer_username',
                'customers.shop_name as customer_shop_name',
                'customers.name_2nd as customer_name_2nd',
                'customers.name_1st as customer_name_1st',
            ])
            ->orderByDesc('orders.created_at')
            ->orderByDesc('orders.id')
            ->orderBy('order_details.id');

        if ($status !== 'all') {
            $normalizedStatus = $this->normalizeStatus($status);
            if ($normalizedStatus !== null) {
                $query->where('orders.status', $normalizedStatus);
            }
        }

        return $query->get()->map(function ($row) {
            $quantity = (int) $row->quantity;
            $unitPrice = (int) $row->unit_price;

            return [
                'order_id' => (int) $row->order_id,
                'order_created_at' => Carbon::parse((string) $row->order_created_at)->format('Y-m-d H:i:s'),
                'order_updated_at' => Carbon::parse((string) $row->order_updated_at)->format('Y-m-d H:i:s'),
                'status' => (string) $row->status,
                'customer_name' => $this->formatSellerCustomerName($row),
                'product_name' => (string) $row->product_name,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'subtotal' => $unitPrice * $quantity,
            ];
        });
    }

    private function groupSellerReportOrders($rows): array
    {
        return $rows
            ->groupBy('order_id')
            ->map(function ($items) {
                $items = $items->values();
                $first = $items->first();

                return [
                    'order_id' => $first['order_id'],
                    'order_created_at' => $first['order_created_at'],
                    'status' => $first['status'],
                    'customer_name' => $first['customer_name'],
                    'total_quantity' => (int) $items->sum('quantity'),
                    'total_sales' => (int) $items->sum('subtotal'),
                    'item_summary' => $items
                        ->map(fn ($item) => $item['product_name'] . ' ×' . $item['quantity'])
                        ->implode('、'),
                    'items' => $items->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function buildSellerReportSummary(array $orders, $rows): array
    {
        $totalSales = collect($orders)->sum('total_sales');
        $totalOrders = count($orders);
        $totalQuantity = collect($orders)->sum('total_quantity');

        return [
            'total_sales' => (int) $totalSales,
            'total_orders' => (int) $totalOrders,
            'total_quantity' => (int) $totalQuantity,
            'average_order_value' => $totalOrders > 0 ? (int) round($totalSales / $totalOrders) : 0,
            'status_counts' => collect($orders)
                ->groupBy('status')
                ->map(fn ($group) => $group->count())
                ->all(),
            'detail_rows' => $rows->count(),
        ];
    }

    private function formatSellerCustomerName($row): string
    {
        $shopName = trim((string) ($row->customer_shop_name ?? ''));
        if ($shopName !== '') {
            return $shopName;
        }

        $fullName = trim((string) ($row->customer_name_2nd ?? '') . ' ' . (string) ($row->customer_name_1st ?? ''));
        if ($fullName !== '') {
            return $fullName;
        }

        $username = trim((string) ($row->customer_username ?? ''));
        return $username !== '' ? $username : '不明';
    }
}
