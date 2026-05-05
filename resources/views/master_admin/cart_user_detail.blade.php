@extends('layouts.master_layout')

@section('title', 'ユーザーカート詳細')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2>ユーザーカート詳細</h2>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div id="userCartContainer">
                <p class="text-muted">読み込み中...</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const username = urlParams.get('username');
    
    if (username) {
        fetch(`/api/master/cart/user/${encodeURIComponent(username)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    displayUserCart(data.data);
                } else {
                    showError(data.message || 'エラーが発生しました');
                }
            })
            .catch(err => {
                console.error(err);
                showError('ユーザーカート情報の取得に失敗しました');
            });
    }
});

function displayUserCart(data) {
    const container = document.getElementById('userCartContainer');
    
    let html = `
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">ユーザー情報</h5>
            </div>
            <div class="card-body">
                <p><strong>ユーザーID:</strong> ${escapeHtml(data.username)}</p>
                <p><strong>氏名:</strong> ${escapeHtml(data.name)}</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">カートアイテム</h5>
            </div>
            <div class="card-body">
    `;
    
    if (data.items.length === 0) {
        html += '<p class="text-muted">カートは空です</p>';
    } else {
        html += `
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>商品名</th>
                        <th>価格</th>
                        <th>数量</th>
                        <th>小計</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        data.items.forEach(item => {
            const product = item.product || {};
            const price = product.price || 0;
            const subtotal = price * item.quantity;
            
            html += `
                <tr>
                    <td>${escapeHtml(product.name || '不明')}</td>
                    <td>¥${price.toLocaleString()}</td>
                    <td>${item.quantity}</td>
                    <td>¥${subtotal.toLocaleString()}</td>
                </tr>
            `;
        });
        
        html += `
                </tbody>
                <tfoot>
                    <tr class="table-active">
                        <th colspan="3">合計</th>
                        <th>¥${(data.total || 0).toLocaleString()}</th>
                    </tr>
                </tfoot>
            </table>
        `;
    }
    
    html += `
            </div>
        </div>
    `;
    
    container.innerHTML = html;
}

function showError(message) {
    const container = document.getElementById('userCartContainer');
    container.innerHTML = `<div class="alert alert-danger">${escapeHtml(message)}</div>`;
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}
</script>
@endsection
