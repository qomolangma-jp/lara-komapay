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

<!-- 人気商品ランキング -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-trophy me-2"></i>人気商品TOP5
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>順位</th>
                                <th>商品名</th>
                                <th>販売数</th>
                                <th>売上</th>
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
@endsection

@section('scripts')
<script>
    const token = localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');

    if (!token || !user.is_admin) {
        window.location.href = '/login';
    }

    async function loadStats() {
        try {
            const response = await fetch('/api/stats/sales', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                displayStats(result.data);
            }
        } catch (error) {
            console.error('統計データの読み込みエラー:', error);
            // エラー時は手動でデータを取得して表示
            loadManualStats();
        }
    }

    async function loadManualStats() {
        try {
            // 注文データから統計を計算
            const ordersRes = await fetch('/api/orders', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });
            
            const usersRes = await fetch('/api/users', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });
            
            const productsRes = await fetch('/api/products', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });

            const orders = ordersRes.ok ? (await ordersRes.json()).data || [] : [];
            const users = usersRes.ok ? (await usersRes.json()).data || [] : [];
            const products = productsRes.ok ? (await productsRes.json()).data || [] : [];

            // 総売上計算
            const totalSales = orders.reduce((sum, o) => sum + o.total_price, 0);
            
            // ステータス別集計
            const statusCounts = {};
            orders.forEach(o => {
                statusCounts[o.status] = (statusCounts[o.status] || 0) + 1;
            });

            displayStats({
                total_sales: totalSales,
                total_orders: orders.length,
                total_users: users.length,
                total_products: products.length,
                top_products: [],
                status_counts: statusCounts
            });
        } catch (error) {
            console.error('手動統計データの読み込みエラー:', error);
        }
    }

    function displayStats(data) {
        document.getElementById('total-sales').textContent = `¥${(data.total_sales || 0).toLocaleString()}`;
        document.getElementById('total-orders').textContent = `${data.total_orders || 0}件`;
        document.getElementById('total-users').textContent = `${data.total_users || 0}人`;
        document.getElementById('total-products').textContent = `${data.total_products || 0}点`;

        // 人気商品TOP5
        const topProducts = document.getElementById('top-products');
        if (data.top_products && data.top_products.length > 0) {
            topProducts.innerHTML = data.top_products.slice(0, 5).map((p, i) => `
                <tr>
                    <td>${i + 1}</td>
                    <td>${p.name}</td>
                    <td>${p.quantity}個</td>
                    <td>¥${(p.sales || 0).toLocaleString()}</td>
                </tr>
            `).join('');
        } else {
            topProducts.innerHTML = '<tr><td colspan="4" class="text-center">データがありません</td></tr>';
        }

        // ステータス別注文数
        const statusStats = document.getElementById('status-stats');
        if (data.status_counts && Object.keys(data.status_counts).length > 0) {
            statusStats.innerHTML = Object.entries(data.status_counts).map(([status, count]) => `
                <tr>
                    <td>${status}</td>
                    <td>${count}件</td>
                </tr>
            `).join('');
        } else {
            statusStats.innerHTML = '<tr><td colspan="2" class="text-center">データがありません</td></tr>';
        }
    }

    loadStats();
</script>
@endsection
