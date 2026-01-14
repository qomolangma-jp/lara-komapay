<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>マスター管理 - 学校食堂注文システム</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .product-image-small { width: 80px; height: 60px; object-fit: cover; border-radius: 5px; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/master">
                <i class="fas fa-utensils me-2"></i>学食システム - マスター管理
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3 text-white" id="username">読み込み中...</span>
                <button class="btn btn-outline-light btn-sm" onclick="logout()">
                    <i class="fas fa-sign-out-alt me-1"></i>ログアウト
                </button>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
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
                                <label class="form-label">説明</label>
                                <textarea id="description" class="form-control" rows="2"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">画像URL</label>
                                <input type="url" id="image_url" class="form-control" 
                                       placeholder="https://example.com/image.jpg">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">または画像をアップロード</label>
                                <input type="file" id="image_file" class="form-control" accept="image/*">
                                <small class="text-muted">JPEG, PNG, GIF (最大2MB)</small>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary" id="submit-btn">
                                    <i class="fas fa-plus me-1"></i>追加
                                </button>
                                <button type="button" class="btn btn-secondary d-none" id="cancel-btn" onclick="cancelEdit()">
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
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>商品一覧
                        </h5>
                        <button class="btn btn-sm btn-outline-primary" onclick="loadProducts()">
                            <i class="fas fa-sync me-1"></i>更新
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>画像</th>
                                        <th>商品名</th>
                                        <th>価格</th>
                                        <th>在庫</th>
                                        <th>カテゴリ</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody id="products-table">
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">読み込み中...</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let token = localStorage.getItem('token');
        let user = JSON.parse(localStorage.getItem('user') || '{}');
        let editMode = false;

        // 管理者権限確認
        if (!token || !user.is_admin) {
            window.location.href = '/login';
        }

        document.getElementById('username').textContent = `${user.username}さん`;

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
                } else if (response.status === 401) {
                    showAlert('ログインセッションが切れました', 'danger');
                    setTimeout(() => window.location.href = '/login', 2000);
                }
            } catch (error) {
                showAlert('商品の読み込みに失敗しました: ' + error.message, 'danger');
            }
        }

        function displayProducts(products) {
            const tbody = document.getElementById('products-table');
            
            if (products.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">商品がありません</td></tr>';
                return;
            }

            tbody.innerHTML = products.map(product => {
                const imageUrl = product.image_url || 'https://via.placeholder.com/80x60?text=No+Image';
                return `
                    <tr>
                        <td><img src="${imageUrl}" class="product-image-small" alt="${product.name}"></td>
                        <td>
                            <strong>${product.name}</strong>
                            ${product.description ? `<br><small class="text-muted">${product.description}</small>` : ''}
                        </td>
                        <td>¥${product.price.toLocaleString()}</td>
                        <td>
                            <span class="badge bg-${product.stock > 0 ? 'success' : 'danger'}">
                                ${product.stock}個
                            </span>
                        </td>
                        <td><span class="badge bg-secondary">${product.category}</span></td>
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
            const datalist = document.getElementById('categories');
            datalist.innerHTML = categories.map(cat => `<option value="${cat}">`).join('');
        }

        // 商品フォーム送信
        document.getElementById('productForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            let imageUrl = document.getElementById('image_url').value;
            const imageFile = document.getElementById('image_file').files[0];
            
            // 画像ファイルがアップロードされている場合
            if (imageFile) {
                const formData = new FormData();
                formData.append('image', imageFile);
                
                try {
                    const uploadResponse = await fetch('/api/upload-image', {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        },
                        body: formData
                    });
                    
                    if (uploadResponse.ok) {
                        const uploadResult = await uploadResponse.json();
                        imageUrl = uploadResult.data.url;
                    } else {
                        showAlert('画像のアップロードに失敗しました', 'danger');
                        return;
                    }
                } catch (error) {
                    showAlert('画像のアップロードエラー: ' + error.message, 'danger');
                    return;
                }
            }
            
            const productData = {
                name: document.getElementById('name').value,
                price: parseInt(document.getElementById('price').value),
                stock: parseInt(document.getElementById('stock').value),
                category: document.getElementById('category').value,
                description: document.getElementById('description').value,
                image_url: imageUrl
            };

            const productId = document.getElementById('product_id').value;
            const method = productId ? 'PUT' : 'POST';
            const url = productId ? `/api/products/${productId}` : '/api/products';

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(productData)
                });

                const result = await response.json();

                if (response.ok) {
                    showAlert(productId ? '商品を更新しました' : '商品を追加しました', 'success');
                    cancelEdit();
                    loadProducts();
                } else {
                    showAlert(result.message || '処理に失敗しました', 'danger');
                }
            } catch (error) {
                showAlert('エラーが発生しました: ' + error.message, 'danger');
            }
        });

        function editProduct(product) {
            editMode = true;
            document.getElementById('form-title').innerHTML = '<i class="fas fa-edit me-2"></i>商品編集';
            document.getElementById('product_id').value = product.id;
            document.getElementById('name').value = product.name;
            document.getElementById('price').value = product.price;
            document.getElementById('stock').value = product.stock;
            document.getElementById('category').value = product.category;
            document.getElementById('description').value = product.description || '';
            document.getElementById('image_url').value = product.image_url || '';
            
            document.getElementById('submit-btn').innerHTML = '<i class="fas fa-save me-1"></i>更新';
            document.getElementById('submit-btn').className = 'btn btn-warning';
            document.getElementById('cancel-btn').classList.remove('d-none');
        }

        function cancelEdit() {
            editMode = false;
            document.getElementById('form-title').innerHTML = '<i class="fas fa-plus me-2"></i>商品追加';
            document.getElementById('productForm').reset();
            document.getElementById('product_id').value = '';
            document.getElementById('image_file').value = '';
            
            document.getElementById('submit-btn').innerHTML = '<i class="fas fa-plus me-1"></i>追加';
            document.getElementById('submit-btn').className = 'btn btn-primary';
            document.getElementById('cancel-btn').classList.add('d-none');
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
                    showAlert(`商品「${name}」を削除しました`, 'warning');
                    loadProducts();
                } else {
                    const result = await response.json();
                    showAlert(result.message || '削除に失敗しました', 'danger');
                }
            } catch (error) {
                showAlert('エラーが発生しました: ' + error.message, 'danger');
            }
        }

        function showAlert(message, type) {
            const alertArea = document.getElementById('alert-area');
            alertArea.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            setTimeout(() => alertArea.innerHTML = '', 5000);
        }

        function logout() {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            window.location.href = '/login';
        }

        // ページ読み込み時
        loadProducts();
    </script>
</body>
</html>
