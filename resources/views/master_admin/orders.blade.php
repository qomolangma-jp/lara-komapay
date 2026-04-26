@extends('layouts.master_layout')

@section('title', '注文管理')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">注文管理</h1>
    <div>
        <button class="btn btn-primary" onclick="loadOrders()">
            <i class="fas fa-sync me-1"></i>更新
        </button>
    </div>
</div>

<style>
    .mobile-order-card {
        border: 1px solid var(--color-border);
        border-radius: var(--radius-md);
        background: var(--color-surface);
        box-shadow: var(--shadow-sm);
    }
    .mobile-order-card + .mobile-order-card {
        margin-top: 12px;
    }
    .mobile-order-card .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
    }
    .mobile-order-card .mobile-action-btn {
        min-height: 44px;
        width: 100%;
        border-radius: 10px;
        font-weight: 700;
    }
    .mobile-order-card .meta-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-top: 8px;
    }
    .mobile-order-card .meta-item {
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

<div class="alert alert-info border mb-3">
    <i class="fas fa-layer-group me-1"></i>
    管理者向け高密度表示: 一覧で主要情報を確認し、各行の「詳細」で展開できます。
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
                <label class="form-label">ステータス絞り込み</label>
                <select class="form-select" id="statusFilter" onchange="filterOrders()">
                    <option value="">すべて</option>
                    <option value="調理中">調理中</option>
                    <option value="完了">完了</option>
                    <option value="受渡済">受渡済</option>
                    <option value="完成">完成（旧）</option>
                    <option value="受取済">受取済（旧）</option>
                    <option value="キャンセル">キャンセル</option>
                </select>
            </div>
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
                        <th>注文ID</th>
                        <th>ユーザー</th>
                        <th>学籍番号</th>
                        <th>商品点数</th>
                        <th>合計金額</th>
                        <th>ステータス</th>
                        <th>注文日時</th>
                        <th>更新日時</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="orders-list">
                    <tr><td colspan="10" class="text-center">読み込み中...</td></tr>
                </tbody>
            </table>
        </div>
        <div id="orders-cards" class="d-lg-none">
            <div class="text-center text-muted py-4">読み込み中...</div>
        </div>
    </div>
</div>

<div class="modal fade" id="statusConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">操作確認</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="statusConfirmMessage">
                ステータスを変更します。よろしいですか？
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">戻る</button>
                <button type="button" class="btn btn-primary" onclick="executeConfirmedStatusUpdate()">実行</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let allOrders = [];
    let selectedDate = '';
    let pendingStatusAction = null;

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
                allOrders = result.data.data || [];
                filterOrders();
            } else {
                const errorText = await response.text();
                console.error('注文の読み込みエラー:', response.status, errorText);
                showAlert('danger', `注文の読み込みに失敗しました (${response.status})`);
            }
        } catch (error) {
            console.error('注文の読み込みエラー:', error);
            showAlert('danger', 'エラーが発生しました: ' + error.message);
        }
    }

    function filterOrders() {
        const statusFilter = document.getElementById('statusFilter').value;
        const filtered = statusFilter ? 
            allOrders.filter(o => o.status === statusFilter) : 
            allOrders;
        displayOrders(filtered);
    }

    function displayOrders(orders) {
        const tbody = document.getElementById('orders-list');
        const cards = document.getElementById('orders-cards');
        if (!orders || orders.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10" class="text-center">注文がありません</td></tr>';
            cards.innerHTML = '<div class="text-center text-muted py-4">注文がありません</div>';
            return;
        }

        function getUserDisplayName(user) {
            if (!user) return '不明';

            const fullName = `${user.name_2nd || ''} ${user.name_1st || ''}`.trim();
            if (fullName) {
                return fullName;
            }

            return user.display_name || user.name || user.username || '不明';
        }

        const tableRows = orders.map(order => {
            const statusMeta = getStatusMeta(order.status);
            const statusBadgeHtml = `<span class="badge status-badge bg-${statusMeta.badgeClass}">${statusMeta.label}</span>`;

            const user = order.user || {};
            const detailRows = (order.details || []).map(detail => {
                const product = detail.product || {};
                const quantity = Number(detail.quantity || 0);
                const unitPrice = Number(product.price || 0);
                const subtotal = quantity * unitPrice;
                return `
                    <tr>
                        <td>${product.name || '不明'}</td>
                        <td class="text-end">${quantity}</td>
                        <td class="text-end">¥${unitPrice.toLocaleString()}</td>
                        <td class="text-end">¥${subtotal.toLocaleString()}</td>
                    </tr>
                `;
            }).join('') || '<tr><td colspan="4" class="text-center text-muted">商品情報がありません</td></tr>';

            const totalItems = (order.details || []).reduce((sum, detail) => sum + Number(detail.quantity || 0), 0);
            const displayName = getUserDisplayName(user);
            const studentId = user.student_id || '-';

            const detailHtml = `
                <div class="p-3 bg-light border rounded-3">
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>注文ID:</strong> #${order.id}</div>
                        <div class="col-md-3"><strong>氏名:</strong> ${displayName}</div>
                        <div class="col-md-3"><strong>学籍番号:</strong> ${studentId}</div>
                        <div class="col-md-3"><strong>ステータス:</strong> ${statusBadgeHtml}</div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr><th>商品名</th><th class="text-end">数量</th><th class="text-end">単価</th><th class="text-end">小計</th></tr>
                            </thead>
                            <tbody>${detailRows}</tbody>
                        </table>
                    </div>
                    <div class="text-end mt-2 fw-bold">合計: ¥${Number(order.total_price || 0).toLocaleString()}</div>
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
                <div class="mobile-order-card p-3">
                    <div class="summary-row mb-2">
                        <div class="fw-bold">#${order.id}</div>
                        ${statusBadgeHtml}
                    </div>
                    <div class="meta-grid">
                        <div class="meta-item"><div class="small text-muted">ユーザー</div><div class="fw-semibold">${displayName}</div></div>
                        <div class="meta-item"><div class="small text-muted">学籍番号</div><div class="fw-semibold">${studentId}</div></div>
                        <div class="meta-item"><div class="small text-muted">商品点数</div><div class="fw-semibold">${totalItems}</div></div>
                        <div class="meta-item"><div class="small text-muted">合計金額</div><div class="fw-semibold">¥${Number(order.total_price || 0).toLocaleString()}</div></div>
                    </div>
                    <div class="small text-muted mt-2">注文: ${new Date(order.created_at).toLocaleString('ja-JP')}</div>
                    <div class="small text-muted">更新: ${new Date(order.updated_at || order.created_at).toLocaleString('ja-JP')}</div>

                    <div class="row g-2 mt-2">
                        <div class="col-12">
                            <button class="btn btn-outline-secondary mobile-action-btn" onclick="toggleOrderDetailRow(${order.id})">詳細を表示/非表示</button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-warning mobile-action-btn" onclick="openStatusConfirmModal(${order.id}, '完了')">完了</button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-success mobile-action-btn" onclick="openStatusConfirmModal(${order.id}, '受渡済')">受渡済</button>
                        </div>
                    </div>
                    ${mobileDetailHtml}
                </div>
            `;

            return {
                table: `
                <tr>
                    <td>
                        <button class="btn btn-outline-secondary btn-sm rounded-0" onclick="toggleOrderDetailRow(${order.id})">
                            詳細
                        </button>
                    </td>
                    <td>#${order.id}</td>
                    <td>${displayName}</td>
                    <td>${studentId}</td>
                    <td>${totalItems}</td>
                    <td>¥${Number(order.total_price || 0).toLocaleString()}</td>
                    <td>${statusBadgeHtml}</td>
                    <td>${new Date(order.created_at).toLocaleString('ja-JP')}</td>
                    <td>${new Date(order.updated_at || order.created_at).toLocaleString('ja-JP')}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-warning rounded-0" onclick="openStatusConfirmModal(${order.id}, '完了')">
                                完了
                            </button>
                            <button class="btn btn-success rounded-0" onclick="openStatusConfirmModal(${order.id}, '受渡済')">
                                受渡済
                            </button>
                        </div>
                    </td>
                </tr>
                <tr id="detail-row-${order.id}" class="d-none">
                    <td colspan="10">${detailHtml}</td>
                </tr>
            `,
                card: mobileCard,
            };
        });

        tbody.innerHTML = tableRows.map(row => row.table).join('');
        cards.innerHTML = tableRows.map(row => row.card).join('');
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

    function openStatusConfirmModal(orderId, status) {
        pendingStatusAction = { orderId, status };

        const message = document.getElementById('statusConfirmMessage');
        if (message) {
            message.innerHTML = `注文 #${orderId} のステータスを <strong>${status}</strong> に変更します。`;
        }

        if (window.bootstrap) {
            const modal = new bootstrap.Modal(document.getElementById('statusConfirmModal'));
            modal.show();
            return;
        }

        updateStatus(orderId, status);
    }

    function executeConfirmedStatusUpdate() {
        if (!pendingStatusAction) return;

        const { orderId, status } = pendingStatusAction;
        pendingStatusAction = null;

        const modalElement = document.getElementById('statusConfirmModal');
        if (modalElement && window.bootstrap) {
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
        }

        updateStatus(orderId, status);
    }

    async function updateStatus(orderId, status) {
        try {
            const response = await fetch(`/api/master/orders/${orderId}/status`, {
                method: 'PUT',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ status })
            });

            if (response.ok) {
                showAlert('success', 'ステータスを更新しました');
                loadOrders();
            } else {
                showAlert('danger', '更新に失敗しました');
            }
        } catch (error) {
            showAlert('danger', 'エラーが発生しました');
        }
    }

    function showAlert(type, message) {
        if (window.UIFeedback) {
            window.UIFeedback.showMessage(message, type);
            window.UIFeedback.showToast(message, type);
            return;
        }

        const alertArea = document.getElementById('alert-area');
        alertArea.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show">${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
        setTimeout(() => alertArea.innerHTML = '', 5000);
    }

    selectedDate = getDateFromUrl();
    if (selectedDate) {
        document.getElementById('dateFilter').value = selectedDate;
    }
    updateDatePageInfo();
    loadOrders();
</script>
@endsection
