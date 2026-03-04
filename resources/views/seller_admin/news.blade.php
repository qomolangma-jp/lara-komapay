@extends('layouts.seller_layout')

@section('title', 'ニュース管理')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">ニュース管理（閲覧のみ）</h1>
    <div>
        <button class="btn btn-sm btn-success" onclick="loadNews()">
            <i class="fas fa-sync me-1"></i>更新
        </button>
    </div>
</div>

<div id="alert-area"></div>

<!-- フィルター -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <label class="form-label">表示絞り込み</label>
                <select class="form-select" id="publishFilter" onchange="filterNews()">
                    <option value="">すべて</option>
                    <option value="1">公開のみ</option>
                    <option value="0">非公開のみ</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- ニュース一覧 -->
<div class="row">
    <div class="col-md-12">
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
                                <th style="width: 80px;">ID</th>
                                <th>タイトル</th>
                                <th style="width: 120px;">公開状態</th>
                                <th style="width: 180px;">投稿日時</th>
                                <th style="width: 100px;">操作</th>
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

<!-- ニュース詳細モーダル -->
<div class="modal fade" id="newsDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newsDetailTitle">ニュース詳細</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="newsDetailContent">
                <!-- 詳細内容がここに表示される -->
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
    const token = localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');

    // 販売者権限確認
    if (!token) {
        window.location.href = '/login';
    }

    let allNews = [];

    // ニュース一覧を読み込み
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
                allNews = result.data;
                displayNews(allNews);
            }
        } catch (error) {
            console.error('ニュースの読み込みエラー:', error);
            showAlert('danger', 'ニュースの読み込みに失敗しました');
        }
    }

    function displayNews(newsList) {
        const tbody = document.getElementById('news-list');
        if (!newsList || newsList.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">ニュースがありません</td></tr>';
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
                    <td>
                        <button class="btn btn-sm btn-info" onclick='viewNews(${JSON.stringify(news)})'>
                            <i class="fas fa-eye"></i> 詳細
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function viewNews(news) {
        document.getElementById('newsDetailTitle').textContent = news.title;
        
        const publishBadge = news.is_published 
            ? '<span class="badge bg-success">公開</span>' 
            : '<span class="badge bg-secondary">非公開</span>';
        
        const content = `
            <div class="mb-3">
                <strong>公開状態:</strong> ${publishBadge}
            </div>
            <div class="mb-3">
                <strong>投稿日時:</strong> ${new Date(news.created_at).toLocaleString('ja-JP')}
            </div>
            <div class="mb-3">
                <strong>本文:</strong>
                <div class="border rounded p-3 mt-2" style="white-space: pre-wrap; background-color: #f8f9fa;">
                    ${news.content}
                </div>
            </div>
        `;
        
        document.getElementById('newsDetailContent').innerHTML = content;
        new bootstrap.Modal(document.getElementById('newsDetailModal')).show();
    }

    function filterNews() {
        const publishValue = document.getElementById('publishFilter').value;
        
        if (publishValue === '') {
            displayNews(allNews);
        } else {
            const isPublished = publishValue === '1';
            const filtered = allNews.filter(n => n.is_published === isPublished);
            displayNews(filtered);
        }
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
    loadNews();
</script>
@endsection
