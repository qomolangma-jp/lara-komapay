<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;

class MasterController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();
        $defaultStart = (clone $today)->subDays(29);

        $startDate = $this->safeParseDate(
            (string) $request->query('start_date', $defaultStart->format('Y-m-d')),
            $defaultStart
        )->startOfDay();

        $endDate = $this->safeParseDate(
            (string) $request->query('end_date', $today->format('Y-m-d')),
            $today
        )->endOfDay();

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        $statusFilter = trim((string) $request->query('status', ''));
        $widgetLimit = (int) $request->query('widget_limit', 5);
        $widgetLimit = max(3, min($widgetLimit, 10));

        $statusOptions = [];
        $kpi = [
            'total_sales' => 0,
            'total_orders' => 0,
            'unique_users' => 0,
            'total_items' => 0,
            'avg_order_value' => 0,
        ];
        $chartLabels = [];
        $salesSeries = [];
        $ordersSeries = [];
        $statusCounts = [];
        $topProducts = collect();
        $recentOrders = collect();

        if (Schema::hasTable('orders')) {
            $baseQuery = Order::query()->whereBetween('created_at', [$startDate, $endDate]);
            if ($statusFilter !== '') {
                $baseQuery->where('status', $statusFilter);
            }

            $kpi['total_sales'] = (int) (clone $baseQuery)->sum('total_price');
            $kpi['total_orders'] = (int) (clone $baseQuery)->count();
            $kpi['unique_users'] = (int) (clone $baseQuery)->distinct('user_id')->count('user_id');
            $kpi['avg_order_value'] = $kpi['total_orders'] > 0
                ? (int) round($kpi['total_sales'] / $kpi['total_orders'])
                : 0;

            $statusOptions = Order::query()
                ->select('status')
                ->distinct()
                ->whereNotNull('status')
                ->orderBy('status')
                ->pluck('status')
                ->filter()
                ->values()
                ->all();

            if (Schema::hasTable('order_details')) {
                $itemQuery = DB::table('order_details')
                    ->join('orders', 'order_details.order_id', '=', 'orders.id')
                    ->whereBetween('orders.created_at', [$startDate, $endDate]);

                if ($statusFilter !== '') {
                    $itemQuery->where('orders.status', $statusFilter);
                }

                $kpi['total_items'] = (int) $itemQuery->sum('order_details.quantity');
            }

            $statusCounts = (clone $baseQuery)
                ->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->orderBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $dailyRows = (clone $baseQuery)
                ->selectRaw('DATE(created_at) as day, COUNT(*) as orders_count, COALESCE(SUM(total_price), 0) as sales_total')
                ->groupBy('day')
                ->orderBy('day')
                ->get();

            $dailyMap = [];
            foreach ($dailyRows as $row) {
                $dailyMap[(string) $row->day] = [
                    'sales_total' => (int) $row->sales_total,
                    'orders_count' => (int) $row->orders_count,
                ];
            }

            $cursor = $startDate->copy()->startOfDay();
            $last = $endDate->copy()->startOfDay();
            while ($cursor->lte($last)) {
                $key = $cursor->format('Y-m-d');
                $chartLabels[] = $cursor->format('m/d');
                $salesSeries[] = $dailyMap[$key]['sales_total'] ?? 0;
                $ordersSeries[] = $dailyMap[$key]['orders_count'] ?? 0;
                $cursor->addDay();
            }

            if (Schema::hasTable('order_details') && Schema::hasTable('products')) {
                $topProductsQuery = DB::table('order_details')
                    ->join('orders', 'order_details.order_id', '=', 'orders.id')
                    ->join('products', 'order_details.product_id', '=', 'products.id')
                    ->whereBetween('orders.created_at', [$startDate, $endDate])
                    ->select(
                        'products.name',
                        DB::raw('SUM(order_details.quantity) as quantity'),
                        DB::raw('SUM(order_details.quantity * products.price) as sales')
                    )
                    ->groupBy('products.id', 'products.name')
                    ->orderByDesc('quantity')
                    ->limit($widgetLimit);

                if ($statusFilter !== '') {
                    $topProductsQuery->where('orders.status', $statusFilter);
                }

                $topProducts = $topProductsQuery->get();
            }

            $recentOrders = (clone $baseQuery)
                ->with('user')
                ->latest('created_at')
                ->limit($widgetLimit)
                ->get();
        }

        return view('master_admin.index', [
            'kpi' => $kpi,
            'statusOptions' => $statusOptions,
            'statusFilter' => $statusFilter,
            'startDateInput' => $startDate->format('Y-m-d'),
            'endDateInput' => $endDate->format('Y-m-d'),
            'widgetLimit' => $widgetLimit,
            'chartLabels' => $chartLabels,
            'salesSeries' => $salesSeries,
            'ordersSeries' => $ordersSeries,
            'statusCounts' => $statusCounts,
            'topProducts' => $topProducts,
            'recentOrders' => $recentOrders,
        ]);
    }

    private function safeParseDate(string $value, Carbon $fallback): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable $e) {
            return $fallback->copy();
        }
    }

    public function users()
    {
        return view('master_admin.users');
    }

    public function products()
    {
        return view('master_admin.products');
    }

    public function orders()
    {
        return view('master_admin.orders');
    }

    public function news()
    {
        return view('master_admin.news');
    }

    public function stats()
    {
        return view('master_admin.stats');
    }

    public function cart()
    {
        return view('master_admin.cart');
    }

    public function logs(Request $request)
    {
        $logType = $request->get('type', 'laravel');
        $logPath = storage_path('logs/');
        $logs = [];
        $logContent = '';
        
        // ログファイルの一覧を取得
        $logFiles = [];
        if (is_dir($logPath)) {
            $files = scandir($logPath);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) === 'log') {
                    $logFiles[] = $file;
                }
            }
        }
        
        // サブディレクトリのログも取得
        $logDirs = ['api', 'web', 'database', 'errors', 'debug'];
        foreach ($logDirs as $dir) {
            $dirPath = $logPath . $dir;
            if (is_dir($dirPath)) {
                $files = scandir($dirPath);
                foreach ($files as $file) {
                    if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) === 'log') {
                        $logFiles[] = $dir . '/' . $file;
                    }
                }
            }
        }
        
        // 選択されたログファイルの内容を読み込み
        $selectedLog = $request->get('file', 'laravel.log');
        $selectedLogPath = $logPath . $selectedLog;
        
        if (file_exists($selectedLogPath)) {
            $content = file_get_contents($selectedLogPath);
            // 最新の100行を表示
            $lines = explode("\n", $content);
            $lines = array_slice($lines, -100);
            $logContent = implode("\n", $lines);
        }
        
        return view('master_admin.logs', compact('logFiles', 'logContent', 'selectedLog'));
    }

    public function orderWindows()
    {
        return view('master_admin.order_windows');
    }
}
