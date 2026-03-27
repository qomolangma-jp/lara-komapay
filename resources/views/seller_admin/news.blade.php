@extends('layouts.seller_layout')

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
            <i class="fas fa-plus me-1"></i>投稿画面
        </button>
    </div>
</div>

<div id="list-screen">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>自分のニュース一覧
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
                            <th style="width: 80px;">ID</th>
                            <th>タイトル</th>
                            <th style="width: 120px;">公開状態</th>
                            <th style="width: 180px;">投稿日時</th>
                            <th style="width: 180px;">最終更新</th>
                            <th style="width: 140px;">操作</th>
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
                            <label class="form-label">公開状態</label>
                            <select id="is_published" class="form-select">
                                <option value="1">公開</option>
                                <option value="0">非公開</option>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>保存
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
    const token = localStorage.getItem('token') || localStorage.getItem('authToken') || '';
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    const listScreen = document.getElementById('list-screen');
    const formScreen = document.getElementById('form-screen');
    const viewListBtn = document.getElementById('view-list-btn');
    const viewFormBtn = document.getElementById('view-form-btn');

    function getHeaders(contentType = null) {
        const headers = { 'Accept': 'application/json' };
        if (token) headers['Authorization'] = `Bearer ${token}`;
        if (contentType) headers['Content-Type'] = contentType;
        return headers;
    }

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

    let allNews = [];

    async function loadNews() {
        try {
            const response = await fetch('/api/news', {
                headers: getHeaders()
            });

            if (response.ok) {
                const result = await response.json();
                allNews = result.data || [];
                displayNews(allNews);
            } else {
                const error = await response.json().catch(() => ({}));
                showAlert('danger', error.message || 'ニュースの取得に失敗しました');
            }
        } catch (error) {
            console.error('ニュースの読み込みエラー:', error);
            showAlert('danger', 'ニュースの読み込みに失敗しました');
        }
    }

    function displayNews(newsList) {
        const tbody = document.getElementById('news-list');
        if (!newsList || newsList.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">ニュースがありません</td></tr>';
            return;
        }
        
        tbody.innerHTML = newsList.map(news => {
            const publishBadge = news.is_published 
                ? '<span class="badge bg-success">公開</span>' 
                : '<span class="badge bg-secondary">非公開</span>';
            
            return `
                <tr>
                    <td>#${news.id}</td>
                    <td>${news.title}</td>
                    <td>${publishBadge}</td>
                    <td>${new Date(news.created_at).toLocaleString('ja-JP')}</td>
                    <td><small class="text-muted">${new Date(news.updated_at).toLocaleString('ja-JP')}</small></td>
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
            `;
        }).join('');
    }

    document.getElementById('newsForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const id = document.getElementById('news_id').value;
        const data = {
            title: document.getElementById('title').value,
            content: document.getElementById('content').value,
            is_published: parseInt(document.getElementById('is_published').value, 10),
            user_id: user.id || null,
        };

        try {
            const url = id ? `/api/news/${id}` : '/api/news';
            const method = id ? 'PUT' : 'POST';
            const response = await fetch(url, {
                method,
                headers: getHeaders('application/json'),
                body: JSON.stringify(data),
            });

            const result = await response.json().catch(() => ({}));
            if (response.ok && result.success) {
                showAlert('success', id ? 'ニュースを更新しました' : 'ニュースを投稿しました');
                resetForm();
                loadNews();
                switchToListView();
            } else {
                showAlert('danger', result.message || '処理に失敗しました');
            }
        } catch (error) {
            console.error('保存エラー:', error);
            showAlert('danger', 'エラーが発生しました');
        }
    });

    function editNews(news) {
        document.getElementById('news_id').value = news.id;
        document.getElementById('title').value = news.title;
        document.getElementById('content').value = news.content;
        document.getElementById('is_published').value = news.is_published ? '1' : '0';
        document.getElementById('cancel-btn').style.display = 'block';
        document.getElementById('form-title').innerHTML = '<i class="fas fa-edit me-2"></i>ニュース編集';
        switchToFormView(true);
    }

    async function deleteNews(id, title) {
        if (!confirm(`「${title}」を削除してもよろしいですか？`)) return;

        try {
            const response = await fetch(`/api/news/${id}`, {
                method: 'DELETE',
                headers: getHeaders(),
            });

            const result = await response.json().catch(() => ({}));
            if (response.ok && result.success) {
                showAlert('success', 'ニュースを削除しました');
                loadNews();
            } else {
                showAlert('danger', result.message || '削除に失敗しました');
            }
        } catch (error) {
            console.error('削除エラー:', error);
            showAlert('danger', 'エラーが発生しました');
        }
    }

    function resetForm() {
        document.getElementById('newsForm').reset();
        document.getElementById('news_id').value = '';
        document.getElementById('is_published').value = '1';
        document.getElementById('cancel-btn').style.display = 'none';
        document.getElementById('form-title').innerHTML = '<i class="fas fa-plus me-2"></i>ニュース投稿';
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

    switchToListView();
    loadNews();
</script>
@endsection
