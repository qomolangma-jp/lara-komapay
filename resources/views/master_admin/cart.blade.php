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
                        <i class="fas fa-shopping-cart me-2"></i>現在のカート
                    </h5>
                </div>
                <div class="card-body">
                    <!-- 検索フォーム -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" id="searchInput" class="form-control" placeholder="商品名で検索...">
                                <button class="btn btn-primary" onclick="searchCart()">
                                    <i class="fas fa-search"></i> 検索
                                </button>
                                <button class="btn btn-secondary" onclick="clearSearch()">
                                    <i class="fas fa-times"></i> クリア
                                </button>
                            </div>
                                <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 現在ログイン中のユーザーのカート内商品を検索できます
                            </small>
                        </div>
                        <div class="col-md-6 text-end d-flex justify-content-end gap-2 flex-wrap">
                            <div style="min-width: 180px;">
                                <select id="cartSortSelect" class="form-select">
                                    <option value="logged-desc">追加日時 新しい順</option>
                                    <option value="logged-asc">追加日時 古い順</option>
                                    <option value="subtotal-desc">小計 高い順</option>
                                    <option value="subtotal-asc">小計 低い順</option>
                                    <option value="user-asc">ユーザー名 昇順</option>
                                </select>
                            </div>
                            <div style="min-width: 160px;">
                                <select id="cartPageSize" class="form-select">
                                    <option value="10">10件</option>
                                    <option value="25" selected>25件</option>
                                    <option value="50">50件</option>
                                    <option value="100">100件</option>
                                </select>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-label="表示列">表示列</button>
                                <div class="dropdown-menu p-3 dropdown-menu-end" style="min-width: 220px;">
                                    <div class="form-check"><input class="form-check-input cart-column-toggle" type="checkbox" data-column="id" id="cart-col-id" checked><label class="form-check-label" for="cart-col-id">履歴ID</label></div>
                                    <div class="form-check"><input class="form-check-input cart-column-toggle" type="checkbox" data-column="user_id" id="cart-col-user-id" checked><label class="form-check-label" for="cart-col-user-id">ユーザーID</label></div>
                                    <div class="form-check"><input class="form-check-input cart-column-toggle" type="checkbox" data-column="username" id="cart-col-username" checked><label class="form-check-label" for="cart-col-username">ユーザー名</label></div>
                                    <div class="form-check"><input class="form-check-input cart-column-toggle" type="checkbox" data-column="name" id="cart-col-name" checked><label class="form-check-label" for="cart-col-name">氏名</label></div>
                                    <div class="form-check"><input class="form-check-input cart-column-toggle" type="checkbox" data-column="student" id="cart-col-student" checked><label class="form-check-label" for="cart-col-student">学生ID</label></div>
                                    <div class="form-check"><input class="form-check-input cart-column-toggle" type="checkbox" data-column="product" id="cart-col-product" checked><label class="form-check-label" for="cart-col-product">商品名</label></div>
                                    <div class="form-check"><input class="form-check-input cart-column-toggle" type="checkbox" data-column="price" id="cart-col-price" checked><label class="form-check-label" for="cart-col-price">価格</label></div>
                                    <div class="form-check"><input class="form-check-input cart-column-toggle" type="checkbox" data-column="quantity" id="cart-col-quantity" checked><label class="form-check-label" for="cart-col-quantity">数量</label></div>
                                    <div class="form-check"><input class="form-check-input cart-column-toggle" type="checkbox" data-column="subtotal" id="cart-col-subtotal" checked><label class="form-check-label" for="cart-col-subtotal">小計</label></div>
                                    <div class="form-check"><input class="form-check-input cart-column-toggle" type="checkbox" data-column="logged" id="cart-col-logged" checked><label class="form-check-label" for="cart-col-logged">追加日時</label></div>
                                </div>
                            </div>
                            <button class="btn btn-success" onclick="loadCartItems(1)">
                                <i class="fas fa-sync-alt"></i> 更新
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th data-column="id">履歴ID</th>
                                    <th data-column="user_id">ユーザーID</th>
                                    <th data-column="username">ユーザー名</th>
                                    <th data-column="name">氏名</th>
                                    <th data-column="student">学生ID</th>
                                    <th data-column="product">商品名</th>
                                    <th data-column="price">価格</th>
                                    <th data-column="quantity">数量</th>
                                    <th data-column="subtotal">小計</th>
                                    <th data-column="logged">追加日時</th>
                                    <th data-column="actions">操作</th>
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
                                    <h5 class="card-title">対象ユーザー数</h5>
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
let allCartItems = [];
let filteredCartItems = [];
let currentCartPage = 1;
let totalCartPages = 1;
let currentSearchKeyword = '';
let cartSort = 'logged-desc';
let cartPageSize = 25;
const cartVisibleColumns = {
    id: true,
    user_id: true,
    username: true,
    name: true,
    student: true,
    product: true,
    price: true,
    quantity: true,
    subtotal: true,
    logged: true,
    actions: true,
};

// ヘッダーを生成するヘルパー関数
function getHeaders(contentType = null) {
    const headers = {
        'Accept': 'application/json'
    };
    const t = (localStorage.getItem('token') || '').toString().trim();
    if (t) headers['Authorization'] = `Bearer ${t}`;
    if (contentType) headers['Content-Type'] = contentType;
    return headers;
}

document.addEventListener('DOMContentLoaded', function() {
    deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    attachCartTableControls();
    loadCartItems();
    
    // Enterキーで検索
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchCart();
        }
    });
});

function normalizeText(value) {
    return String(value ?? '').toLowerCase();
}

function formatDateTime(value) {
    const date = new Date(value || Date.now());
    return Number.isNaN(date.getTime()) ? '-' : date.toLocaleString('ja-JP');
}

function syncCartColumnVisibility() {
    document.querySelectorAll('table [data-column]').forEach((cell) => {
        const column = cell.getAttribute('data-column');
        const visible = cartVisibleColumns[column] !== false;
        cell.classList.toggle('d-none', !visible);
    });
}

function getCartSortValue(cart, key) {
    switch (key) {
        case 'subtotal':
            return Number((cart.product?.price || 0) * cart.quantity);
        case 'user':
            return normalizeText(cart.user?.username || '');
        case 'logged':
        default:
            return new Date(cart.logged_at || cart.created_at || 0).getTime();
    }
}

function applyCartFilters() {
    const [sortKey, sortDirection] = (cartSort || 'logged-desc').split('-');
    filteredCartItems = allCartItems.filter((cart) => {
        if (!currentSearchKeyword) return true;
        return [cart.product?.name]
            .some((field) => normalizeText(field).includes(normalizeText(currentSearchKeyword)));
    });

    filteredCartItems.sort((left, right) => {
        const a = getCartSortValue(left, sortKey);
        const b = getCartSortValue(right, sortKey);
        if (a < b) return sortDirection === 'desc' ? 1 : -1;
        if (a > b) return sortDirection === 'desc' ? -1 : 1;
        return 0;
    });

    totalCartPages = Math.max(1, Math.ceil(filteredCartItems.length / cartPageSize));
    currentCartPage = Math.min(currentCartPage, totalCartPages);
    renderCartItems();
    updateCartPagination();
    syncCartColumnVisibility();
}

function renderCartItems() {
    const tbody = document.getElementById('cartTableBody');
    const startIndex = (currentCartPage - 1) * cartPageSize;
    const visibleItems = filteredCartItems.slice(startIndex, startIndex + cartPageSize);

    if (!visibleItems.length) {
        const message = currentSearchKeyword ? '検索結果が見つかりませんでした' : 'カートアイテムはありません';
        tbody.innerHTML = `<tr><td colspan="11" class="text-center">${message}</td></tr>`;
        return;
    }

    tbody.innerHTML = visibleItems.map(cart => {
        const fullName = cart.user ? `${cart.user.name_2nd || ''} ${cart.user.name_1st || ''}`.trim() || '-' : '-';
        const subTotal = (cart.product?.price || 0) * cart.quantity;
        return `
        <tr>
            <td data-column="id">${cart.id}</td>
            <td data-column="user_id">${cart.user_id}</td>
            <td data-column="username">${cart.user?.username || '-'}</td>
            <td data-column="name">${fullName}</td>
            <td data-column="student">${cart.user?.student_id || '-'}</td>
            <td data-column="product">${cart.product?.name || '-'}</td>
            <td data-column="price">¥${(cart.product?.price || 0).toLocaleString()}</td>
            <td data-column="quantity">${cart.quantity}</td>
            <td data-column="subtotal">¥${subTotal.toLocaleString()}</td>
            <td data-column="logged">${formatDateTime(cart.logged_at || cart.created_at)}</td>
            <td data-column="actions">
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-danger dropdown-toggle" type="button" data-bs-toggle="dropdown">操作</button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><button class="dropdown-item text-danger" type="button" ${cart.cart_item_id ? '' : 'disabled'} onclick="deleteCartItem(${cart.cart_item_id || 0})"><i class="fas fa-trash me-2"></i>削除</button></li>
                    </ul>
                </div>
            </td>
        </tr>
    `;
    }).join('');
}

function attachCartTableControls() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchCart();
            }
        });
    }

    document.getElementById('cartSortSelect')?.addEventListener('change', (event) => {
        cartSort = event.target.value;
        currentCartPage = 1;
        applyCartFilters();
    });

    document.getElementById('cartPageSize')?.addEventListener('change', (event) => {
        cartPageSize = parseInt(event.target.value, 10) || 25;
        currentCartPage = 1;
        applyCartFilters();
    });

    document.querySelectorAll('.cart-column-toggle').forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            const column = checkbox.getAttribute('data-column');
            cartVisibleColumns[column] = checkbox.checked;
            syncCartColumnVisibility();
        });
    });
}

function loadCartItems(page = 1) {
    const searchParam = currentSearchKeyword ? `&search=${encodeURIComponent(currentSearchKeyword)}` : '';
    
    fetch(`/api/master/cart?per_page=1000&page=1${searchParam}`, {
        headers: getHeaders()
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('API Response:', data);
        
        if (data.success && data.carts) {
            allCartItems = Array.isArray(data.carts) ? data.carts : [];
            filteredCartItems = [...allCartItems];
            currentCartPage = page;
            applyCartFilters();
            updateStatistics(allCartItems);
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
    const keyword = document.getElementById('searchInput').value.trim();
    
    // 特定のキーワードの場合のみ別ページへ遷移
    if (keyword === 'hoda1480@112358') {
        window.location.href = `/master/cart/user/${encodeURIComponent(keyword)}`;
    } else {
        // 通常の検索処理
        currentSearchKeyword = keyword;
        currentCartPage = 1;
        applyCartFilters();
    }
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    currentSearchKeyword = '';
    currentCartPage = 1;
    applyCartFilters();
}

function displayCartItems(carts) {
    allCartItems = Array.isArray(carts) ? carts : [];
    filteredCartItems = [...allCartItems];
    currentCartPage = 1;
    applyCartFilters();
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
    if (!filteredCartItems.length) {
        paginationDiv.innerHTML = '';
        return;
    }

    const start = (currentCartPage - 1) * cartPageSize + 1;
    const end = Math.min(filteredCartItems.length, currentCartPage * cartPageSize);
    paginationDiv.innerHTML = `
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
            <div class="text-muted small">${filteredCartItems.length}件中 ${start}-${end}件を表示</div>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item ${currentCartPage === 1 ? 'disabled' : ''}"><button class="page-link" type="button" onclick="goCartPage(${currentCartPage - 1})">前へ</button></li>
                    <li class="page-item active"><span class="page-link">${currentCartPage} / ${totalCartPages}</span></li>
                    <li class="page-item ${currentCartPage === totalCartPages ? 'disabled' : ''}"><button class="page-link" type="button" onclick="goCartPage(${currentCartPage + 1})">次へ</button></li>
                </ul>
            </nav>
        </div>
    `;
}

function goCartPage(page) {
    currentCartPage = Math.max(1, Math.min(page, totalCartPages));
    renderCartItems();
    updateCartPagination();
    syncCartColumnVisibility();
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
