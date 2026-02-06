@extends('layouts.master_layout')

@section('title', 'ニュース管理')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">ニュース管理</h1>
</div>

<div id="alert-area"></div>

<div class="row">
    <!-- ニュース追加フォーム -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-plus me-2"></i>ニュース投稿
                </h5>
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
                            <i class="fas fa-save me-1"></i>投稿
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="resetForm()" id="cancel-btn" style="display:none;">
                            <i class="fas fa-times me-1"></i>キャンセル
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ニュース一覧 -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>ニュース一覧
                </h5>
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
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="news-list">
                            <tr><td colspan="5" class="text-center">読み込み中...</td></tr>
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

    if (!token || !user.is_admin) {
        window.location.href = '/login';
    }

    async function loadNews() {
        try {
            const response = await fetch('/api/news', {
                headers: {
                    'Authorization': `Bearer ${token}`,
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
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">ニュースがありません</td></tr>';
            return;
        }

        tbody.innerHTML = newsList.map(news => `
            <tr>
                <td>${news.id}</td>
                <td>${news.title}</td>
                <td>
                    <span class="badge ${news.is_published ? 'bg-success' : 'bg-secondary'}">
                        ${news.is_published ? '公開' : '非公開'}
                    </span>
                </td>
                <td>${new Date(news.created_at).toLocaleString('ja-JP')}</td>
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
        const data = {
            title: document.getElementById('title').value,
            content: document.getElementById('content').value,
            is_published: parseInt(document.getElementById('is_published').value)
        };

        try {
            const url = id ? `/api/news/${id}` : '/api/news';
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

            if (response.ok) {
                showAlert('success', id ? 'ニュースを更新しました' : 'ニュースを投稿しました');
                resetForm();
                loadNews();
            } else {
                showAlert('danger', '処理に失敗しました');
            }
        } catch (error) {
            showAlert('danger', 'エラーが発生しました');
        }
    });

    function editNews(news) {
        document.getElementById('news_id').value = news.id;
        document.getElementById('title').value = news.title;
        document.getElementById('content').value = news.content;
        document.getElementById('is_published').value = news.is_published ? '1' : '0';
        document.getElementById('cancel-btn').style.display = 'block';
    }

    async function deleteNews(id, title) {
        if (!confirm(`「${title}」を削除してもよろしいですか？`)) return;
        
        try {
            const response = await fetch(`/api/news/${id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                showAlert('success', 'ニュースを削除しました');
                loadNews();
            } else {
                showAlert('danger', '削除に失敗しました');
            }
        } catch (error) {
            showAlert('danger', 'エラーが発生しました');
        }
    }

    function resetForm() {
        document.getElementById('newsForm').reset();
        document.getElementById('news_id').value = '';
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

    loadNews();
</script>
@endsection
