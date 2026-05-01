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

<!-- 注文一覧 -->
<div class="card">
    <div class="card-body">
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
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">操作</button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><button class="dropdown-item" type="button" onclick="toggleOrderDetailRow(${order.id})"><i class="fas fa-eye me-2"></i>詳細の表示切替</button></li>
                                    </ul>
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
        info.textContent = `${selectedDate} の注文ページを表示しています。`;
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

            const response = await fetch(`/api/master/orders${query ? `?${query}` : ''}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                // Paginationオブジェクトから配列を取得
                const fetchedOrders = result.data.data || [];
                
                // 自分の商品が含まれる注文のみをフィルタリング
                allOrders = await filterMyOrders(fetchedOrders);
                displayOrders(allOrders);
            } else {
                const errorText = await response.text();
                console.error('注文の読み込みエラー:', response.status, errorText);
            }
        } catch (error) {
            console.error('注文の読み込みエラー:', error);
        }
    }

    // 自分の商品が含まれる注文をフィルタリング
    async function filterMyOrders(orders) {
        const myOrders = [];
        
        for (const order of orders) {
            // 注文にはすでにdetailsが含まれているかチェック
            if (order.details && order.details.length > 0) {
                // 自分の商品が含まれているかチェック
                const hasMyProduct = order.details.some(detail => 
                    detail.product && detail.product.seller_id === user.id
                );
                
                if (hasMyProduct) {
                    myOrders.push(order);
                }
            } else {
                // detailsがない場合は個別に取得
                try {
                    const detailsResponse = await fetch(`/api/master/orders/${order.id}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    
                    if (detailsResponse.ok) {
                        const detailsResult = await detailsResponse.json();
                        const orderWithDetails = detailsResult.data;
                        
                        // 自分の商品が含まれているかチェック
                        const hasMyProduct = orderWithDetails.details && orderWithDetails.details.some(detail => 
                            detail.product && detail.product.seller_id === user.id
                        );
                        
                        if (hasMyProduct) {
                            myOrders.push(orderWithDetails);
                        }
                    }
                } catch (error) {
                    console.error('注文詳細の取得エラー:', error);
                }
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

            const myDetails = (order.details || [])
                .filter(detail => detail.product && detail.product.seller_id === user.id);

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

            const detailRows = myDetails.map(detail => {
                const product = detail.product || {};
                const quantity = Number(detail.quantity || 0);
                const price = Number(product.price || 0);
                const subtotal = quantity * price;
                return `
                    <tr>
                        <td>${product.name || '不明'}</td>
                        <td class="text-end">${quantity}</td>
                        <td class="text-end">¥${price.toLocaleString()}</td>
                        <td class="text-end">¥${subtotal.toLocaleString()}</td>
                    </tr>
                `;
            }).join('') || '<tr><td colspan="4" class="text-center text-muted">詳細情報がありません</td></tr>';

            const detailHtml = `
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
                            <tbody>${detailRows}</tbody>
                        </table>
                    </div>
                </div>
            `;

            const mobileDetailHtml = `
                <div id="detail-card-${order.id}" class="d-none mt-2">
                    <div class="p-2 bg-light border rounded-3">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr><th>商品名</th><th class="text-end">数量</th><th class="text-end">単価</th><th class="text-end">小計</th></tr>
                            </thead>
                            <tbody>${detailRows}</tbody>
                        </table>
                    </div>
                </div>
            `;

            const mobileCard = `
                <div class="seller-mobile-order-card p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-bold">#${order.id}</div>
                        ${statusBadgeHtml}
                    </div>
                    <div class="meta-grid">
                        <div class="meta-item"><div class="small text-muted">商品</div><div class="fw-semibold">${productNames}</div></div>
                        <div class="meta-item"><div class="small text-muted">合計の数</div><div class="fw-semibold">${totalQuantity}</div></div>
                        <div class="meta-item" style="grid-column: 1 / -1;"><div class="small text-muted">合計金額</div><div class="fw-semibold">¥${myTotal.toLocaleString()}</div></div>
                    </div>
                    <div class="small text-muted mt-2">注文日時: ${new Date(order.created_at).toLocaleString('ja-JP')}</div>
                    <div class="mt-2">
                        <button class="btn btn-outline-secondary mobile-action-btn" onclick="toggleOrderDetailRow(${order.id})">詳細を表示/非表示</button>
                    </div>
                    ${mobileDetailHtml}
                </div>
            `;
            
            return {
                table: `
                <tr>
                    <td>
                        <button class="btn btn-outline-secondary btn-sm rounded-0" onclick="toggleOrderDetailRow(${order.id})">詳細</button>
                    </td>
                    <td>${productNames}</td>
                    <td>${totalQuantity}</td>
                    <td>¥${myTotal.toLocaleString()}</td>
                </tr>
                <tr id="detail-row-${order.id}" class="d-none">
                    <td colspan="4">${detailHtml}</td>
                </tr>
            `,
                card: mobileCard,
            };
        });

        tbody.innerHTML = rows.map(row => row.table).join('');
        cards.innerHTML = rows.map(row => row.card).join('');
    }

    function toggleOrderDetailRow(orderId) {
        const row = document.getElementById(`detail-row-${orderId}`);
        if (row) {
            row.classList.toggle('d-none');
        }

        const cardDetail = document.getElementById(`detail-card-${orderId}`);
        if (cardDetail) {
            cardDetail.classList.toggle('d-none');
        }
    }

    // ページ読み込み時
    selectedDate = getDateFromUrl();
    if (selectedDate) {
        document.getElementById('dateFilter').value = selectedDate;
    }
    updateDatePageInfo();
    loadOrders();
</script>
@endsection
