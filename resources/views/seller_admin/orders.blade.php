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
                        <th>ステータス</th>
                        <th>金額</th>
                        <th>注文日時</th>
                    </tr>
                </thead>
                <tbody id="orders-list">
                    <tr><td colspan="4" class="text-center">読み込み中...</td></tr>
                </tbody>
            </table>
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
        if (!orders || orders.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">注文がありません</td></tr>';
            return;
        }
        
        tbody.innerHTML = orders.map(order => {
            const statusClass = {
                '調理中': 'warning',
                '完成': 'success',
                '受取済': 'info',
                'キャンセル': 'danger'
            }[order.status] || 'secondary';

            const myTotal = (order.details || [])
                .filter(detail => detail.product && detail.product.seller_id === user.id)
                .reduce((sum, detail) => {
                    const unitPrice = detail.product ? (detail.product.price || 0) : 0;
                    return sum + (unitPrice * (detail.quantity || 0));
                }, 0);
            
            return `
                <tr>
                    <td>#${order.id}</td>
                    <td><span class="badge bg-${statusClass}">${order.status}</span></td>
                    <td>¥${myTotal.toLocaleString()}</td>
                    <td>${new Date(order.created_at).toLocaleString('ja-JP')}</td>
                </tr>
            `;
        }).join('');
    }

    function filterOrders() {
        const status = document.getElementById('statusFilter').value;
        const filtered = status ? allOrders.filter(o => o.status === status) : allOrders;
        displayOrders(filtered);
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
