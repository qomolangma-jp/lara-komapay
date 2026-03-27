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
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>商品一覧
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
                                    <th>販売者</th>
                                    <th>アレルギー</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody id="products-list">
                                <tr><td colspan="8" class="text-center">読み込み中...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
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
                            <label class="form-label">販売者</label>
                            <select id="seller_id" class="form-select">
                                <option value="">-- 販売者を選択 --</option>
                            </select>
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
                            <div id="current-image-wrapper" class="mb-2 d-none">
                                <small class="text-muted d-block mb-1">登録済み画像</small>
                                <img id="current-image-preview" src="" alt="登録済み画像" class="img-thumbnail" style="max-width: 220px;">
                                <div class="mt-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm" id="remove-current-image-btn" onclick="removeCurrentImage()">
                                        <i class="fas fa-trash me-1"></i>画像を削除
                                    </button>
                                </div>
                            </div>
                            <input type="file" id="image_file" class="form-control" accept="image/*">
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> <strong>注意：</strong><br>
                                • 画像は送信時に <strong>縦3:横4（横:縦 = 4:3）</strong> に自動加工されます<br>
                                • 登録済み画像がある場合は、先に「画像を削除」してから新しい画像を登録してください<br>
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
                            <button type="submit" class="btn btn-primary" id="submit-btn">
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

<!-- 商品詳細モーダル -->
<div class="modal fade" id="productDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">商品詳細</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="productDetailContent">
                読み込み中...
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
    const token = localStorage.getItem('token') || '';
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    let editingImageUrl = '';
    let shouldRemoveCurrentImage = false;
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
            const response = await fetch('/api/master/users', {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                const selectElement = document.getElementById('seller_id');
                selectElement.innerHTML = '<option value="">-- 販売者を選択 --</option>';
                
                // status='seller'のみをフィルタリング
                const sellers = result.data.filter(user => user.status === 'seller');
                
                sellers.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = user.shop_name || `${user.name_2nd} ${user.name_1st}`;
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
            const response = await fetch('/api/master/products', {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                console.log('商品データ:', result.data); // デバッグ用
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
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">商品がありません</td></tr>';
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
                    <td>${product.seller ? (product.seller.shop_name || (product.seller.name_2nd + ' ' + product.seller.name_1st)) : '-'}</td>
                    <td>
                        ${product.allergens ? 
                            `<small class="text-danger"><i class="fas fa-exclamation-triangle"></i> ${product.allergens}</small>` : 
                            '<small class="text-muted">未入力</small>'
                        }
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-info" onclick='showProductDetail(${JSON.stringify(product)})'>
                                <i class="fas fa-eye"></i>
                            </button>
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

    const TARGET_RATIO = 4 / 3; // 横:縦（縦3:横4）
    const TARGET_WIDTH = 1200;
    const TARGET_HEIGHT = 900;

    async function loadImageElement(source) {
        return new Promise((resolve, reject) => {
            const image = new Image();
            if (typeof source === 'string') {
                image.crossOrigin = 'anonymous';
            }
            image.onload = () => resolve(image);
            image.onerror = () => reject(new Error('画像の読み込みに失敗しました'));
            image.src = source;
        });
    }

    async function convertImageTo43Blob(imageSource) {
        const image = await loadImageElement(imageSource);
        const sourceWidth = image.naturalWidth;
        const sourceHeight = image.naturalHeight;
        const sourceRatio = sourceWidth / sourceHeight;

        let cropWidth = sourceWidth;
        let cropHeight = sourceHeight;
        let cropX = 0;
        let cropY = 0;

        if (sourceRatio > TARGET_RATIO) {
            cropWidth = Math.floor(sourceHeight * TARGET_RATIO);
            cropX = Math.floor((sourceWidth - cropWidth) / 2);
        } else if (sourceRatio < TARGET_RATIO) {
            cropHeight = Math.floor(sourceWidth / TARGET_RATIO);
            cropY = Math.floor((sourceHeight - cropHeight) / 2);
        }

        const canvas = document.createElement('canvas');
        canvas.width = TARGET_WIDTH;
        canvas.height = TARGET_HEIGHT;
        const context = canvas.getContext('2d');
        context.drawImage(image, cropX, cropY, cropWidth, cropHeight, 0, 0, TARGET_WIDTH, TARGET_HEIGHT);

        return await new Promise((resolve, reject) => {
            canvas.toBlob((blob) => {
                if (blob) {
                    resolve(blob);
                } else {
                    reject(new Error('画像変換に失敗しました'));
                }
            }, 'image/jpeg', 0.9);
        });
    }

    async function uploadProcessedImage(blob) {
        const formData = new FormData();
        formData.append('image', blob, `product_${Date.now()}.jpg`);

        const response = await fetch('/api/master/upload-image', {
            method: 'POST',
            headers: {
                'Accept': 'application/json'
            },
            body: formData
        });

        let result;
        try {
            result = await response.json();
        } catch (error) {
            result = { message: '画像アップロードに失敗しました' };
        }

        if (!response.ok || !result.success || !result.data?.url) {
            throw new Error(result.message || '画像アップロードに失敗しました');
        }

        return result.data.url;
    }

    function updateCurrentImagePreview(imageUrl) {
        const previewWrapper = document.getElementById('current-image-wrapper');
        const previewImage = document.getElementById('current-image-preview');
        const removeBtn = document.getElementById('remove-current-image-btn');

        if (!previewWrapper || !previewImage || !removeBtn) {
            return;
        }

        if (imageUrl) {
            previewWrapper.classList.remove('d-none');
            previewImage.src = imageUrl;
            removeBtn.disabled = false;
        } else {
            previewWrapper.classList.add('d-none');
            previewImage.src = '';
            removeBtn.disabled = true;
        }
    }

    function removeCurrentImage() {
        if (!editingImageUrl) {
            return;
        }
        shouldRemoveCurrentImage = true;
        editingImageUrl = '';
        updateCurrentImagePreview('');
        showAlert('info', '現在の画像を削除対象にしました。更新すると画像が削除されます。');
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
            seller_id: document.getElementById('seller_id').value || null,
            label: document.getElementById('label').value || null,
            description: document.getElementById('description').value || null,
            allergens: document.getElementById('allergens').value || null
        };

        try {
            if (id && editingImageUrl && imageFile && !shouldRemoveCurrentImage) {
                showAlert('warning', '画像を変更する場合は、先に登録済み画像を削除してください。');
                return;
            }

            if (id && shouldRemoveCurrentImage) {
                data.image_url = '';
            }

            if (imageFile) {
                const fileObjectUrl = URL.createObjectURL(imageFile);
                try {
                    const processedBlob = await convertImageTo43Blob(fileObjectUrl);
                    data.image_url = await uploadProcessedImage(processedBlob);
                } finally {
                    URL.revokeObjectURL(fileObjectUrl);
                }
            }

            const url = id ? `/api/master/products/${id}` : '/api/master/products';
            const method = id ? 'PUT' : 'POST';
            
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            let result;
            try {
                result = await response.json();
            } catch (e) {
                result = { message: `サーバーエラー (${response.status})` };
            }
            
            if (response.ok) {
                showAlert('success', id ? '商品を更新しました' : '商品を登録しました');
                resetForm();
                loadProducts();
                switchToListView();
            } else {
                console.error('更新失敗:', response.status, result);
                
                // バリデーションエラーの詳細を表示
                let errorMessage = result.message || `処理に失敗しました (${response.status})`;
                if (result.errors) {
                    const errorDetails = Object.entries(result.errors)
                        .map(([field, messages]) => `${field}: ${messages.join(', ')}`)
                        .join('<br>');
                    errorMessage += '<br><br>' + errorDetails;
                }
                
                showAlert('danger', errorMessage);
            }
        } catch (error) {
            console.error('エラー:', error);
            showAlert('danger', 'エラーが発生しました: ' + error.message);
        }
    });

    function showProductDetail(product) {
        const seller = product.seller ? 
            (product.seller.shop_name || `${product.seller.name_2nd} ${product.seller.name_1st}`) : 
            '未設定';
        
        const content = `
            <div class="row">
                <div class="col-md-4">
                    ${product.image_url ? 
                        `<img src="${product.image_url}" class="img-fluid rounded" alt="${product.name}">` : 
                        '<div class="bg-secondary text-white d-flex align-items-center justify-content-center" style="height: 200px; border-radius: 5px;">画像なし</div>'
                    }
                </div>
                <div class="col-md-8">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 120px;">商品名</th>
                            <td>
                                ${product.name}
                                ${product.label ? `<span class="badge bg-warning text-dark ms-2">${product.label}</span>` : ''}
                            </td>
                        </tr>
                        <tr>
                            <th>価格</th>
                            <td>¥${product.price.toLocaleString()}</td>
                        </tr>
                        <tr>
                            <th>在庫</th>
                            <td><span class="badge ${product.stock > 0 ? 'bg-success' : 'bg-danger'}">${product.stock}個</span></td>
                        </tr>
                        <tr>
                            <th>カテゴリ</th>
                            <td><span class="badge bg-secondary">${product.category || '-'}</span></td>
                        </tr>
                        <tr>
                            <th>販売者</th>
                            <td><strong>${seller}</strong></td>
                        </tr>
                        <tr>
                            <th>説明</th>
                            <td>${product.description || '-'}</td>
                        </tr>
                    </table>
                </div>
            </div>
        `;
        
        document.getElementById('productDetailContent').innerHTML = content;
        new bootstrap.Modal(document.getElementById('productDetailModal')).show();
    }

    function editProduct(product) {
        document.getElementById('product_id').value = product.id;
        document.getElementById('name').value = product.name;
        document.getElementById('price').value = product.price;
        document.getElementById('stock').value = product.stock;
        document.getElementById('category').value = product.category;
        document.getElementById('seller_id').value = product.seller_id || '';
        document.getElementById('label').value = product.label || '';
        document.getElementById('description').value = product.description || '';
        document.getElementById('image_file').value = '';
        document.getElementById('allergens').value = product.allergens || '';
        editingImageUrl = product.image_original_url || product.image_url || '';
        shouldRemoveCurrentImage = false;
        updateCurrentImagePreview(editingImageUrl);
        
        document.getElementById('form-title').innerHTML = '<i class="fas fa-edit me-2"></i>商品編集';
        document.getElementById('submit-btn').innerHTML = '<i class="fas fa-save me-1"></i>更新';
        document.getElementById('cancel-btn').style.display = 'block';
        switchToFormView(true);
    }

    async function deleteProduct(id, name) {
        if (!confirm(`「${name}」を削除してもよろしいですか？`)) return;
        
        try {
            const response = await fetch(`/api/master/products/${id}`, {
                method: 'DELETE',
                headers: {
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
        document.getElementById('image_file').value = '';
        editingImageUrl = '';
        shouldRemoveCurrentImage = false;
        updateCurrentImagePreview('');
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
    loadUsers();
    loadProducts();
</script>
@endsection
