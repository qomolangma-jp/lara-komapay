@extends('layouts.master_layout')

@section('title', '売上統計')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">売上統計ダッシュボード</h1>
    <button class="btn btn-sm btn-primary" onclick="loadStats()">
        <i class="fas fa-sync me-1"></i>更新
    </button>
</div>

<div id="alert-area"></div>

<!-- 絞り込み連動パネル -->
<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>絞り込み連動パネル</h5>
        <span class="badge bg-primary" style="font-size: 0.72rem;">全ウィジェットに適用</span>
    </div>
    <div class="card-body">
        <form method="GET" action="/master/stats" class="row g-3 align-items-end js-feedback-form" data-feedback-loading="統計ページを更新中...">
            <div class="col-md-3">
                <label for="start_date" class="form-label">開始日</label>
                <input type="date" id="start_date" name="start_date" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">終了日</label>
                <input type="date" id="end_date" name="end_date" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">注文ステータス</label>
                <select id="status" name="status" class="form-select">
                    <option value="">すべて</option>
                    <option value="調理中">調理中</option>
                    <option value="完了">完了</option>
                    <option value="受渡済">受渡済</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary rounded-0 py-2 px-3" style="font-size: 0.85rem;">
                    <i class="fas fa-search me-1"></i>適用
                </button>
            </div>
        </form>
    </div>
</div>

<!-- KPI カード -->
<div class="row mb-4">
    <div class="col-xl-2 col-md-4 col-6 mb-3">
        <div class="card h-100 border-0" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color:#fff;">
            <div class="card-body">
                <div class="small opacity-75">総売上</div>
                <div class="h4 mb-0" id="kpi-total-sales">¥0</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6 mb-3">
        <div class="card h-100 border-0" style="background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); color:#fff;">
            <div class="card-body">
                <div class="small opacity-75">注文数</div>
                <div class="h4 mb-0" id="kpi-total-orders">0件</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6 mb-3">
        <div class="card h-100 border-0" style="background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%); color:#fff;">
            <div class="card-body">
                <div class="small opacity-75">利用ユーザー数</div>
                <div class="h4 mb-0" id="kpi-unique-users">0人</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6 mb-3">
        <div class="card h-100 border-0" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color:#fff;">
            <div class="card-body">
                <div class="small opacity-75">販売個数</div>
                <div class="h4 mb-0" id="kpi-total-items">0個</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6 mb-3">
        <div class="card h-100 border-0" style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); color:#fff;">
            <div class="card-body">
                <div class="small opacity-75">平均客単価</div>
                <div class="h4 mb-0" id="kpi-avg-order-value">¥0</div>
            </div>
        </div>
    </div>
</div>

<!-- チャート行 -->
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

<!-- サマリーカード -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-yen-sign me-2"></i>総売上</h5>
                <h2 id="total-sales">¥0</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-receipt me-2"></i>注文数</h5>
                <h2 id="total-orders">0件</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-users me-2"></i>ユーザー数</h5>
                <h2 id="total-users">0人</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-shopping-bag me-2"></i>商品数</h5>
                <h2 id="total-products">0点</h2>
            </div>
        </div>
    </div>
</div>

<!-- 人気商品TOP5 -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-star me-2"></i>人気商品TOP5
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>商品名</th>
                                <th>数量</th>
                                <th class="text-end">売上</th>
                            </tr>
                        </thead>
                        <tbody id="top-products">
                            <tr><td colspan="4" class="text-center">読み込み中...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ステータス別注文数 -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-pie me-2"></i>ステータス別注文数
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>ステータス</th>
                                <th>件数</th>
                            </tr>
                        </thead>
                        <tbody id="status-stats">
                            <tr><td colspan="2" class="text-center">読み込み中...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 最近の注文 -->
<div class="card mb-4">
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
                <tbody id="recent-orders-list">
                    <tr><td colspan="4" class="text-center">読み込み中...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    async function loadStats() {
        try {
            const response = await fetch('/api/master/stats/sales', {
                headers: { 'Accept': 'application/json' }
            });
            if (response.ok) {
                const result = await response.json();
                displayStats(result.data);
            }
        } catch (error) {
            console.error('Stats load error:', error);
            loadManualStats();
        }
    }

    async function loadManualStats() {
        try {
            const [ordersRes, usersRes, productsRes] = await Promise.all([
                fetch('/api/master/orders', { headers: { 'Accept': 'application/json' } }),
                fetch('/api/master/users', { headers: { 'Accept': 'application/json' } }),
                fetch('/api/master/products', { headers: { 'Accept': 'application/json' } })
            ]);

            const orders = ordersRes.ok ? (await ordersRes.json()).data || [] : [];
            const users = usersRes.ok ? (await usersRes.json()).data || [] : [];
            const products = productsRes.ok ? (await productsRes.json()).data || [] : [];

            const totalSales = orders.reduce((sum, o) => sum + o.total_price, 0);
            const statusCounts = {};
            orders.forEach(o => { statusCounts[o.status] = (statusCounts[o.status] || 0) + 1; });

            displayStats({
                total_sales: totalSales, total_orders: orders.length, total_users: users.length,
                total_products: products.length, top_products: [], status_counts: statusCounts
            });
        } catch (error) {
            console.error('Manual stats error:', error);
        }
    }

    function displayStats(data) {
        document.getElementById('total-sales').textContent = `¥${(data.total_sales || 0).toLocaleString()}`;
        document.getElementById('total-orders').textContent = `${data.total_orders || 0}件`;
        document.getElementById('total-users').textContent = `${data.total_users || 0}人`;
        document.getElementById('total-products').textContent = `${data.total_products || 0}点`;

        const topProducts = document.getElementById('top-products');
        if (data.top_products && data.top_products.length > 0) {
            topProducts.innerHTML = data.top_products.slice(0, 5).map((p, i) =>
                `<tr><td>${i + 1}</td><td>${p.name}</td><td>${p.quantity}個</td><td>¥${(p.sales || 0).toLocaleString()}</td></tr>`
            ).join('');
        } else {
            topProducts.innerHTML = '<tr><td colspan="4" class="text-center">データがありません</td></tr>';
        }

        const statusStats = document.getElementById('status-stats');
        if (data.status_counts && Object.keys(data.status_counts).length > 0) {
            statusStats.innerHTML = Object.entries(data.status_counts).map(([status, count]) =>
                `<tr><td>${status}</td><td>${count}件</td></tr>`
            ).join('');
        } else {
            statusStats.innerHTML = '<tr><td colspan="2" class="text-center">データがありません</td></tr>';
        }

        // KPI更新
        if (document.getElementById('kpi-total-sales')) {
            document.getElementById('kpi-total-sales').textContent = `¥${(data.total_sales || 0).toLocaleString()}`;
            document.getElementById('kpi-total-orders').textContent = `${data.total_orders || 0}件`;
            document.getElementById('kpi-unique-users').textContent = `${(data.unique_users || data.total_users || 0)}人`;
            document.getElementById('kpi-total-items').textContent = `${(data.total_items || 0)}個`;
            document.getElementById('kpi-avg-order-value').textContent = `¥${(data.avg_order_value || 0).toLocaleString()}`;
        }

        // 最近の注文
        const recentList = document.getElementById('recent-orders-list');
        if (recentList && data.recent_orders && data.recent_orders.length > 0) {
            recentList.innerHTML = data.recent_orders.slice(0, 5).map(o => {
                const nm = o.user ? ((o.user.name_2nd || '') + ' ' + (o.user.name_1st || '')).trim() : '不明';
                return `<tr><td>#${o.id}</td><td>${nm}</td><td><span class="badge bg-secondary">${o.status}</span></td><td class="text-end">¥${(o.total_price || 0).toLocaleString()}</td></tr>`;
            }).join('');
        }

        loadCharts(data);
    }

    function loadCharts(data) {
        if (typeof Chart === 'undefined') {
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js';
            s.onload = () => renderCharts(data);
            document.head.appendChild(s);
        } else {
            renderCharts(data);
        }
    }

    function renderCharts(data) {
        try {
            const ctxSales = document.getElementById('salesOrdersChart');
            if (ctxSales && data.chart_labels && data.chart_labels.length > 0) {
                new Chart(ctxSales, {
                    type: 'line',
                    data: {
                        labels: data.chart_labels,
                        datasets: [
                            { label: '売上', data: data.sales_series || [], borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,0.1)', fill: true, tension: 0.3, yAxisID: 'ySales' },
                            { label: '注文数', data: data.orders_series || [], borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,0.1)', fill: false, tension: 0.3, yAxisID: 'yOrders' }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: true,
                        plugins: { legend: { position: 'top' } },
                        scales: {
                            ySales: { type: 'linear', position: 'left', ticks: { callback: v => '¥' + v.toLocaleString() } },
                            yOrders: { type: 'linear', position: 'right', grid: { drawOnChartArea: false } }
                        }
                    }
                });
            }

            const ctxStatus = document.getElementById('statusChart');
            if (ctxStatus && data.status_counts && Object.keys(data.status_counts).length > 0) {
                const labels = Object.keys(data.status_counts);
                new Chart(ctxStatus, {
                    type: 'doughnut',
                    data: { labels: labels, datasets: [{ data: Object.values(data.status_counts), backgroundColor: ['#2563eb', '#16a34a', '#f59e0b', '#ef4444', '#06b6d4', '#8b5cf6'] }] },
                    options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom' } } }
                });
            }
        } catch (e) {
            console.log('Chart render:', e.message);
        }
    }

    loadStats();
</script>
@endsection
