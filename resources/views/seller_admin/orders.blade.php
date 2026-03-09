@extends('layouts.seller_layout')

@section('title', '注文管理')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">注文管理（閲覧のみ）</h1>
    <div>
        <button class="btn btn-sm btn-success" onclick="loadOrders()">
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
                <!-- 詳細内容がここに表示される -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const token = localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');

    // 販売者権限確認（管理者またはstatus='seller'のみ）
    if (!token || (!user.is_admin && user.status !== 'seller')) {
        alert('販売者権限が必要です');
        window.location.href = '/login';
    }

    let allOrders = [];

    // 注文一覧を読み込み（自分の商品の注文のみ）
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
                allOrders = result.data;
                
                // 自分の商品が含まれる注文のみをフィルタリング
                const myOrders = await filterMyOrders(allOrders);
                displayOrders(myOrders);
            }
        } catch (error) {
            console.error('注文の読み込みエラー:', error);
        }
    }

    // 自分の商品が含まれる注文をフィルタリング
    async function filterMyOrders(orders) {
        const myOrders = [];
        
        for (const order of orders) {
            // 注文詳細を取得して自分の商品があるかチェック
            try {
                const detailsResponse = await fetch(`/api/orders/${order.id}/details`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                
                if (detailsResponse.ok) {
                    const detailsResult = await detailsResponse.json();
                    const details = detailsResult.data;
                    
                    // 自分の商品が含まれているかチェック
                    const hasMyProduct = details.some(detail => 
                        detail.product && detail.product.seller_id === user.id
                    );
                    
                    if (hasMyProduct) {
                        myOrders.push(order);
                    }
                }
            } catch (error) {
                console.error('注文詳細の取得エラー:', error);
            }
        }
        
        return myOrders;
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
                '完成': 'success',
                '受取済': 'info',
                'キャンセル': 'danger'
            }[order.status] || 'secondary';
            
            return `
                <tr>
                    <td>#${order.id}</td>
                    <td>${order.user ? order.user.name_2nd + ' ' + order.user.name_1st : '-'}</td>
                    <td>¥${order.total_price.toLocaleString()}</td>
                    <td><span class="badge bg-${statusClass}">${order.status}</span></td>
                    <td>${new Date(order.created_at).toLocaleString('ja-JP')}</td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="viewOrderDetails(${order.id})">
                            <i class="fas fa-eye"></i> 詳細
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    async function viewOrderDetails(orderId) {
        try {
            const response = await fetch(`/api/orders/${orderId}/details`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                const details = result.data;
                
                // 自分の商品のみをフィルタリング
                const myDetails = details.filter(detail => 
                    detail.product && detail.product.seller_id === user.id
                );
                
                let content = '<div class="table-responsive"><table class="table">';
                content += '<thead><tr><th>商品名</th><th>単価</th><th>数量</th><th>小計</th></tr></thead><tbody>';
                
                let total = 0;
                myDetails.forEach(detail => {
                    const subtotal = detail.price * detail.quantity;
                    total += subtotal;
                    content += `
                        <tr>
                            <td>${detail.product ? detail.product.name : '削除された商品'}</td>
                            <td>¥${detail.price.toLocaleString()}</td>
                            <td>${detail.quantity}個</td>
                            <td>¥${subtotal.toLocaleString()}</td>
                        </tr>
                    `;
                });
                
                content += '</tbody></table></div>';
                content += `<div class="text-end"><h5>自分の商品合計: ¥${total.toLocaleString()}</h5></div>`;
                
                document.getElementById('orderDetailContent').innerHTML = content;
                new bootstrap.Modal(document.getElementById('orderDetailModal')).show();
            }
        } catch (error) {
            console.error('注文詳細の読み込みエラー:', error);
        }
    }

    function filterOrders() {
        const status = document.getElementById('statusFilter').value;
        const filtered = status ? allOrders.filter(o => o.status === status) : allOrders;
        displayOrders(filtered);
    }

    // ページ読み込み時
    loadOrders();
</script>
@endsection
