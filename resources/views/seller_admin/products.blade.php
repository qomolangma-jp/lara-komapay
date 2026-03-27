@extends('layouts.seller_layout')

@section('title', '商品管理')

@section('content')
<style>
    .product-image-small { width: 80px; height: 60px; object-fit: cover; border-radius: 5px; }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">商品管理</h1>
</div>

<div id="alert-area"></div>

<div class="mb-3">
    <div class="btn-group" role="group" aria-label="画面切り替え">
        <button type="button" class="btn btn-success" id="view-list-btn" onclick="switchToListView()">
            <i class="fas fa-list me-1"></i>一覧画面
        </button>
        <button type="button" class="btn btn-outline-success" id="view-form-btn" onclick="switchToFormView(false)">
            <i class="fas fa-plus me-1"></i>登録・編集画面
        </button>
    </div>
</div>

<div id="list-screen">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>自分の商品一覧
            </h5>
            <button type="button" class="btn btn-success btn-sm" onclick="switchToFormView(false)">
                <i class="fas fa-plus me-1"></i>新規追加
            </button>
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
                            <th>アレルギー</th>
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

<div id="form-screen" class="d-none">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0" id="form-title">
                        <i class="fas fa-plus me-2"></i>商品追加
                    </h5>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="switchToListView()">
                        <i class="fas fa-arrow-left me-1"></i>一覧に戻る
                    </button>
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
                            <label class="form-label">ラベル</label>
                            <select id="label" class="form-select">
                                <option value="">-- ラベルなし --</option>
                                <option value="おすすめ">おすすめ</option>
                                <option value="期間限定">期間限定</option>
                                <option value="新商品">新商品</option>
                                <option value="売れ筋">売れ筋</option>
                                <option value="人気">人気</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">説明</label>
                            <textarea id="description" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">商品画像ファイル</label>
                            <input type="file" id="image_file" class="form-control" accept="image/*">
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> <strong>注意：</strong><br>
                                • URL入力は廃止しました。画像ファイルを選択してください<br>
                                • JPG/PNG/GIF 形式、最大2MBまでアップロードできます
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">アレルギー情報</label>
                            <input type="text" id="allergens" class="form-control" placeholder="例: 小麦, 卵, 乳">
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> カンマ（,）区切りで複数入力できます
                            </small>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success" id="submit-btn">
                                <i class="fas fa-save me-1"></i>登録
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="resetForm(); switchToListView();" id="cancel-btn" style="display:none;">
                                <i class="fas fa-times me-1"></i>キャンセル
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const token = localStorage.getItem('token') || '';
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    const listScreen = document.getElementById('list-screen');
    const formScreen = document.getElementById('form-screen');
    const viewListBtn = document.getElementById('view-list-btn');
    const viewFormBtn = document.getElementById('view-form-btn');

    function setActiveScreen(screen) {
        if (screen === 'form') {
            listScreen.classList.add('d-none');
            formScreen.classList.remove('d-none');
            viewListBtn.classList.remove('btn-success');
            viewListBtn.classList.add('btn-outline-success');
            viewFormBtn.classList.remove('btn-outline-success');
            viewFormBtn.classList.add('btn-success');
        } else {
            formScreen.classList.add('d-none');
            listScreen.classList.remove('d-none');
            viewFormBtn.classList.remove('btn-success');
            viewFormBtn.classList.add('btn-outline-success');
            viewListBtn.classList.remove('btn-outline-success');
            viewListBtn.classList.add('btn-success');
        }
    }

    function switchToListView() {
        setActiveScreen('list');
    }

    function switchToFormView(isEdit) {
        if (!isEdit) {
            resetForm();
        }
        setActiveScreen('form');
    }

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

    // 商品一覧を読み込み（自分の商品のみ）
    async function loadProducts() {
        try {
            const response = await fetch('/api/products', {
                headers: getHeaders()
            });

            if (response.ok) {
                const result = await response.json();
                // 自分の商品のみをフィルタリング
                const myProducts = result.data.filter(p => p.seller_id === user.id);
                displayProducts(myProducts);
                updateCategories(myProducts);
            }
        } catch (error) {
            console.error('商品の読み込みエラー:', error);
        }
    }

    function displayProducts(products) {
        const tbody = document.getElementById('products-list');
        if (!products || products.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">商品がありません</td></tr>';
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
                    <td>
                        ${product.name}
                        ${product.label ? `<span class="badge bg-warning text-dark ms-1">${product.label}</span>` : ''}
                    </td>
                    <td>¥${product.price.toLocaleString()}</td>
                    <td>
                        <span class="badge ${product.stock > 0 ? 'bg-success' : 'bg-danger'}">
                            ${product.stock}個
                        </span>
                    </td>
                    <td><span class="badge bg-secondary">${product.category || '-'}</span></td>
                    <td>
                        ${product.allergens ? 
                            `<small class="text-danger"><i class="fas fa-exclamation-triangle"></i> ${product.allergens}</small>` : 
                            '<small class="text-muted">未入力</small>'
                        }
                    </td>
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

    async function uploadImageFile(file) {
        const formData = new FormData();
        formData.append('image', file);

        const response = await fetch('/api/upload-image', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            },
            body: formData
        });

        const result = await response.json();
        if (!response.ok || !result.success || !result.data || !result.data.url) {
            throw new Error(result.message || '画像アップロードに失敗しました');
        }

        return result.data.url;
    }

    // 商品登録・編集
    document.getElementById('productForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const id = document.getElementById('product_id').value;
        const imageFile = document.getElementById('image_file').files[0] || null;
        const data = {
            name: document.getElementById('name').value,
            price: parseInt(document.getElementById('price').value),
            stock: parseInt(document.getElementById('stock').value) || 0,
            category: document.getElementById('category').value || 'その他',
            seller_id: user.id, // 自分のIDを設定
            label: document.getElementById('label').value || null,
            description: document.getElementById('description').value || null,
            allergens: document.getElementById('allergens').value || null
        };

        try {
            if (imageFile) {
                data.image_url = await uploadImageFile(imageFile);
            }

            const url = id ? `/api/products/${id}` : '/api/products';
            const method = id ? 'PUT' : 'POST';
            
            const response = await fetch(url, {
                method: method,
                headers: getHeaders('application/json'),
                body: JSON.stringify(data)
            });

            const result = await response.json();
            
            if (response.ok) {
                showAlert('success', id ? '商品を更新しました' : '商品を登録しました');
                resetForm();
                loadProducts();
                switchToListView();
            } else {
                showAlert('danger', result.message || '処理に失敗しました');
            }
        } catch (error) {
            showAlert('danger', 'エラーが発生しました: ' + error.message);
        }
    });

    function editProduct(product) {
        // 自分の商品以外は編集不可
        if (product.seller_id !== user.id) {
            showAlert('danger', 'この商品は編集できません');
            return;
        }

        document.getElementById('product_id').value = product.id;
        document.getElementById('name').value = product.name;
        document.getElementById('price').value = product.price;
        document.getElementById('stock').value = product.stock;
        document.getElementById('category').value = product.category;
        document.getElementById('label').value = product.label || '';
        document.getElementById('description').value = product.description || '';
        document.getElementById('image_file').value = '';
        document.getElementById('allergens').value = product.allergens || '';
        
        document.getElementById('form-title').innerHTML = '<i class="fas fa-edit me-2"></i>商品編集';
        document.getElementById('submit-btn').innerHTML = '<i class="fas fa-save me-1"></i>更新';
        document.getElementById('cancel-btn').style.display = 'block';
        switchToFormView(true);
    }

    async function deleteProduct(id, name) {
        if (!confirm(`「${name}」を削除してもよろしいですか？`)) return;
        
        try {
            const response = await fetch(`/api/products/${id}`, {
                method: 'DELETE',
                headers: getHeaders()
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
        document.getElementById('image_file').value = '';
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
    switchToListView();
    loadProducts();
</script>
@endsection
