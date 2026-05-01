@extends('layouts.seller_layout')

@section('title', '商品管理')

@section('content')
<style>
    .product-image-small { width: 80px; height: 60px; object-fit: cover; border-radius: 5px; }
    .product-card { border-radius: 12px; border: 1px solid var(--color-border); box-shadow: var(--shadow-sm); overflow: hidden; background: #fff; }
    .product-card .card-body { padding: 12px; }
    .product-card .product-thumb { width: 92px; height: 72px; object-fit: cover; border-radius: 6px; }
    .product-card-action .btn { padding: 0.45rem 0.75rem; }
    @media (max-width: 768px) {
        .btn { padding: 0.6rem 0.9rem; font-size: 0.98rem; }
    }
    .upload-dropzone {
        border: 2px dashed var(--color-border);
        border-radius: 12px;
        background: var(--color-surface-muted);
        padding: 16px;
        text-align: center;
        cursor: pointer;
        transition: border-color 0.2s ease, background-color 0.2s ease;
    }
    .upload-dropzone:hover,
    .upload-dropzone.is-dragover {
        border-color: var(--color-primary);
        background: #eefaf0;
    }
    .upload-dropzone .upload-title {
        font-weight: 700;
    }
    .upload-dropzone .upload-sub {
        font-size: 0.85rem;
        color: var(--color-text-muted);
    }
    .preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(92px, 1fr));
        gap: 8px;
        margin-top: 10px;
    }
    .preview-item {
        border: 1px solid var(--color-border);
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
    }
    .preview-item img {
        width: 100%;
        height: 76px;
        object-fit: cover;
        display: block;
    }
    .preview-item .meta {
        font-size: 0.72rem;
        padding: 4px 6px;
        color: var(--color-text-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
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
                <div class="d-none d-lg-block">
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

                <!-- Mobile: card list -->
                <div id="products-mobile-list" class="d-lg-none"></div>
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
                            <select id="category" class="form-select">
                                <option value="">選択してください</option>
                                <option value="その他">その他</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">ラベル</label>
                            <input type="text" id="label" class="form-control" maxlength="50" placeholder="例: おすすめ、新商品">
                            <div class="form-text">短い自由入力テキストとして扱います。</div>
                            <div class="invalid-feedback" id="label-error"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">説明</label>
                            <textarea id="description" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">メイン画像ファイル</label>
                            <div id="current-image-wrapper" class="mb-2 d-none">
                                <small class="text-muted d-block mb-1">登録済み画像</small>
                                <img id="current-image-preview" src="" alt="登録済み画像" class="img-thumbnail" style="max-width: 220px;">
                                <div class="mt-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm" id="remove-current-image-btn" onclick="removeCurrentImage()">
                                        <i class="fas fa-trash me-1"></i>画像を削除
                                    </button>
                                </div>
                            </div>
                            <div id="main-dropzone" class="upload-dropzone mb-2" role="button" tabindex="0">
                                <div class="upload-title"><i class="fas fa-cloud-upload-alt me-1"></i>画像をドラッグ＆ドロップ</div>
                                <div class="upload-sub">またはクリックしてファイルを選択（JPG/PNG/GIF, 最大2MB）</div>
                            </div>
                            <input type="file" id="image_file" class="form-control d-none" accept="image/jpeg,image/png,image/gif">
                            <div id="main-preview-grid" class="preview-grid"></div>
                            <div class="progress mt-2 d-none" id="main-upload-progress-wrap" style="height: 10px;">
                                <div id="main-upload-progress" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small id="main-upload-progress-text" class="text-muted d-none">アップロード準備中...</small>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> <strong>注意：</strong><br>
                                • メイン画像を変更する場合は新しい画像を選択してください<br>
                                • JPG/PNG/GIF 形式、最大2MBまでアップロードできます
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">追加画像（複数可）</label>
                            <div id="gallery-dropzone" class="upload-dropzone mb-2" role="button" tabindex="0">
                                <div class="upload-title"><i class="fas fa-images me-1"></i>追加画像をドラッグ＆ドロップ</div>
                                <div class="upload-sub">複数選択可（JPG/PNG/GIF, 1ファイル最大2MB）</div>
                            </div>
                            <input type="file" id="gallery_files" class="form-control d-none" accept="image/jpeg,image/png,image/gif" multiple>
                            <div id="gallery-preview-grid" class="preview-grid"></div>
                            <div class="progress mt-2 d-none" id="gallery-upload-progress-wrap" style="height: 10px;">
                                <div id="gallery-upload-progress" class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small id="gallery-upload-progress-text" class="text-muted d-none">アップロード準備中...</small>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> 詳細画面に表示する追加画像を複数登録できます
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">アレルギー情報</label>
                            <div class="tag-input border rounded-3 p-2 bg-white">
                                <div id="allergen-tag-list" class="d-flex flex-wrap gap-2 mb-2"></div>
                                <input type="text" id="allergen-input" class="form-control border-0 shadow-none p-0" placeholder="例: 小麦, 卵, 乳">
                                <input type="hidden" id="allergens">
                            </div>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Enter またはカンマでタグを追加できます
                            </small>
                            <div class="invalid-feedback d-block" id="allergens-error"></div>
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
    let allergenTags = [];
    const ACCEPTED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif'];
    const MAX_IMAGE_SIZE = 2 * 1024 * 1024;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function setFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const error = document.getElementById(`${fieldId}-error`);
        if (field) {
            field.classList.toggle('is-invalid', Boolean(message));
            field.setAttribute('aria-invalid', message ? 'true' : 'false');
        }
        if (error) {
            error.textContent = message || '';
            error.classList.toggle('d-block', Boolean(message));
        }
    }

    function validateRequiredField(fieldId, message) {
        const field = document.getElementById(fieldId);
        if (!field) return true;
        const isValid = String(field.value || '').trim() !== '';
        setFieldError(fieldId, isValid ? '' : message);
        return isValid;
    }

    function validateLabelField() {
        const field = document.getElementById('label');
        if (!field) return true;
        const value = field.value.trim();
        const isValid = value.length <= 50;
        setFieldError('label', isValid ? '' : 'ラベルは50文字以内で入力してください');
        return isValid;
    }

    function renderAllergenTags() {
        const tagList = document.getElementById('allergen-tag-list');
        const hiddenInput = document.getElementById('allergens');
        if (!tagList || !hiddenInput) return;

        hiddenInput.value = allergenTags.join(', ');
        tagList.innerHTML = allergenTags.map((tag) => `
            <span class="badge rounded-pill text-bg-light border d-inline-flex align-items-center gap-2 allergen-tag">
                <span>${escapeHtml(tag)}</span>
                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-muted allergen-remove" data-tag="${escapeHtml(tag)}" aria-label="${escapeHtml(tag)} を削除">×</button>
            </span>
        `).join('');

        tagList.querySelectorAll('.allergen-remove').forEach((button) => {
            button.addEventListener('click', () => {
                removeAllergenTag(button.getAttribute('data-tag') || '');
            });
        });
    }

    function addAllergenTagsFromText(value) {
        const parts = String(value || '')
            .split(/[\n,、]/)
            .map((part) => part.trim())
            .filter(Boolean);

        let changed = false;
        parts.forEach((tag) => {
            if (!allergenTags.includes(tag)) {
                allergenTags.push(tag);
                changed = true;
            }
        });

        if (changed) {
            renderAllergenTags();
        }
    }

    function removeAllergenTag(tag) {
        allergenTags = allergenTags.filter((item) => item !== tag);
        renderAllergenTags();
    }

    function setupAllergenInput() {
        const input = document.getElementById('allergen-input');
        if (!input) return;

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ',') {
                event.preventDefault();
                addAllergenTagsFromText(input.value);
                input.value = '';
            }
        });

        input.addEventListener('blur', () => {
            if (input.value.trim()) {
                addAllergenTagsFromText(input.value);
                input.value = '';
            }
        });
    }

    function attachImmediateValidation() {
        ['name', 'price', 'category', 'label'].forEach((fieldId) => {
            const field = document.getElementById(fieldId);
            if (!field) return;
            field.addEventListener('input', () => {
                if (fieldId === 'label') {
                    validateLabelField();
                } else {
                    setFieldError(fieldId, '');
                }
            });
            field.addEventListener('blur', () => {
                if (fieldId === 'name') {
                    validateRequiredField('name', '商品名は必須です');
                } else if (fieldId === 'price') {
                    const isValid = String(field.value || '').trim() !== '' && Number(field.value) >= 0;
                    setFieldError('price', isValid ? '' : '価格は0以上の数値で入力してください');
                } else if (fieldId === 'label') {
                    validateLabelField();
                }
            });
        });
    }

    function getScreenElements() {
        return {
            listScreen: document.getElementById('list-screen'),
            formScreen: document.getElementById('form-screen'),
            viewListBtn: document.getElementById('view-list-btn'),
            viewFormBtn: document.getElementById('view-form-btn'),
        };
    }

    function setActiveScreen(screen) {
        const { listScreen, formScreen, viewListBtn, viewFormBtn } = getScreenElements();
        if (!listScreen || !formScreen || !viewListBtn || !viewFormBtn) {
            return;
        }

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

    function validateImageFiles(files, { multiple = false } = {}) {
        const picked = multiple ? Array.from(files || []) : Array.from(files || []).slice(0, 1);
        const valid = [];
        const errors = [];

        for (const file of picked) {
            if (!ACCEPTED_IMAGE_TYPES.includes(file.type)) {
                errors.push(`${file.name}: JPG/PNG/GIFのみ対応です`);
                continue;
            }
            if (file.size > MAX_IMAGE_SIZE) {
                errors.push(`${file.name}: サイズ上限2MBを超えています`);
                continue;
            }
            valid.push(file);
        }

        return { valid, errors };
    }

    function setInputFiles(inputElement, files) {
        const dt = new DataTransfer();
        (files || []).forEach(file => dt.items.add(file));
        inputElement.files = dt.files;
    }

    function renderFilePreviews(files, previewGridId) {
        const grid = document.getElementById(previewGridId);
        if (!grid) return;
        const targetFiles = Array.from(files || []);

        if (targetFiles.length === 0) {
            grid.innerHTML = '';
            return;
        }

        grid.innerHTML = targetFiles.map(file => {
            const tempUrl = URL.createObjectURL(file);
            const escapedName = (file.name || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            return `
                <div class="preview-item">
                    <img src="${tempUrl}" alt="${escapedName}" onload="URL.revokeObjectURL(this.src)">
                    <div class="meta" title="${escapedName}">${escapedName}</div>
                </div>
            `;
        }).join('');
    }

    function setUploadProgress(kind, percent, text) {
        const wrap = document.getElementById(`${kind}-upload-progress-wrap`);
        const bar = document.getElementById(`${kind}-upload-progress`);
        const label = document.getElementById(`${kind}-upload-progress-text`);
        if (!wrap || !bar || !label) return;

        wrap.classList.remove('d-none');
        label.classList.remove('d-none');
        bar.style.width = `${Math.max(0, Math.min(100, percent))}%`;
        label.textContent = text || `アップロード中... ${Math.round(percent)}%`;
    }

    function resetUploadProgress(kind) {
        const wrap = document.getElementById(`${kind}-upload-progress-wrap`);
        const bar = document.getElementById(`${kind}-upload-progress`);
        const label = document.getElementById(`${kind}-upload-progress-text`);
        if (!wrap || !bar || !label) return;

        bar.style.width = '0%';
        wrap.classList.add('d-none');
        label.classList.add('d-none');
        label.textContent = 'アップロード準備中...';
    }

    function bindUploadDropzone({ zoneId, inputId, previewGridId, multiple }) {
        const zone = document.getElementById(zoneId);
        const input = document.getElementById(inputId);
        if (!zone || !input) return;

        const assignFiles = (fileList) => {
            const { valid, errors } = validateImageFiles(fileList, { multiple });
            if (errors.length > 0) {
                showAlert('warning', errors.join('<br>'));
            }
            setInputFiles(input, valid);
            renderFilePreviews(valid, previewGridId);
        };

        zone.addEventListener('click', () => input.click());
        zone.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                input.click();
            }
        });

        ['dragenter', 'dragover'].forEach(name => {
            zone.addEventListener(name, (event) => {
                event.preventDefault();
                event.stopPropagation();
                zone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'dragend'].forEach(name => {
            zone.addEventListener(name, () => zone.classList.remove('is-dragover'));
        });

        zone.addEventListener('drop', (event) => {
            event.preventDefault();
            event.stopPropagation();
            zone.classList.remove('is-dragover');
            assignFiles(event.dataTransfer.files);
        });

        input.addEventListener('change', () => assignFiles(input.files));
    }

    function uploadFileWithProgress(file, progressCallback) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('image', file);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/api/upload-image');
            xhr.setRequestHeader('Authorization', `Bearer ${token}`);
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.upload.onprogress = (event) => {
                if (event.lengthComputable && typeof progressCallback === 'function') {
                    progressCallback(Math.round((event.loaded / event.total) * 100));
                }
            };

            xhr.onload = () => {
                try {
                    const result = JSON.parse(xhr.responseText || '{}');
                    if (xhr.status >= 200 && xhr.status < 300 && result.success && result.data && result.data.url) {
                        resolve(result.data.url);
                        return;
                    }
                    reject(new Error(result.message || '画像アップロードに失敗しました'));
                } catch (error) {
                    reject(new Error('画像アップロードの応答が不正です'));
                }
            };

            xhr.onerror = () => reject(new Error('画像アップロード通信でエラーが発生しました'));
            xhr.send(formData);
        });
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
                displayProductsMobile(myProducts);
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
                            <button class="btn btn-info" type="button" aria-label="${product.name} の詳細を表示" onclick='showProductDetail(${JSON.stringify(product)})'>
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-warning" type="button" aria-label="${product.name} を編集" onclick='editProduct(${JSON.stringify(product)})'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger" type="button" aria-label="${product.name} を削除" onclick="deleteProduct(${product.id}, '${product.name}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function displayProductsMobile(products) {
        const container = document.getElementById('products-mobile-list');
        if (!container) return;
        if (!products || products.length === 0) {
            container.innerHTML = '<div class="text-center">商品がありません</div>';
            return;
        }

        container.innerHTML = products.map(product => {
            const image = product.image_url ? `<img src="${product.image_url}" alt="${escapeHtml(product.name)}" class="product-thumb">` : `<div class="bg-secondary text-white d-flex align-items-center justify-content-center" style="width:92px;height:72px;border-radius:6px;">画像なし</div>`;
            return `
                <div class="product-card mb-3">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <div style="flex:0 0 92px;">
                            ${image}
                        </div>
                        <div style="flex:1;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-bold">${escapeHtml(product.name)}</div>
                                    ${product.label ? `<div class="mt-1"><span class="badge bg-warning text-dark">${escapeHtml(product.label)}</span></div>` : ''}
                                </div>
                                <div class="text-nowrap">¥${Number(product.price || 0).toLocaleString()}</div>
                            </div>
                            <div class="mt-2 text-muted small">
                                <span>在庫: <strong>${Number(product.stock||0)}</strong></span>
                                <span class="ms-2">カテゴリ: ${escapeHtml(product.category || '-')}</span>
                            </div>
                            <div class="mt-2 product-card-action">
                                <button class="btn btn-info btn-sm" type="button" aria-label="${escapeHtml(product.name)} の詳細" onclick='showProductDetail(${JSON.stringify(product)})'>詳細</button>
                                <button class="btn btn-warning btn-sm" type="button" aria-label="${escapeHtml(product.name)} を編集" onclick='editProduct(${JSON.stringify(product)})'>編集</button>
                                <button class="btn btn-danger btn-sm" type="button" aria-label="${escapeHtml(product.name)} を削除" onclick="deleteProduct(${product.id}, '${escapeHtml(product.name)}')">削除</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function showProductDetail(product) {
        const galleryImages = Array.isArray(product.additional_image_urls) ? product.additional_image_urls : [];
        const galleryMarkup = galleryImages.length > 0
            ? `
                <div class="mt-3">
                    <h6 class="mb-2">追加画像</h6>
                    <div class="d-flex flex-wrap gap-2">
                        ${galleryImages.map(imageUrl => `
                            <a href="${imageUrl}" target="_blank" rel="noopener noreferrer">
                                <img src="${imageUrl}" class="img-thumbnail" style="width: 96px; height: 96px; object-fit: cover;" alt="${product.name}">
                            </a>
                        `).join('')}
                    </div>
                </div>
            `
            : '<div class="mt-3 text-muted">追加画像はありません</div>';

        const content = `
            <div class="row">
                <div class="col-md-4">
                    ${product.image_url ? 
                        `<img src="${product.image_original_url || product.image_url}" class="img-fluid rounded mb-2" alt="${product.name}">` : 
                        '<div class="bg-secondary text-white d-flex align-items-center justify-content-center" style="height: 200px; border-radius: 5px;">画像なし</div>'
                    }
                    ${galleryMarkup}
                </div>
                <div class="col-md-8">
                    <table class="table table-borderless">
                        <tr><th style="width: 120px;">商品名</th><td>${product.name}</td></tr>
                        <tr><th>価格</th><td>¥${product.price.toLocaleString()}</td></tr>
                        <tr><th>在庫</th><td><span class="badge ${product.stock > 0 ? 'bg-success' : 'bg-danger'}">${product.stock}個</span></td></tr>
                        <tr><th>カテゴリ</th><td><span class="badge bg-secondary">${product.category || '-'}</span></td></tr>
                        <tr><th>説明</th><td>${product.description || '-'}</td></tr>
                    </table>
                </div>
            </div>
        `;

        document.getElementById('productDetailContent').innerHTML = content;
        new bootstrap.Modal(document.getElementById('productDetailModal')).show();
    }

    function updateCategories(products) {
        const categorySelect = document.getElementById('category');
        if (!categorySelect) return;

        const currentValue = categorySelect.value;
        const categories = [...new Set(products.map(p => p.category).filter(Boolean))].sort();
        const options = [
            '<option value="">選択してください</option>',
            '<option value="その他">その他</option>',
            ...categories.filter((category) => category !== 'その他').map((category) => `<option value="${category}">${category}</option>`),
        ];

        categorySelect.innerHTML = options.join('');

        if (currentValue && categories.includes(currentValue) || currentValue === 'その他') {
            categorySelect.value = currentValue;
        }
    }

    async function uploadImageFile(file, progressCallback = null) {
        return uploadFileWithProgress(file, progressCallback);
    }

    async function uploadMultipleImages(files) {
        const uploadedUrls = [];
        const fileCount = files.length || 1;
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const uploadedUrl = await uploadImageFile(file, (progress) => {
                const totalPercent = ((i + (progress / 100)) / fileCount) * 100;
                setUploadProgress('gallery', totalPercent, `追加画像をアップロード中... ${i + 1}/${fileCount}`);
            });
            uploadedUrls.push(uploadedUrl);
        }
        return uploadedUrls;
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

    // ドロップゾーンの初期化は DOMContentLoaded 後に確実に実行する

    // 商品登録・編集
    document.addEventListener('submit', async (e) => {
        if (!(e.target instanceof HTMLFormElement) || e.target.id !== 'productForm') {
            return;
        }

        e.preventDefault();

        const nameValid = validateRequiredField('name', '商品名は必須です');
        const priceField = document.getElementById('price');
        const priceValid = String(priceField.value || '').trim() !== '' && Number(priceField.value) >= 0;
        const labelValid = validateLabelField();
        setFieldError('price', priceValid ? '' : '価格は0以上の数値で入力してください');

        if (!nameValid || !priceValid || !labelValid) {
            e.target.reportValidity();
            return;
        }
        
        const id = document.getElementById('product_id').value;
        const imageFile = document.getElementById('image_file').files[0] || null;
        const galleryFiles = Array.from(document.getElementById('gallery_files').files || []);
        const data = {
            name: document.getElementById('name').value,
            price: parseInt(document.getElementById('price').value),
            stock: parseInt(document.getElementById('stock').value) || 0,
            category: document.getElementById('category').value || 'その他',
            seller_id: user.id, // 自分のIDを設定
            label: document.getElementById('label').value.trim() || null,
            description: document.getElementById('description').value || null,
            allergens: document.getElementById('allergens').value || null
        };

        try {
            resetUploadProgress('main');
            resetUploadProgress('gallery');

            if (!id && !imageFile) {
                showAlert('warning', 'メイン画像を登録してください。');
                return;
            }

            if (id && shouldRemoveCurrentImage) {
                data.image_url = '';
            }

            if (imageFile) {
                data.image_url = await uploadImageFile(imageFile, (progress) => {
                    setUploadProgress('main', progress, `メイン画像をアップロード中... ${progress}%`);
                });
                setUploadProgress('main', 100, 'メイン画像アップロード完了');
            }

            if (galleryFiles.length > 0) {
                data.additional_image_urls = await uploadMultipleImages(galleryFiles);
                setUploadProgress('gallery', 100, '追加画像アップロード完了');
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
        document.getElementById('gallery_files').value = '';
        document.getElementById('main-preview-grid').innerHTML = '';
        document.getElementById('gallery-preview-grid').innerHTML = '';
        resetUploadProgress('main');
        resetUploadProgress('gallery');
        allergenTags = String(product.allergens || '')
            .split(/[\n,、]/)
            .map((value) => value.trim())
            .filter(Boolean);
        renderAllergenTags();
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
        document.getElementById('gallery_files').value = '';
        document.getElementById('label').value = '';
        document.getElementById('allergen-input').value = '';
        allergenTags = [];
        renderAllergenTags();
        editingImageUrl = '';
        shouldRemoveCurrentImage = false;
        updateCurrentImagePreview('');
        document.getElementById('main-preview-grid').innerHTML = '';
        document.getElementById('gallery-preview-grid').innerHTML = '';
        resetUploadProgress('main');
        resetUploadProgress('gallery');
        document.getElementById('form-title').innerHTML = '<i class="fas fa-plus me-2"></i>商品追加';
        document.getElementById('submit-btn').innerHTML = '<i class="fas fa-save me-1"></i>登録';
        document.getElementById('cancel-btn').style.display = 'none';
        ['name', 'price', 'label'].forEach((fieldId) => setFieldError(fieldId, ''));
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
    function initializeSellerProductsPage() {
        setupAllergenInput();
        attachImmediateValidation();
        // ドロップゾーン初期化（要素が確実に存在するタイミングで実行）
        bindUploadDropzone({
            zoneId: 'main-dropzone',
            inputId: 'image_file',
            previewGridId: 'main-preview-grid',
            multiple: false,
        });

        bindUploadDropzone({
            zoneId: 'gallery-dropzone',
            inputId: 'gallery_files',
            previewGridId: 'gallery-preview-grid',
            multiple: true,
        });
        switchToListView();
        loadProducts();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeSellerProductsPage, { once: true });
    } else {
        initializeSellerProductsPage();
    }
</script>
