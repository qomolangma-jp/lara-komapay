@extends('layouts.master_layout')

@section('title', 'ダッシュボード')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2 mb-0">ダッシュボード可視化</h1>
    <div class="text-muted small">対象期間: {{ $startDateInput }} 〜 {{ $endDateInput }}</div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">管理ショートカット</h5>
</div>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-info h-100">
            <div class="card-body d-flex flex-column h-100">
                <h5 class="card-title"><i class="fas fa-chart-line me-2"></i>売上統計</h5>
                <p class="card-text">売上データの確認・分析</p>
                <a href="/master/stats" class="btn btn-light btn-sm mt-auto">管理画面へ</a>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-primary h-100">
            <div class="card-body d-flex flex-column h-100">
                <h5 class="card-title"><i class="fas fa-users me-2"></i>ユーザー管理</h5>
                <p class="card-text">ユーザーの登録・編集・削除</p>
                <a href="/master/users" class="btn btn-light btn-sm mt-auto">管理画面へ</a>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-success h-100">
            <div class="card-body d-flex flex-column h-100">
                <h5 class="card-title"><i class="fas fa-shopping-bag me-2"></i>商品管理</h5>
                <p class="card-text">商品の登録・編集・削除</p>
                <a href="/master/products" class="btn btn-light btn-sm mt-auto">管理画面へ</a>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-warning h-100">
            <div class="card-body d-flex flex-column h-100">
                <h5 class="card-title"><i class="fas fa-receipt me-2"></i>注文管理</h5>
                <p class="card-text">注文の確認・ステータス変更</p>
                <a href="/master/orders" class="btn btn-light btn-sm mt-auto">管理画面へ</a>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-danger h-100">
            <div class="card-body d-flex flex-column h-100">
                <h5 class="card-title"><i class="fas fa-newspaper me-2"></i>ニュース管理</h5>
                <p class="card-text">お知らせの登録・編集・削除</p>
                <a href="/master/news" class="btn btn-light btn-sm mt-auto">管理画面へ</a>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-dark h-100">
            <div class="card-body d-flex flex-column h-100">
                <h5 class="card-title"><i class="fas fa-shopping-cart me-2"></i>カート管理</h5>
                <p class="card-text">カート履歴の確認・削除</p>
                <a href="/master/cart" class="btn btn-light btn-sm mt-auto">管理画面へ</a>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-secondary h-100">
            <div class="card-body d-flex flex-column h-100">
                <h5 class="card-title"><i class="fas fa-file-alt me-2"></i>ログ管理</h5>
                <p class="card-text">システムログの確認・分析</p>
                <a href="/master/logs" class="btn btn-light btn-sm mt-auto">管理画面へ</a>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-primary h-100">
            <div class="card-body d-flex flex-column h-100">
                <h5 class="card-title"><i class="fas fa-calendar-alt me-2"></i>注文可能時間設定</h5>
                <p class="card-text">日付ごとの受付時間・休止日を設定</p>
                <a href="/master/order-windows" class="btn btn-light btn-sm mt-auto">管理画面へ</a>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>絞り込み連動パネル</h5>
        <span class="badge bg-primary" style="font-size: 0.72rem;">全ウィジェットに適用</span>
    </div>
    <div class="card-body">
        <form method="GET" action="/master" class="row g-3 align-items-end js-feedback-form" data-feedback-loading="ダッシュボードを更新中...">
            <div class="col-md-3">
                <label for="start_date" class="form-label">開始日</label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="{{ $startDateInput }}">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">終了日</label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="{{ $endDateInput }}">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">注文ステータス</label>
                <select id="status" name="status" class="form-select">
                    <option value="">すべて</option>
                    @foreach($statusOptions as $status)
                        <option value="{{ $status }}" {{ $statusFilter === $status ? 'selected' : '' }}>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="widget_limit" class="form-label">表示件数</label>
                <select id="widget_limit" name="widget_limit" class="form-select">
                    @foreach([3, 5, 7, 10] as $limit)
                        <option value="{{ $limit }}" {{ (int)$widgetLimit === $limit ? 'selected' : '' }}>{{ $limit }}件</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary rounded-0 py-2 px-3" style="font-size: 0.85rem; white-space: nowrap; min-width: 100%;">
                    <i class="fas fa-search me-1"></i>適用
                </button>
            </div>
            <div class="col-md-12 d-flex gap-2">
                <a href="/master" class="btn btn-outline-secondary btn-sm">リセット</a>
                <a href="/master/stats" class="btn btn-outline-primary btn-sm">売上統計画面へ</a>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-xl-2 col-md-4 col-6 mb-3">
        <div class="card h-100 border-0" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color:#fff;">
            <div class="card-body">
                <div class="small opacity-75">総売上</div>
                <div class="h4 mb-0">¥{{ number_format($kpi['total_sales']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6 mb-3">
        <div class="card h-100 border-0" style="background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); color:#fff;">
            <div class="card-body">
                <div class="small opacity-75">注文数</div>
                <div class="h4 mb-0">{{ number_format($kpi['total_orders']) }}件</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6 mb-3">
        <div class="card h-100 border-0" style="background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%); color:#fff;">
            <div class="card-body">
                <div class="small opacity-75">利用ユーザー数</div>
                <div class="h4 mb-0">{{ number_format($kpi['unique_users']) }}人</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6 mb-3">
        <div class="card h-100 border-0" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color:#fff;">
            <div class="card-body">
                <div class="small opacity-75">販売個数</div>
                <div class="h4 mb-0">{{ number_format($kpi['total_items']) }}個</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6 mb-3">
        <div class="card h-100 border-0" style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); color:#fff;">
            <div class="card-body">
                <div class="small opacity-75">平均客単価</div>
                <div class="h4 mb-0">¥{{ number_format($kpi['avg_order_value']) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-8 mb-3">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>売上／注文数トレンド</h5>
                <span class="text-muted small">日次</span>
            </div>
            <div class="card-body">
                <canvas id="salesOrdersChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-3">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>ステータス内訳</h5>
            </div>
            <div class="card-body">
                <canvas id="statusChart" height="240"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>人気商品ランキング</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>商品名</th>
                                <th class="text-end">販売数</th>
                                <th class="text-end">売上</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topProducts as $product)
                                <tr>
                                    <td>{{ $product->name }}</td>
                                    <td class="text-end">{{ number_format($product->quantity) }}個</td>
                                    <td class="text-end">¥{{ number_format($product->sales) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">データがありません</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-clock me-2"></i>最近の注文</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>氏名</th>
                                <th>ステータス</th>
                                <th class="text-end">金額</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOrders as $order)
                                @php
                                    $displayName = trim((($order->user->name_2nd ?? '') . ' ' . ($order->user->name_1st ?? '')));
                                    $displayName = $displayName !== '' ? $displayName : ($order->user->username ?? '不明');
                                @endphp
                                <tr>
                                    <td>#{{ $order->id }}</td>
                                    <td>{{ $displayName }}</td>
                                    <td><span class="badge bg-secondary">{{ $order->status }}</span></td>
                                    <td class="text-end">¥{{ number_format($order->total_price) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">データがありません</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    const chartLabels = @json($chartLabels);
    const salesSeries = @json($salesSeries);
    const ordersSeries = @json($ordersSeries);
    const statusMap = @json($statusCounts);

    const salesOrdersCtx = document.getElementById('salesOrdersChart');
    if (salesOrdersCtx) {
        new Chart(salesOrdersCtx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: '売上 (円)',
                        data: salesSeries,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.15)',
                        fill: true,
                        tension: 0.3,
                        yAxisID: 'ySales'
                    },
                    {
                        label: '注文数 (件)',
                        data: ordersSeries,
                        borderColor: '#16a34a',
                        backgroundColor: 'rgba(22, 163, 74, 0.15)',
                        fill: false,
                        tension: 0.3,
                        yAxisID: 'yOrders'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    ySales: {
                        type: 'linear',
                        position: 'left',
                        ticks: {
                            callback: function(value) {
                                return '¥' + Number(value).toLocaleString();
                            }
                        }
                    },
                    yOrders: {
                        type: 'linear',
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }

    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        const labels = Object.keys(statusMap);
        const values = Object.values(statusMap);
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: ['#2563eb', '#16a34a', '#f59e0b', '#ef4444', '#0ea5e9', '#8b5cf6']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
</script>
@endsection
