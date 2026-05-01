@extends('layouts.seller_layout')

@section('title', '注文管理')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">注文管理（閲覧のみ）</h1>
    <div>
        <button class="btn btn-success" onclick="loadOrders()">
            <i class="fas fa-sync me-1"></i>更新
        </button>
    </div>
</div>

<style>
    .seller-mobile-order-card {
        border: 1px solid var(--color-border);
        border-radius: var(--radius-md);
        background: var(--color-surface);
        box-shadow: var(--shadow-sm);
    }
    .seller-mobile-order-card + .seller-mobile-order-card {
        margin-top: 12px;
    }
    .seller-mobile-order-card .mobile-action-btn {
        min-height: 44px;
        width: 100%;
        border-radius: 10px;
        font-weight: 700;
    }
    .seller-mobile-order-card .meta-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-top: 8px;
    }
    .seller-mobile-order-card .meta-item {
        background: var(--color-surface-muted);
        border-radius: 8px;
        padding: 8px;
    }
    .status-badge {
        min-width: 72px;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        font-weight: 700;
    }
</style>

<div id="alert-area"></div>

<div class="alert alert-light border mb-3">
    <i class="fas fa-compress me-1"></i>
    販売者向け最小表示: 必要情報のみ表示し、各行の「詳細」で内訳を確認できます。
</div>

<div class="alert alert-light border mb-3">
    <i class="fas fa-circle-info me-1"></i>
    ステータス凡例:
    <span class="badge status-badge bg-warning text-dark ms-1">調理中</span>
    <span class="badge status-badge bg-info ms-1">完了</span>
    <span class="badge status-badge bg-success ms-1">受渡済</span>
    <span class="badge status-badge bg-danger ms-1">停止</span>
</div>

<!-- フィルター -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <label class="form-label">日付ページ</label>
                <input type="date" class="form-control" id="dateFilter" onchange="applyDatePage()">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-outline-secondary w-100" onclick="clearDatePage()">
                    日付指定を解除
                </button>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-light border mb-3" id="datePageInfo" style="display:none;"></div>

<!-- 商品集計 -->
<div class="card mb-3">
    <div class="card-body">
        <h5 class="card-title mb-2">商品集計 <small class="text-muted">表示対象: <span id="summary-date"></span></small></h5>
        <div id="product-summary">
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-sm">
                    <thead>
                        <tr><th>商品名</th><th style="width:120px;">合計数</th><th style="width:160px;">合計金額</th></tr>
                    </thead>
                    <tbody id="product-summary-tbody"><tr><td colspan="3" class="text-center">集計中...</td></tr></tbody>
                </table>
            </div>
            <div id="product-summary-mobile" class="d-lg-none"></div>
        </div>
    </div>
</div>

<!-- 注文一覧 -->
<div class="card">
    <div class="card-body">
        <!-- デスクトップ表示 -->
        <div class="table-responsive d-none d-lg-block">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width: 90px;">詳細</th>
                        <th>商品名</th>
                        <th>合計の数</th>
                        <th>合計金額</th>
                    </tr>
                </thead>
                <tbody id="orders-list">
                    <tr><td colspan="4" class="text-center">読み込み中...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- モバイル表示 -->
        <div id="orders-cards" class="d-lg-none"></div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const token = localStorage.getItem('token') || '';
    const user = JSON.parse(localStorage.getItem('user') || '{}');

    // ヘッダーを生成するヘルパー関数
    function getHeaders(contentType = null) {
        const headers = {
            'Accept': 'application/json'
        };
        
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        
        if (contentType) {
            headers['Content-Type'] = contentType;
        }
        
        return headers;
    }

    let allOrders = [];
    let selectedDate = '';
    let isDefaultToday = false;

    const STATUS_META = {
        '調理中': { badgeClass: 'warning text-dark', label: '調理中' },
        '完了': { badgeClass: 'info', label: '完了' },
        '完成': { badgeClass: 'info', label: '完了' },
        '受渡済': { badgeClass: 'success', label: '受渡済' },
        '受取済': { badgeClass: 'success', label: '受渡済' },
        'キャンセル': { badgeClass: 'danger', label: '停止' },
    };

    function getStatusMeta(status) {
        return STATUS_META[status] || { badgeClass: 'secondary', label: status || '不明' };
    }

    function getDateFromUrl() {
        const params = new URLSearchParams(window.location.search);
        return params.get('date') || '';
    }

    function setDateToUrl(date) {
        const url = new URL(window.location.href);
        if (date) {
            url.searchParams.set('date', date);
        } else {
            url.searchParams.delete('date');
        }
        window.history.replaceState({}, '', url.toString());
    }

    function updateDatePageInfo() {
        const info = document.getElementById('datePageInfo');
        if (!selectedDate) {
            info.style.display = 'none';
            return;
        }

        info.style.display = 'block';
        const suffix = (isDefaultToday ? '（本日）' : '');
        info.textContent = `表示対象: ${selectedDate} ${suffix}`;
    }

    function applyDatePage() {
        selectedDate = document.getElementById('dateFilter').value || '';
        setDateToUrl(selectedDate);
        updateDatePageInfo();
        loadOrders();
    }

    function clearDatePage() {
        selectedDate = '';
        document.getElementById('dateFilter').value = '';
        setDateToUrl('');
        updateDatePageInfo();
        loadOrders();
    }

    // 注文一覧を読み込み（自分の商品の注文のみ）
    async function loadOrders() {
        try {
            const params = new URLSearchParams();
            if (selectedDate) {
                params.set('date', selectedDate);
            }
            const query = params.toString();
            const url = query ? `/api/seller/orders?${query}` : '/api/seller/orders';
            
            const response = await fetch(url, {
                headers: getHeaders()
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            allOrders = data.data || [];

            // 自分の商品を含む注文のみをフィルター
            const myOrders = filterMyOrders(allOrders);
            displayOrders(myOrders);
            updateProductSummary(myOrders);
        } catch (error) {
            UIFeedback.showToast(`注文の読み込みエラー: ${error.message}`, 'danger');
            console.error('Orders load error:', error);
        }
    }

    // 自分の商品を含む注文のみをフィルター
    function filterMyOrders(orders) {
        const myOrders = [];
        for (const order of orders) {
            const myDetails = (order.details || [])
                .filter(detail => detail.product && detail.product.seller_id === user.id);

            if (myDetails.length > 0) {
                myOrders.push({
                    ...order,
                    details: myDetails
                });
            }
        }
        
        return myOrders;
    }

    function displayOrders(orders) {
        const tbody = document.getElementById('orders-list');
        const cards = document.getElementById('orders-cards');

        if (!orders || orders.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">注文がありません</td></tr>';
            cards.innerHTML = '<div class="text-center text-muted py-4">注文がありません</div>';
            return;
        }
        
        const rows = orders.map(order => {
            const statusMeta = getStatusMeta(order.status);
            const statusBadgeHtml = `<span class="badge status-badge bg-${statusMeta.badgeClass}">${statusMeta.label}</span>`;

            const myDetails = order.details || [];

            const productNames = myDetails.length > 0
                ? myDetails.map(detail => `${detail.product.name} ×${detail.quantity || 0}`).join('、')
                : '不明';

            const totalQuantity = myDetails.reduce((sum, detail) => {
                return sum + (detail.quantity || 0);
            }, 0);

            const myTotal = myDetails
                .reduce((sum, detail) => {
                    const unitPrice = detail.product ? (detail.product.price || 0) : 0;
                    return sum + (unitPrice * (detail.quantity || 0));
                }, 0);

            return `
                <tr>
                    <td><button class="btn btn-sm btn-outline-secondary" onclick="toggleOrderDetailRow(${order.id})"><i class="fas fa-eye"></i></button></td>
                    <td>${productNames}</td>
                    <td>${totalQuantity}</td>
                    <td>¥${myTotal.toLocaleString()}</td>
                </tr>
                <tr class="order-detail-row" id="detail-${order.id}" style="display:none;">
                    <td colspan="4">
                        <div class="p-3 bg-light border rounded-3">
                            <div class="row mb-2">
                                <div class="col-md-3"><strong>注文ID:</strong> #${order.id}</div>
                                <div class="col-md-3"><strong>ステータス:</strong> ${statusBadgeHtml}</div>
                                <div class="col-md-6"><strong>注文日時:</strong> ${new Date(order.created_at).toLocaleString('ja-JP')}</div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr><th>商品名</th><th class="text-end">数量</th><th class="text-end">単価</th><th class="text-end">小計</th></tr>
                                    </thead>
                                    <tbody>
                                        ${myDetails.map(detail => {
                                            const product = detail.product || {};
                                            const quantity = Number(detail.quantity || 0);
                                            const price = Number(product.price || 0);
                                            const subtotal = quantity * price;
                                            return `<tr><td>${product.name || '不明'}</td><td class="text-end">${quantity}</td><td class="text-end">¥${price.toLocaleString()}</td><td class="text-end">¥${subtotal.toLocaleString()}</td></tr>`;
                                        }).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        tbody.innerHTML = rows;

        // モバイル表示用カードレンダリング
        const cardHtml = orders.map(order => {
            const statusMeta = getStatusMeta(order.status);
            const myDetails = order.details || [];
            const totalQuantity = myDetails.reduce((sum, d) => sum + (d.quantity || 0), 0);
            const myTotal = myDetails.reduce((sum, d) => {
                const price = d.product ? (d.product.price || 0) : 0;
                return sum + (price * (d.quantity || 0));
            }, 0);

            return `
                <div class="seller-mobile-order-card p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="small text-muted mb-1">注文ID: #${order.id}</div>
                            <div class="mb-2"><span class="badge status-badge bg-${statusMeta.badgeClass}">${statusMeta.label}</span></div>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary ms-2" onclick="toggleOrderDetailRow(${order.id})"><i class="fas fa-eye"></i></button>
                    </div>
                    <div class="mb-2">
                        <strong>商品:</strong> ${myDetails.map(d => `${d.product.name} ×${d.quantity}`).join('、')}
                    </div>
                    <div class="meta-grid">
                        <div class="meta-item">
                            <small class="text-muted">合計数</small>
                            <div class="fw-bold">${totalQuantity}個</div>
                        </div>
                        <div class="meta-item">
                            <small class="text-muted">合計金額</small>
                            <div class="fw-bold">¥${myTotal.toLocaleString()}</div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        cards.innerHTML = cardHtml;
    }

    function toggleOrderDetailRow(orderId) {
        const detailRow = document.getElementById(`detail-${orderId}`);
        if (detailRow) {
            detailRow.style.display = detailRow.style.display === 'none' ? 'table-row' : 'none';
        }
    }

    function updateProductSummary(orders) {
        const aggregates = computeProductAggregates(orders);
        renderProductAggregates(aggregates);
    }

    function computeProductAggregates(orders) {
        const agg = {};
        for (const order of orders) {
            for (const detail of order.details || []) {
                if (!detail.product) continue;
                const pid = detail.product.id;
                if (!agg[pid]) {
                    agg[pid] = {
                        name: detail.product.name,
                        qty: 0,
                        revenue: 0
                    };
                }
                const qty = Number(detail.quantity || 0);
                const price = Number(detail.product.price || 0);
                agg[pid].qty += qty;
                agg[pid].revenue += qty * price;
            }
        }
        return agg;
    }

    function renderProductAggregates(aggregates) {
        const tbody = document.getElementById('product-summary-tbody');
        const mobile = document.getElementById('product-summary-mobile');

        if (Object.keys(aggregates).length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center">集計対象がありません</td></tr>';
            mobile.innerHTML = '<div class="text-center text-muted py-3">集計対象がありません</div>';
            return;
        }

        const rows = Object.values(aggregates).map(item => `
            <tr>
                <td>${item.name}</td>
                <td class="text-end">${item.qty}個</td>
                <td class="text-end">¥${item.revenue.toLocaleString()}</td>
            </tr>
        `).join('');

        tbody.innerHTML = rows;

        const cardHtml = Object.values(aggregates).map(item => `
            <div class="card mb-2">
                <div class="card-body p-2">
                    <strong>${item.name}</strong>
                    <div class="small mt-1"><span class="badge bg-secondary">${item.qty}個</span></div>
                    <div class="small mt-1">¥${item.revenue.toLocaleString()}</div>
                </div>
            </div>
        `).join('');

        mobile.innerHTML = cardHtml;

        // 対象日付を表示
        const today = new Date().toISOString().slice(0, 10);
        const displayDate = selectedDate || today;
        document.getElementById('summary-date').textContent = displayDate + (isDefaultToday ? '（本日）' : '');
    }

    // 初期化
    (function init() {
        const dateFromUrl = getDateFromUrl();
        if (dateFromUrl) {
            selectedDate = dateFromUrl;
            document.getElementById('dateFilter').value = dateFromUrl;
        } else {
            // デフォルトは本日
            selectedDate = new Date().toISOString().slice(0, 10);
            document.getElementById('dateFilter').value = selectedDate;
            isDefaultToday = true;
        }
        updateDatePageInfo();
        loadOrders();
    })();
</script>
@endsection
