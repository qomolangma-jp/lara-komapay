@extends('layouts.master_layout')

@section('title', 'カート管理')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2>カート管理</h2>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-shopping-cart me-2"></i>カート履歴
                    </h5>
                </div>
                <div class="card-body">
                    <!-- 検索フォーム -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" id="searchInput" class="form-control" placeholder="ユーザー名、氏名、学生IDで検索...">
                                <button class="btn btn-primary" onclick="searchCart()">
                                    <i class="fas fa-search"></i> 検索
                                </button>
                                <button class="btn btn-secondary" onclick="clearSearch()">
                                    <i class="fas fa-times"></i> クリア
                                </button>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> ユーザー名、姓名、学生IDで検索できます
                            </small>
                        </div>
                        <div class="col-md-6 text-end">
                            <button class="btn btn-success" onclick="loadCartItems(1)">
                                <i class="fas fa-sync-alt"></i> 更新
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>カートID</th>
                                    <th>ユーザーID</th>
                                    <th>ユーザー名</th>
                                    <th>氏名</th>
                                    <th>学生ID</th>
                                    <th>商品名</th>
                                    <th>価格</th>
                                    <th>数量</th>
                                    <th>小計</th>
                                    <th>追加日時</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody id="cartTableBody">
                                <tr>
                                    <td colspan="11" class="text-center">読み込み中...</td>
                                </tr>
                            </tbody>
                        </table>
                        <div id="cart-pagination"></div>
                    </div>

                    <!-- 統計情報 -->
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">総カートアイテム数</h5>
                                    <p class="card-text fs-3" id="totalItems">0</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">カート利用ユーザー数</h5>
                                    <p class="card-text fs-3" id="totalUsers">0</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">カート総額</h5>
                                    <p class="card-text fs-3" id="totalAmount">¥0</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 削除確認モーダル -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">カートアイテム削除確認</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                このカートアイテムを削除してもよろしいですか？
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-danger" onclick="confirmDelete()">削除</button>
            </div>
        </div>
    </div>
</div>

<script>
let deleteCartId = null;
let deleteModal = null;
const token = localStorage.getItem('token') || '';

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

document.addEventListener('DOMContentLoaded', function() {
    deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    loadCartItems();
    
    // Enterキーで検索
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchCart();
        }
    });
});

let currentCartPage = 1;
let totalCartPages = 1;
let currentSearchKeyword = '';

function loadCartItems(page = 1) {
    const searchParam = currentSearchKeyword ? `&search=${encodeURIComponent(currentSearchKeyword)}` : '';
    
    fetch(`/api/master/cart?per_page=100&page=${page}${searchParam}`, {
        headers: getHeaders()
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('API Response:', data);
        
        if (data.success && data.carts) {
            // ページネーション情報がある場合
            if (data.pagination) {
                currentCartPage = data.pagination.current_page;
                totalCartPages = data.pagination.last_page;
                updateCartPagination();
            }
            displayCartItems(data.carts);
            updateStatistics(data.carts);
        } else {
            console.error('API Error:', data);
            alert('カート情報の読み込みに失敗しました: ' + (data.message || '不明なエラー'));
        }
    })
    .catch(error => {
        console.error('Fetch Error:', error);
        alert('通信エラー: ' + error.message);
        document.getElementById('cartTableBody').innerHTML = 
            '<tr><td colspan="11" class="text-center text-danger">エラーが発生しました: ' + error.message + '</td></tr>';
    });
}

function searchCart() {
    currentSearchKeyword = document.getElementById('searchInput').value.trim();
    loadCartItems(1);
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    currentSearchKeyword = '';
    loadCartItems(1);
}

function displayCartItems(carts) {
    console.log('displayCartItems called with:', carts);
    const tbody = document.getElementById('cartTableBody');
    
    if (!carts || carts.length === 0) {
        console.log('No carts to display');
        const message = currentSearchKeyword ? '検索結果が見つかりませんでした' : 'カートアイテムはありません';
        tbody.innerHTML = `<tr><td colspan="11" class="text-center">${message}</td></tr>`;
        return;
    }

    console.log('Displaying', carts.length, 'cart items');
    tbody.innerHTML = carts.map(cart => {
        const fullName = cart.user ? `${cart.user.name_2nd || ''} ${cart.user.name_1st || ''}`.trim() || '-' : '-';
        return `
        <tr>
            <td>${cart.id}</td>
            <td>${cart.user_id}</td>
            <td>${cart.user?.username || '-'}</td>
            <td>${fullName}</td>
            <td>${cart.user?.student_id || '-'}</td>
            <td>${cart.product?.name || '-'}</td>
            <td>¥${(cart.product?.price || 0).toLocaleString()}</td>
            <td>${cart.quantity}</td>
            <td>¥${((cart.product?.price || 0) * cart.quantity).toLocaleString()}</td>
            <td>${new Date(cart.created_at).toLocaleString('ja-JP')}</td>
            <td>
                <button class="btn btn-sm btn-danger" onclick="deleteCartItem(${cart.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
    }).join('');
}

function updateStatistics(carts) {
    const totalItems = carts.length;
    const uniqueUsers = new Set(carts.map(c => c.user_id)).size;
    const totalAmount = carts.reduce((sum, cart) => {
        return sum + ((cart.product?.price || 0) * cart.quantity);
    }, 0);

    document.getElementById('totalItems').textContent = totalItems;
    document.getElementById('totalUsers').textContent = uniqueUsers;
    document.getElementById('totalAmount').textContent = '¥' + totalAmount.toLocaleString();
}

function updateCartPagination() {
    const paginationDiv = document.getElementById('cart-pagination');
    if (!paginationDiv) return;
    
    let html = `<div class="d-flex justify-content-between align-items-center mt-3">`;
    html += `<div>ページ ${currentCartPage} / ${totalCartPages}</div>`;
    html += `<div class="btn-group">`;
    
    if (currentCartPage > 1) {
        html += `<button class="btn btn-sm btn-outline-success" onclick="loadCartItems(${currentCartPage - 1})">前へ</button>`;
    }
    if (currentCartPage < totalCartPages) {
        html += `<button class="btn btn-sm btn-outline-success" onclick="loadCartItems(${currentCartPage + 1})">次へ</button>`;
    }
    
    html += `</div></div>`;
    paginationDiv.innerHTML = html;
}

function deleteCartItem(cartId) {
    deleteCartId = cartId;
    deleteModal.show();
}

function confirmDelete() {
    if (!deleteCartId) return;

    fetch(`/api/master/cart/${deleteCartId}`, {
        method: 'DELETE',
        headers: getHeaders('application/json')
    })
    .then(response => response.json())
    .then(data => {
        deleteModal.hide();
        if (data.success) {
            alert('カートアイテムを削除しました');
            loadCartItems();
        } else {
            alert('削除に失敗しました: ' + (data.message || '不明なエラー'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        deleteModal.hide();
        alert('削除中にエラーが発生しました');
    })
    .finally(() => {
        deleteCartId = null;
    });
}
</script>
@endsection
