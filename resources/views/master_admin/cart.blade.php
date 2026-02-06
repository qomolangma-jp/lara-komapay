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
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>カートID</th>
                                    <th>ユーザーID</th>
                                    <th>ユーザー名</th>
                                    <th>商品ID</th>
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
                                    <td colspan="10" class="text-center">読み込み中...</td>
                                </tr>
                            </tbody>
                        </table>
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

document.addEventListener('DOMContentLoaded', function() {
    deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    loadCartItems();
});

function loadCartItems() {
    const token = localStorage.getItem('token');
    fetch('/api/master/cart', {
        headers: {
            'Accept': 'application/json',
            'Authorization': 'Bearer ' + token
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayCartItems(data.carts);
            updateStatistics(data.carts);
        } else {
            alert('カート情報の読み込みに失敗しました');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('cartTableBody').innerHTML = 
            '<tr><td colspan="10" class="text-center text-danger">エラーが発生しました</td></tr>';
    });
}

function displayCartItems(carts) {
    const tbody = document.getElementById('cartTableBody');
    
    if (!carts || carts.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center">カートアイテムはありません</td></tr>';
        return;
    }

    tbody.innerHTML = carts.map(cart => `
        <tr>
            <td>${cart.id}</td>
            <td>${cart.user_id}</td>
            <td>${cart.user?.username || '-'}</td>
            <td>${cart.product_id}</td>
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
    `).join('');
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

function deleteCartItem(cartId) {
    deleteCartId = cartId;
    deleteModal.show();
}

function confirmDelete() {
    if (!deleteCartId) return;

    const token = localStorage.getItem('token');
    fetch(`/api/master/cart/${deleteCartId}`, {
        method: 'DELETE',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + token
        }
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
