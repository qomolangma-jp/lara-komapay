@extends('layouts.master_layout')

@section('title', '注文管理')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">注文管理</h1>
    <div>
        <button class="btn btn-sm btn-primary" onclick="loadOrders()">
            <i class="fas fa-sync me-1"></i>更新
        </button>
    </div>
</div>

<div id="alert-area"></div>

<!-- フィルター -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <label class="form-label">ステータス絞り込み</label>
                <select class="form-select" id="statusFilter" onchange="filterOrders()">
                    <option value="">すべて</option>
                    <option value="調理中">調理中</option>
                    <option value="完成">完成</option>
                    <option value="受取済">受取済</option>
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
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>注文ID</th>
                        <th>ユーザー</th>
                        <th>合計金額</th>
                        <th>ステータス</th>
                        <th>注文日時</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="orders-list">
                    <tr><td colspan="6" class="text-center">読み込み中...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 注文詳細モーダル -->
<div class="modal fade" id="orderDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">注文詳細</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailContent">
                読み込み中...
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let allOrders = [];
    let selectedDate = '';

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
        if (!orders || orders.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">注文がありません</td></tr>';
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

        tbody.innerHTML = orders.map(order => {
            const statusClass = {
                '調理中': 'warning',
                '完成': 'info',
                '受取済': 'success',
                'キャンセル': 'danger'
            }[order.status] || 'secondary';

            return `
                <tr>
                    <td>#${order.id}</td>
                    <td>${getUserDisplayName(order.user)}</td>
                    <td>¥${order.total_price.toLocaleString()}</td>
                    <td><span class="badge bg-${statusClass}">${order.status}</span></td>
                    <td>${new Date(order.created_at).toLocaleString('ja-JP')}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-info" onclick="viewOrderDetail(${order.id})">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-warning" onclick="updateStatus(${order.id}, '完成')">
                                完成
                            </button>
                            <button class="btn btn-success" onclick="updateStatus(${order.id}, '受取済')">
                                受取済
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    async function viewOrderDetail(orderId) {
        try {
            const response = await fetch(`/api/master/orders/${orderId}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                const order = result.data;

                const userName = order.user
                    ? `${order.user.name_2nd || ''} ${order.user.name_1st || ''}`.trim() || order.user.display_name || order.user.name || order.user.username || '不明'
                    : '不明';
                
                document.getElementById('orderDetailContent').innerHTML = `
                    <div class="mb-3">
                        <strong>注文ID:</strong> #${order.id}<br>
                        <strong>氏名:</strong> ${userName}<br>
                        <strong>ステータス:</strong> ${order.status}<br>
                        <strong>注文日時:</strong> ${new Date(order.created_at).toLocaleString('ja-JP')}
                    </div>
                    <h6>注文商品</h6>
                    <table class="table table-sm">
                        <thead>
                            <tr><th>商品名</th><th>数量</th><th>単価</th><th>小計</th></tr>
                        </thead>
                        <tbody>
                            ${(order.details || []).map(d => `
                                <tr>
                                    <td>${d.product ? d.product.name : '不明'}</td>
                                    <td>${d.quantity}</td>
                                    <td>¥${d.product ? d.product.price.toLocaleString() : 0}</td>
                                    <td>¥${(d.quantity * (d.product ? d.product.price : 0)).toLocaleString()}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                    <div class="text-end">
                        <strong>合計: ¥${order.total_price.toLocaleString()}</strong>
                    </div>
                `;
                
                new bootstrap.Modal(document.getElementById('orderDetailModal')).show();
            }
        } catch (error) {
            console.error('注文詳細の読み込みエラー:', error);
        }
    }

    async function updateStatus(orderId, status) {
        if (!confirm(`ステータスを「${status}」に変更しますか？`)) return;

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
        const alertArea = document.getElementById('alert-area');
        alertArea.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
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
