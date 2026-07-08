@extends('layouts.seller_layout')

@section('title', '注文管理')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">注文管理（確認・更新）</h1>
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
    .postpay-row-selected {
        background: rgba(13, 110, 253, 0.06);
    }
    .postpay-action-bar {
        background: var(--color-surface-muted);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-md);
        padding: 12px;
        margin-bottom: 16px;
    }
</style>

<div id="alert-area"></div>

<div class="alert alert-light border mb-3">
    <i class="fas fa-compress me-1"></i>
    販売者向け最小表示: 注文確定（確認済）を経由した注文のみ表示されます。各行の「詳細」で内訳を確認できます。
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

<div class="postpay-action-bar d-flex flex-wrap gap-2 align-items-center justify-content-between">
    <div>
        <div class="fw-semibold">後払い購入の完了一括操作</div>
        <div class="small text-muted">チェックした後払い注文だけを完了にできます。</div>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="small text-muted">選択数: <span id="postpay-selected-count">0</span></span>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearPostpaySelections()">選択解除</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="completeSelectedPostpayOrders()">選択した後払いを完了</button>
    </div>
</div>

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
                        <th style="width: 72px;">後払い</th>
                        <th style="width: 90px;">詳細</th>
                        <th>商品名</th>
                        <th>合計の数</th>
                        <th>合計金額</th>
                    </tr>
                </thead>
                <tbody id="orders-list">
                    <tr><td colspan="5" class="text-center">読み込み中...</td></tr>
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
        const headers = { 'Accept': 'application/json' };
        const t = (localStorage.getItem('token') || '').toString().trim();
        if (t) {
            headers['Authorization'] = `Bearer ${t}`;
        }
        if (contentType) {
            headers['Content-Type'] = contentType;
        }
        return headers;
    }

    let allOrders = [];
    let selectedDate = '';
    let isDefaultToday = false;
    let selectedPostpayOrderIds = new Set();

    const STATUS_META = {
        '未確認': { badgeClass: 'secondary', label: '未確認' },
        '確認済': { badgeClass: 'info', label: '注文確定' },
        '注文確定': { badgeClass: 'info', label: '注文確定' },
        '調理中': { badgeClass: 'warning text-dark', label: '調理中' },
        '調理済': { badgeClass: 'primary', label: '調理済' },
        '受取済': { badgeClass: 'success', label: '受取済' },
        '後払い購入': { badgeClass: 'dark', label: '後払い購入' },
        '停止': { badgeClass: 'danger', label: '停止' },
        '予約時間': { badgeClass: 'info', label: '予約時間' },
    };

    function getStatusMeta(status) {
        return STATUS_META[status] || { badgeClass: 'secondary', label: status || '不明' };
    }

    function getSellerStatusOptions(selectedStatus = '') {
        const allowedTransitions = {
            '確認済': ['確認済', '調理中', '停止'],
            '注文確定': ['注文確定', '調理中', '停止'],
            '後払い購入': ['後払い購入', '完了', '停止'],
            '調理中': ['調理中', '調理済', '停止'],
            '調理済': ['調理済', '受取済', '停止'],
            '受取済': ['受取済'],
            '停止': ['停止'],
        };
        const statuses = allowedTransitions[selectedStatus] || [selectedStatus];

        return statuses.map((status) => {
            const selected = status === selectedStatus ? 'selected' : '';
            const label = (status === '確認済' || status === '注文確定') ? '注文確定' : status;
            return `<option value="${status}" ${selected}>${label}</option>`;
        }).join('');
    }

    function isPostpayOrder(order) {
        return order && order.status === '後払い購入';
    }

    function updatePostpaySelectionCount() {
        const counter = document.getElementById('postpay-selected-count');
        if (counter) {
            counter.textContent = String(selectedPostpayOrderIds.size);
        }
    }

    function clearPostpaySelections() {
        selectedPostpayOrderIds.clear();
        document.querySelectorAll('.postpay-select-checkbox').forEach((checkbox) => {
            checkbox.checked = false;
        });
        updatePostpaySelectionCount();
    }

    function syncPostpaySelections(orders) {
        const availableIds = new Set(
            (orders || [])
                .filter((order) => isPostpayOrder(order))
                .map((order) => Number(order.id))
        );

        let changed = false;
        selectedPostpayOrderIds.forEach((orderId) => {
            if (!availableIds.has(Number(orderId))) {
                selectedPostpayOrderIds.delete(Number(orderId));
                changed = true;
            }
        });

        if (changed) {
            updatePostpaySelectionCount();
        }
    }

    function togglePostpaySelection(orderId, checked) {
        if (checked) {
            selectedPostpayOrderIds.add(Number(orderId));
        } else {
            selectedPostpayOrderIds.delete(Number(orderId));
        }
        updatePostpaySelectionCount();
    }

    async function completePostpayOrder(orderId) {
        const response = await fetch(`/api/seller/orders/${orderId}/status`, {
            method: 'PUT',
            headers: getHeaders('application/json'),
            body: JSON.stringify({ status: '完了' })
        });

        const result = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(result.message || `HTTP error! status: ${response.status}`);
        }

        return result;
    }

    async function completeSelectedPostpayOrders() {
        const orderIds = Array.from(selectedPostpayOrderIds);

        if (orderIds.length === 0) {
            UIFeedback.showToast('完了にする後払い注文を選択してください', 'warning');
            return;
        }

        if (!window.confirm(`選択した${orderIds.length}件の後払い注文を完了にします。よろしいですか？`)) {
            return;
        }

        try {
            UIFeedback.showLoading('後払い注文を完了に更新しています...');
            await Promise.all(orderIds.map((orderId) => completePostpayOrder(orderId)));
            selectedPostpayOrderIds.clear();
            UIFeedback.showToast('後払い注文を完了にしました', 'success');
            loadOrders();
        } catch (error) {
            console.error('Bulk postpay completion error:', error);
            UIFeedback.showToast(`後払い注文の完了に失敗しました: ${error.message}`, 'danger');
        } finally {
            UIFeedback.hideLoading();
        }
    }

    async function updateOrderStatus(orderId) {
        const statusSelect = document.getElementById(`seller-status-${orderId}`) || document.getElementById(`seller-status-mobile-${orderId}`);
        if (!statusSelect) return;

        const status = statusSelect.value;

        try {
            const response = await fetch(`/api/seller/orders/${orderId}/status`, {
                method: 'PUT',
                headers: getHeaders('application/json'),
                body: JSON.stringify({ status })
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(result.message || `HTTP error! status: ${response.status}`);
            }

            UIFeedback.showToast('ステータスを更新しました', 'success');
            loadOrders();
        } catch (error) {
            console.error('Order status update error:', error);
            UIFeedback.showToast(`ステータス更新に失敗しました: ${error.message}`, 'danger');
        }
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
            
            const headers = getHeaders();
            console.debug('[orders] fetch url:', url);
            console.debug('[orders] headers:', headers);

            const response = await fetch(url, { headers });

            if (response.status === 401) {
                console.warn('[orders] 受信: 401 Unauthorized - token may be missing or invalid');
                UIFeedback.showToast('認証エラー：トークンが無効です。再ログインしてください', 'danger');
                return;
            }

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

        syncPostpaySelections(orders);
        updatePostpaySelectionCount();

        if (!orders || orders.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">注文がありません</td></tr>';
            cards.innerHTML = '<div class="text-center text-muted py-4">注文がありません</div>';
            return;
        }
        
        const rows = orders.map(order => {
            const statusMeta = getStatusMeta(order.status);
            const statusBadgeHtml = `<span class="badge status-badge bg-${statusMeta.badgeClass}">${statusMeta.label}</span>`;
            const postpayCheckboxHtml = isPostpayOrder(order)
                ? `<input class="form-check-input postpay-select-checkbox" type="checkbox" ${selectedPostpayOrderIds.has(Number(order.id)) ? 'checked' : ''} onchange="togglePostpaySelection(${order.id}, this.checked)" aria-label="後払い注文 ${order.id} を選択">`
                : '<span class="text-muted small">-</span>';

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

            const rowClass = isPostpayOrder(order) && selectedPostpayOrderIds.has(Number(order.id)) ? 'postpay-row-selected' : '';
            const quickCompleteButton = isPostpayOrder(order)
                ? `<button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="completePostpayOrder(${order.id}).then(() => loadOrders()).catch((error) => UIFeedback.showToast('後払い完了に失敗しました: ' + error.message, 'danger'))">完了</button>`
                : '';

            return `
                <tr class="${rowClass}">
                    <td>${postpayCheckboxHtml}</td>
                    <td><button class="btn btn-sm btn-outline-secondary" onclick="toggleOrderDetailRow(${order.id})"><i class="fas fa-eye"></i></button></td>
                    <td>${productNames}</td>
                    <td>${totalQuantity}</td>
                    <td>¥${myTotal.toLocaleString()}${quickCompleteButton}</td>
                </tr>
                <tr class="order-detail-row" id="detail-${order.id}" style="display:none;">
                    <td colspan="5">
                        <div class="p-3 bg-light border rounded-3">
                            <div class="row mb-2">
                                <div class="col-md-3"><strong>注文ID:</strong> #${order.id}</div>
                                <div class="col-md-3"><strong>ステータス:</strong> ${statusBadgeHtml}</div>
                                <div class="col-md-3"><strong>予約時間:</strong> ${order.scheduled_at ? new Date(order.scheduled_at).toLocaleString('ja-JP') : '-'}</div>
                                <div class="col-md-3"><strong>注文日時:</strong> ${new Date(order.created_at).toLocaleString('ja-JP')}</div>
                            </div>
                            ${isPostpayOrder(order) ? `
                            <div class="alert alert-info py-2 mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div><i class="fas fa-clipboard-check me-1"></i>後払い注文です。選択して一括完了、またはこの場で「完了」を押せます。</div>
                                <button type="button" class="btn btn-sm btn-primary" onclick="completePostpayOrder(${order.id}).then(() => loadOrders()).catch((error) => UIFeedback.showToast('後払い完了に失敗しました: ' + error.message, 'danger'))">完了にする</button>
                            </div>
                            ` : ''}
                            <div class="row mb-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label mb-1">ステータス更新</label>
                                    <select class="form-select form-select-sm" id="seller-status-${order.id}">
                                        ${getSellerStatusOptions(order.status)}
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="button" class="btn btn-sm btn-primary mt-4" onclick="updateOrderStatus(${order.id})">更新</button>
                                </div>
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
            const selected = selectedPostpayOrderIds.has(Number(order.id));

            return `
                <div class="seller-mobile-order-card p-3 ${isPostpayOrder(order) && selected ? 'postpay-row-selected' : ''}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="small text-muted mb-1">注文ID: #${order.id}</div>
                            <div class="mb-2"><span class="badge status-badge bg-${statusMeta.badgeClass}">${statusMeta.label}</span></div>
                        </div>
                        <div class="d-flex align-items-center gap-2 ms-2">
                            ${isPostpayOrder(order) ? `<input class="form-check-input postpay-select-checkbox" type="checkbox" ${selected ? 'checked' : ''} onchange="togglePostpaySelection(${order.id}, this.checked)" aria-label="後払い注文 ${order.id} を選択">` : ''}
                            <button class="btn btn-sm btn-outline-secondary" onclick="toggleOrderDetailRow(${order.id})"><i class="fas fa-eye"></i></button>
                        </div>
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
                        <div class="meta-item">
                            <small class="text-muted">予約時間</small>
                            <div class="fw-bold">${order.scheduled_at ? new Date(order.scheduled_at).toLocaleString('ja-JP') : '-'}</div>
                        </div>
                    </div>
                    ${isPostpayOrder(order) ? `
                    <div class="mt-3 d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-primary flex-grow-1" onclick="completePostpayOrder(${order.id}).then(() => loadOrders()).catch((error) => UIFeedback.showToast('後払い完了に失敗しました: ' + error.message, 'danger'))">完了にする</button>
                    </div>
                    ` : ''}
                    <div class="mt-3">
                        <label class="form-label mb-1 small">ステータス更新</label>
                        <div class="input-group input-group-sm">
                            <select class="form-select" id="seller-status-mobile-${order.id}">
                                ${getSellerStatusOptions(order.status)}
                            </select>
                            <button type="button" class="btn btn-primary" onclick="updateOrderStatus(${order.id})">更新</button>
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
        updatePostpaySelectionCount();
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
