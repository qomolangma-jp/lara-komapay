@extends('layouts.seller_layout')

@section('title', 'ダッシュボード')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">販売者ダッシュボード</h1>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-shopping-bag me-2"></i>商品管理</h5>
                <p class="card-text">商品の登録・編集・削除</p>
                <a href="/seller/products" class="btn btn-light btn-sm">管理画面へ</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-receipt me-2"></i>注文管理</h5>
                <p class="card-text">注文の確認（閲覧のみ）</p>
                <a href="/seller/orders" class="btn btn-light btn-sm">管理画面へ</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-newspaper me-2"></i>ニュース管理</h5>
                <p class="card-text">お知らせの確認（閲覧のみ）</p>
                <a href="/seller/news" class="btn btn-light btn-sm">管理画面へ</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card border-primary">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="card-title mb-1"><i class="fas fa-chart-line me-2"></i>売上・注文履歴レポート</h5>
                    <p class="card-text mb-0">期間指定の売上確認と、CSVダウンロードでの履歴保存ができます。</p>
                </div>
                <a href="/seller/reports" class="btn btn-primary">レポートを開く</a>
            </div>
        </div>
    </div>
</div>

<!-- 統計情報 -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2"></i>概要統計
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center mb-3">
                        <h6 class="text-muted">登録商品数</h6>
                        <h2 id="total-products">-</h2>
                    </div>
                    <div class="col-md-4 text-center mb-3">
                        <h6 class="text-muted">今日の注文数</h6>
                        <h2 id="today-orders">-</h2>
                    </div>
                    <div class="col-md-4 text-center mb-3">
                        <h6 class="text-muted">在庫切れ商品</h6>
                        <h2 id="out-of-stock">-</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const token = localStorage.getItem('token') || '';
    const user = JSON.parse(localStorage.getItem('user') || '{}');

    // ヘッダーを生成するヘルパー関数
    function getHeaders(contentType = null) {
        const headers = { 'Accept': 'application/json' };
        const t = (localStorage.getItem('token') || '').toString().trim();
        if (t) headers['Authorization'] = `Bearer ${t}`;
        if (contentType) headers['Content-Type'] = contentType;
        return headers;
    }

    // 統計データを読み込み
    async function loadStatistics() {
        try {
            // 商品数を取得
            const productsResponse = await fetch('/api/products', {
                headers: getHeaders()
            });

            if (productsResponse.ok) {
                const productsData = await productsResponse.json();
                const products = productsData.data || [];
                
                // 自分の商品のみをカウント（seller_id でフィルタリング）
                const myProducts = products.filter(p => p.seller_id === user.id);
                document.getElementById('total-products').textContent = myProducts.length;

                // 在庫切れ商品数
                const outOfStock = myProducts.filter(p => p.stock === 0).length;
                document.getElementById('out-of-stock').textContent = outOfStock;
            }

            // 注文数を取得
            const ordersResponse = await fetch('/api/master/orders', {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (ordersResponse.ok) {
                const ordersData = await ordersResponse.json();
                // Paginationオブジェクトから配列を取得
                const orders = ordersData.data.data || [];
                
                // 今日の日付
                const today = new Date().toISOString().split('T')[0];
                
                // 自分の商品が含まれる今日の注文をカウント
                let todayOrdersCount = 0;
                for (const order of orders) {
                    const orderDate = order.created_at.split('T')[0];
                    if (orderDate === today && order.details) {
                        const hasMyProduct = order.details.some(detail => 
                            detail.product && detail.product.seller_id === user.id
                        );
                        if (hasMyProduct) {
                            todayOrdersCount++;
                        }
                    }
                }
                
                document.getElementById('today-orders').textContent = todayOrdersCount;
            }
        } catch (error) {
            console.error('統計データの読み込みエラー:', error);
        }
    }

    // ページ読み込み時に統計データを取得
    loadStatistics();
</script>
@endsection
