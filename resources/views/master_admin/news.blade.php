@extends('layouts.master_layout')

@section('title', 'ニュース管理')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">ニュース管理</h1>
</div>

<div id="alert-area"></div>

<div class="mb-3">
    <div class="btn-group" role="group" aria-label="画面切り替え">
        <button type="button" class="btn btn-primary" id="view-list-btn" onclick="switchToListView()">
            <i class="fas fa-list me-1"></i>一覧画面
        </button>
        <button type="button" class="btn btn-outline-primary" id="view-form-btn" onclick="switchToFormView(false)">
            <i class="fas fa-plus me-1"></i>登録・編集画面
        </button>
    </div>
</div>

<div id="list-screen">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>ニュース一覧
            </h5>
            <button type="button" class="btn btn-primary btn-sm" onclick="switchToFormView(false)">
                <i class="fas fa-plus me-1"></i>新規投稿
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>タイトル</th>
                            <th>公開状態</th>
                            <th>投稿日時</th>
                            <th>最終更新</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="news-list">
                        <tr><td colspan="6" class="text-center">読み込み中...</td></tr>
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
                        <i class="fas fa-plus me-2"></i>ニュース投稿
                    </h5>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="switchToListView()">
                        <i class="fas fa-arrow-left me-1"></i>一覧に戻る
                    </button>
                </div>
                <div class="card-body">
                    <form id="newsForm">
                        <input type="hidden" id="news_id" name="news_id">
                        
                        <div class="mb-3">
                            <label class="form-label">タイトル <span class="text-danger">*</span></label>
                            <input type="text" id="title" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">本文 <span class="text-danger">*</span></label>
                            <textarea id="content" class="form-control" rows="5" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">画像（任意）</label>
                            <input type="file" id="image" class="form-control" accept="image/*">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="remove_image">
                                <label class="form-check-label" for="remove_image">現在の画像を削除</label>
                            </div>
                            <div id="image-preview-wrapper" class="mt-2 d-none">
                                <img id="image-preview" src="" alt="ニュース画像プレビュー" class="img-thumbnail" style="max-height: 180px;">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">公開状態</label>
                            <select id="is_published" class="form-select">
                                <option value="1">公開</option>
                                <option value="0">非公開</option>
                            </select>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>投稿
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
    const listScreen = document.getElementById('list-screen');
    const formScreen = document.getElementById('form-screen');
    const viewListBtn = document.getElementById('view-list-btn');
    const viewFormBtn = document.getElementById('view-form-btn');
    const imageInput = document.getElementById('image');
    const removeImageCheckbox = document.getElementById('remove_image');
    const imagePreviewWrapper = document.getElementById('image-preview-wrapper');
    const imagePreview = document.getElementById('image-preview');

    function setActiveScreen(screen) {
        if (screen === 'form') {
            listScreen.classList.add('d-none');
            formScreen.classList.remove('d-none');
            viewListBtn.classList.remove('btn-primary');
            viewListBtn.classList.add('btn-outline-primary');
            viewFormBtn.classList.remove('btn-outline-primary');
            viewFormBtn.classList.add('btn-primary');
        } else {
            formScreen.classList.add('d-none');
            listScreen.classList.remove('d-none');
            viewFormBtn.classList.remove('btn-primary');
            viewFormBtn.classList.add('btn-outline-primary');
            viewListBtn.classList.remove('btn-outline-primary');
            viewListBtn.classList.add('btn-primary');
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

    async function loadNews() {
        try {
            const response = await fetch('/api/master/news', {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                displayNews(result.data || []);
            }
        } catch (error) {
            console.error('ニュースの読み込みエラー:', error);
        }
    }

    function displayNews(newsList) {
        const tbody = document.getElementById('news-list');
        if (!newsList || newsList.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">ニュースがありません</td></tr>';
            return;
        }

        const formatJstDateTime = (value) => {
            if (!value) return '-';

            const raw = String(value).trim();
            const hasTimezone = /(?:Z|[+-]\d{2}:?\d{2})$/i.test(raw);
            const normalized = hasTimezone
                ? raw
                : raw.replace(' ', 'T') + 'Z';

            const date = new Date(normalized);
            if (Number.isNaN(date.getTime())) return '-';

            return date.toLocaleString('ja-JP', {
                timeZone: 'Asia/Tokyo',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
            });
        };

        tbody.innerHTML = newsList.map(news => `
            <tr>
                <td>${news.id}</td>
                <td>
                    <div>${news.title}</div>
                    ${news.image_url ? `<img src="${news.image_url}" alt="ニュース画像" class="img-thumbnail mt-1" style="width: 72px; height: 72px; object-fit: cover;">` : ''}
                </td>
                <td>
                    <span class="badge ${news.is_published ? 'bg-success' : 'bg-secondary'}">
                        ${news.is_published ? '公開' : '非公開'}
                    </span>
                </td>
                <td>${formatJstDateTime(news.created_at)}</td>
                <td>
                    <small class="text-muted">${formatJstDateTime(news.updated_at)}</small>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-warning" onclick='editNews(${JSON.stringify(news)})'>
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger" onclick="deleteNews(${news.id}, '${news.title}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    document.getElementById('newsForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const id = document.getElementById('news_id').value;
        const formData = new FormData();
        formData.append('title', document.getElementById('title').value);
        formData.append('content', document.getElementById('content').value);
        formData.append('is_published', document.getElementById('is_published').value);
        formData.append('remove_image', removeImageCheckbox.checked ? '1' : '0');

        const imageFile = imageInput.files && imageInput.files[0] ? imageInput.files[0] : null;
        if (imageFile) {
            formData.append('image', imageFile);
        }

        console.log('Form submit - ID:', id);

        try {
            let url = '/api/master/news';
            let method = 'POST';
            if (id) {
                url = `/api/master/news/${id}`;
                formData.append('_method', 'PUT');
            }
            
            console.log('Request URL:', url, 'Method:', method);
            
            const response = await fetch(url, {
                method,
                headers: {
                    'Accept': 'application/json'
                },
                body: formData
            });

            console.log('Response status:', response.status);
            const result = await response.json();
            console.log('Response data:', result);
            
            if (response.ok && result.success) {
                showAlert('success', id ? 'ニュースを更新しました' : 'ニュースを投稿しました');
                resetForm();
                loadNews();
                switchToListView();
            } else {
                console.error('Error response:', result);
                const detail = result.error ? ` (${result.error})` : '';
                showAlert('danger', (result.message || '処理に失敗しました') + detail);
            }
        } catch (error) {
            console.error('Fetch error:', error);
            showAlert('danger', 'エラーが発生しました: ' + error.message);
        }
    });

    function editNews(news) {
        document.getElementById('news_id').value = news.id;
        document.getElementById('title').value = news.title;
        document.getElementById('content').value = news.content;
        document.getElementById('is_published').value = news.is_published ? '1' : '0';
        removeImageCheckbox.checked = false;
        imageInput.value = '';
        updateImagePreview(news.image_url || '');
        document.getElementById('cancel-btn').style.display = 'block';
        document.getElementById('form-title').innerHTML = '<i class="fas fa-edit me-2"></i>ニュース編集';
        console.log('Edit mode - news_id set to:', news.id);
        switchToFormView(true);
    }

    async function deleteNews(id, title) {
        if (!confirm(`「${title}」を削除してもよろしいですか？`)) return;
        
        // 削除実行前に即座にフォームをリセット（編集中のIDをクリア）
        console.log('Deleting news ID:', id, '- Resetting form immediately');
        resetForm();
        
        try {
            const response = await fetch(`/api/master/news/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                console.log('News deleted successfully');
                showAlert('success', 'ニュースを削除しました');
                loadNews();
            } else {
                const errorData = await response.json();
                showAlert('danger', '削除に失敗しました: ' + (errorData.message || '不明なエラー'));
            }
        } catch (error) {
            console.log('Delete error:', error);
            showAlert('danger', 'エラーが発生しました');
        }
    }

    function resetForm() {
        // フォームをリセット
        document.getElementById('newsForm').reset();
        
        // 明示的に全フィールドをクリア
        document.getElementById('news_id').value = '';
        document.getElementById('title').value = '';
        document.getElementById('content').value = '';
        document.getElementById('is_published').value = '1';
        removeImageCheckbox.checked = false;
        imageInput.value = '';
        updateImagePreview('');
        
        // UIをリセット
        document.getElementById('cancel-btn').style.display = 'none';
        document.getElementById('form-title').innerHTML = '<i class="fas fa-newspaper me-2"></i>ニュース投稿';
        
        console.log('Form reset completed - news_id:', document.getElementById('news_id').value);
    }

    function updateImagePreview(imageUrl) {
        if (!imageUrl) {
            imagePreview.src = '';
            imagePreviewWrapper.classList.add('d-none');
            return;
        }

        imagePreview.src = imageUrl;
        imagePreviewWrapper.classList.remove('d-none');
    }

    imageInput.addEventListener('change', () => {
        const file = imageInput.files && imageInput.files[0] ? imageInput.files[0] : null;
        if (!file) {
            return;
        }

        removeImageCheckbox.checked = false;
        const objectUrl = URL.createObjectURL(file);
        updateImagePreview(objectUrl);
    });

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

    switchToListView();
    loadNews();
</script>
@endsection
