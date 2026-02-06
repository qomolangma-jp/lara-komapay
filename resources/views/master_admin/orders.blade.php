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
        </div>
    </div>
</div>

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
    const token = localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');

    if (!token || !user.is_admin) {
        window.location.href = '/login';
    }

    let allOrders = [];

    async function loadOrders() {
        try {
            const response = await fetch('/api/orders', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                allOrders = result.data || [];
                filterOrders();
            }
        } catch (error) {
            console.error('注文の読み込みエラー:', error);
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
                    <td>${order.user ? order.user.username : '不明'}</td>
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
            const response = await fetch(`/api/orders/${orderId}`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                const order = result.data;
                
                document.getElementById('orderDetailContent').innerHTML = `
                    <div class="mb-3">
                        <strong>注文ID:</strong> #${order.id}<br>
                        <strong>ユーザー:</strong> ${order.user ? order.user.username : '不明'}<br>
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
            const response = await fetch(`/api/orders/${orderId}/status`, {
                method: 'PUT',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
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

    loadOrders();
</script>
@endsection
