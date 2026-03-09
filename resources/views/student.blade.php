<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>学生注文画面 - 学校食堂注文システム</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .product-image {
            height: 200px;
            object-fit: cover;
        }
        .order-history {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-light">
    <!-- ナビゲーションバー -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/student">🍽️ 学校食堂</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3" id="username">
                    読み込み中...
                </span>
                <button class="btn btn-outline-light btn-sm" onclick="logout()">ログアウト</button>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- アラート表示エリア -->
        <div id="alert-area"></div>

        <div class="row">
            <!-- メニュー部分 -->
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>🍽️ 今日のメニュー</h2>
                    <div class="btn-group" role="group" id="category-filter">
                        <button type="button" class="btn btn-outline-primary active" onclick="filterCategory('all')">
                            すべて
                        </button>
                    </div>
                </div>

                <div class="row g-4" id="products-container">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">読み込み中...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 注文履歴 -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">📋 注文履歴（最新5件）</h5>
                    </div>
                    <div class="card-body order-history" id="order-history">
                        <div class="text-center">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">読み込み中...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 注文確認モーダル -->
    <div class="modal fade" id="orderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">注文確認</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="orderConfirmText"></p>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">数量</label>
                        <input type="number" class="form-control" id="quantity" min="1" value="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="button" class="btn btn-primary" onclick="confirmOrder()">注文する</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let token = localStorage.getItem('token');
        let user = JSON.parse(localStorage.getItem('user') || '{}');
        let orderModal;
        let selectedProduct = null;

        // ログイン確認
        if (!token || !user.username) {
            window.location.href = '/login';
        }

        // 管理者は管理画面へリダイレクト
        if (user.is_admin) {
            window.location.href = '/master';
        }

        document.getElementById('username').textContent = `こんにちは、${user.username}さん`;

        // モーダル初期化
        orderModal = new bootstrap.Modal(document.getElementById('orderModal'));

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
                    const products = result.data.filter(p => p.stock > 0);
                    displayProducts(products);
                    setupCategoryFilter(products);
                } else if (response.status === 401) {
                    showAlert('ログインセッションが切れました', 'danger');
                    setTimeout(() => window.location.href = '/login', 2000);
                }
            } catch (error) {
                showAlert('商品の読み込みに失敗しました: ' + error.message, 'danger');
            }
        }

        function displayProducts(products) {
            const container = document.getElementById('products-container');
            
            if (products.length === 0) {
                container.innerHTML = '<p class="text-center text-muted">現在注文可能な商品はありません</p>';
                return;
            }

            container.innerHTML = products.map(product => {
                const imageUrl = product.image_url || `https://via.placeholder.com/300x200/E0E0E0/666666?text=${encodeURIComponent(product.name)}`;
                const shopName = product.seller ? (product.seller.shop_name || `${product.seller.name_2nd} ${product.seller.name_1st}`) : '店舗未設定';
                return `
                    <div class="col-md-6 col-lg-4 product-item" data-category="${product.category}">
                        <div class="card product-card h-100 shadow-sm">
                            <img src="${imageUrl}" class="card-img-top product-image" alt="${product.name}">
                            <div class="card-body">
                                <span class="badge bg-secondary mb-2">${product.category}</span>
                                <span class="badge bg-info mb-2 ms-1">🏪 ${shopName}</span>
                                <h5 class="card-title">${product.name}</h5>
                                <p class="card-text text-muted small">${product.description || ''}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="h5 mb-0 text-primary">¥${product.price.toLocaleString()}</span>
                                    <span class="text-muted small">在庫: ${product.stock}個</span>
                                </div>
                                <button class="btn btn-primary w-100 mt-3" onclick='openOrderModal(${JSON.stringify(product)})'>
                                    注文する
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function setupCategoryFilter(products) {
            const categories = [...new Set(products.map(p => p.category))].sort();
            const filterContainer = document.getElementById('category-filter');
            
            const allBtn = filterContainer.querySelector('button');
            categories.forEach(category => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-outline-primary';
                btn.textContent = category;
                btn.onclick = () => filterCategory(category);
                filterContainer.appendChild(btn);
            });
        }

        function filterCategory(category) {
            const items = document.querySelectorAll('.product-item');
            const buttons = document.querySelectorAll('#category-filter button');
            
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            items.forEach(item => {
                if (category === 'all' || item.dataset.category === category) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function openOrderModal(product) {
            selectedProduct = product;
            document.getElementById('orderConfirmText').textContent = 
                `${product.name}（¥${product.price.toLocaleString()}）を注文しますか？`;
            document.getElementById('quantity').value = 1;
            document.getElementById('quantity').max = product.stock;
            orderModal.show();
        }

        async function confirmOrder() {
            const quantity = parseInt(document.getElementById('quantity').value);
            
            if (!selectedProduct || quantity < 1) return;

            try {
                const response = await fetch('/api/orders', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        items: [{
                            product_id: selectedProduct.id,
                            quantity: quantity
                        }]
                    })
                });

                const result = await response.json();

                if (response.ok) {
                    showAlert(`${selectedProduct.name} を ${quantity}個 注文しました！`, 'success');
                    orderModal.hide();
                    loadProducts();
                    loadOrderHistory();
                } else {
                    showAlert(result.message || '注文に失敗しました', 'danger');
                }
            } catch (error) {
                showAlert('エラーが発生しました: ' + error.message, 'danger');
            }
        }

        async function loadOrderHistory() {
            try {
                const response = await fetch('/api/orders/my', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const result = await response.json();
                    const orders = result.data.data.slice(0, 5);
                    displayOrderHistory(orders);
                }
            } catch (error) {
                console.error('注文履歴の読み込みエラー:', error);
            }
        }

        function displayOrderHistory(orders) {
            const container = document.getElementById('order-history');
            
            if (orders.length === 0) {
                container.innerHTML = '<p class="text-muted">注文履歴がありません</p>';
                return;
            }

            container.innerHTML = orders.map(order => `
                <div class="card mb-2">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">${new Date(order.created_at).toLocaleString('ja-JP')}</small>
                            <span class="badge bg-${getStatusColor(order.status)}">${order.status}</span>
                        </div>
                        <div class="mt-1">
                            ${order.details.map(d => `${d.product.name} x${d.quantity}`).join(', ')}
                        </div>
                        <strong class="text-primary">¥${order.total_price.toLocaleString()}</strong>
                    </div>
                </div>
            `).join('');
        }

        function getStatusColor(status) {
            const colors = {
                '調理中': 'warning',
                '完成': 'success',
                '受け取り済み': 'secondary',
                'キャンセル': 'danger'
            };
            return colors[status] || 'info';
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
        loadOrderHistory();
    </script>
</body>
</html>
