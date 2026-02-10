@extends('layouts.master_layout')

@section('title', '商品管理')

@section('content')
<style>
    .product-image-small { width: 80px; height: 60px; object-fit: cover; border-radius: 5px; }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">商品管理</h1>
</div>

<div id="alert-area"></div>

<div class="row">
    <!-- 商品追加・編集フォーム -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0" id="form-title">
                    <i class="fas fa-plus me-2"></i>商品追加
                </h5>
            </div>
            <div class="card-body">
                <form id="productForm">
                    <input type="hidden" id="product_id" name="product_id">
                    
                    <div class="mb-3">
                        <label class="form-label">商品名 <span class="text-danger">*</span></label>
                        <input type="text" id="name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">価格 <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">¥</span>
                            <input type="number" id="price" class="form-control" min="1" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">在庫数</label>
                        <input type="number" id="stock" class="form-control" min="0" value="0">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">カテゴリ</label>
                        <input type="text" id="category" class="form-control" list="categories">
                        <datalist id="categories"></datalist>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">販売者</label>
                        <select id="seller_id" class="form-select">
                            <option value="">-- 販売者を選択 --</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">説明</label>
                        <textarea id="description" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">画像URL</label>
                        <input type="url" id="image_url" class="form-control" placeholder="https://example.com/image.jpg">
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="fas fa-save me-1"></i>登録
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="resetForm()" id="cancel-btn" style="display:none;">
                            <i class="fas fa-times me-1"></i>キャンセル
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 商品一覧 -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>商品一覧
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>画像</th>
                                <th>商品名</th>
                                <th>価格</th>
                                <th>在庫</th>
                                <th>カテゴリ</th>
                                <th>販売者</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="products-list">
                            <tr><td colspan="7" class="text-center">読み込み中...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const token = localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');

    // 管理者権限確認
    if (!token || !user.is_admin) {
        window.location.href = '/login';
    }

    // ユーザー一覧を読み込み
    async function loadUsers() {
        try {
            await loadUsersForSelect();
        } catch (error) {
            console.error('ユーザーの読み込みエラー:', error);
        }
    }

    // セレクトボックスにユーザーを読み込む
    async function loadUsersForSelect() {
        try {
            const response = await fetch('/api/auth/users', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                const selectElement = document.getElementById('seller_id');
                selectElement.innerHTML = '<option value="">-- 販売者を選択 --</option>';
                
                result.data.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = `${user.name_2nd} ${user.name_1st}`;
                    selectElement.appendChild(option);
                });
            }
        } catch (error) {
            console.error('ユーザーの読み込みエラー:', error);
        }
    }

    // 商品一覧を読み込み
    async function loadProducts() {
        try {
            const response = await fetch('/api/products', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                displayProducts(result.data);
                updateCategories(result.data);
            }
        } catch (error) {
            console.error('商品の読み込みエラー:', error);
        }
    }

    function displayProducts(products) {
        const tbody = document.getElementById('products-list');
        if (!products || products.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">商品がありません</td></tr>';
            return;
        }
        
        tbody.innerHTML = products.map(product => {
            return `
                <tr>
                    <td>
                        ${product.image_url ? 
                            `<img src="${product.image_url}" class="product-image-small" alt="${product.name}">` : 
                            '<div class="product-image-small bg-secondary d-flex align-items-center justify-content-center text-white">画像なし</div>'
                        }
                    </td>
                    <td>${product.name}</td>
                    <td>¥${product.price.toLocaleString()}</td>
                    <td>
                        <span class="badge ${product.stock > 0 ? 'bg-success' : 'bg-danger'}">
                            ${product.stock}個
                        </span>
                    </td>
                    <td><span class="badge bg-secondary">${product.category || '-'}</span></td>
                    <td>${product.seller ? product.seller.name_2nd + ' ' + product.seller.name_1st : '-'}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-warning" onclick='editProduct(${JSON.stringify(product)})'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger" onclick="deleteProduct(${product.id}, '${product.name}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function updateCategories(products) {
        const categories = [...new Set(products.map(p => p.category))].sort();
        document.getElementById('categories').innerHTML = categories.map(c => 
            `<option value="${c}">`
        ).join('');
    }

    // 商品登録・編集
    document.getElementById('productForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const id = document.getElementById('product_id').value;
        const data = {
            name: document.getElementById('name').value,
            price: parseInt(document.getElementById('price').value),
            stock: parseInt(document.getElementById('stock').value) || 0,
            category: document.getElementById('category').value || 'その他',
            seller_id: document.getElementById('seller_id').value || null,
            description: document.getElementById('description').value || null,
            image_url: document.getElementById('image_url').value || null
        };

        try {
            const url = id ? `/api/products/${id}` : '/api/products';
            const method = id ? 'PUT' : 'POST';
            
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            
            if (response.ok) {
                showAlert('success', id ? '商品を更新しました' : '商品を登録しました');
                resetForm();
                loadProducts();
            } else {
                showAlert('danger', result.message || '処理に失敗しました');
            }
        } catch (error) {
            showAlert('danger', 'エラーが発生しました: ' + error.message);
        }
    });

    function editProduct(product) {
        document.getElementById('product_id').value = product.id;
        document.getElementById('name').value = product.name;
        document.getElementById('price').value = product.price;
        document.getElementById('stock').value = product.stock;
        document.getElementById('category').value = product.category;
        document.getElementById('seller_id').value = product.seller_id || '';
        document.getElementById('description').value = product.description || '';
        document.getElementById('image_url').value = product.image_url || '';
        
        document.getElementById('form-title').innerHTML = '<i class="fas fa-edit me-2"></i>商品編集';
        document.getElementById('submit-btn').innerHTML = '<i class="fas fa-save me-1"></i>更新';
        document.getElementById('cancel-btn').style.display = 'block';
    }

    async function deleteProduct(id, name) {
        if (!confirm(`「${name}」を削除してもよろしいですか？`)) return;
        
        try {
            const response = await fetch(`/api/products/${id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                showAlert('success', '商品を削除しました');
                loadProducts();
            } else {
                showAlert('danger', '削除に失敗しました');
            }
        } catch (error) {
            showAlert('danger', 'エラーが発生しました');
        }
    }

    function resetForm() {
        document.getElementById('productForm').reset();
        document.getElementById('product_id').value = '';
        document.getElementById('form-title').innerHTML = '<i class="fas fa-plus me-2"></i>商品追加';
        document.getElementById('submit-btn').innerHTML = '<i class="fas fa-save me-1"></i>登録';
        document.getElementById('cancel-btn').style.display = 'none';
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

    // ページ読み込み時
    loadUsers();
    loadProducts();
</script>
@endsection
