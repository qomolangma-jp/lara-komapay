@extends('layouts.master_layout')

@section('title', 'ユーザー管理')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">ユーザー管理</h1>
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
            <h5 class="mb-0"><i class="fas fa-users me-2"></i>ユーザー一覧</h5>
            <button type="button" class="btn btn-primary btn-sm" onclick="switchToFormView(false)">
                <i class="fas fa-plus me-1"></i>新規登録
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ユーザー名</th>
                            <th>氏名</th>
                            <th>店舗名</th>
                            <th>LINE ID</th>
                            <th>学生ID</th>
                            <th>ステータス</th>
                            <th>管理者</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="users-table-body">
                        <tr>
                            <td colspan="8" class="text-center">読み込み中...</td>
                        </tr>
                    </tbody>
                </table>
                <div id="user-pagination"></div>
            </div>
        </div>
    </div>
</div>

<div id="form-screen" class="d-none">
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0" id="form-title"><i class="fas fa-plus me-2"></i>ユーザー登録</h5>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="switchToListView()">
                <i class="fas fa-arrow-left me-1"></i>一覧に戻る
            </button>
        </div>
        <div class="card-body">
            <form id="userForm" onsubmit="saveUser(event)">
                <input type="hidden" id="user_id">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ユーザー名 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" required pattern="[!-~]+" title="半角英数字と記号のみ入力できます（スペース不可）" placeholder="例: yamada_01">
                        <small class="form-text text-muted">半角英数字と記号のみ（スペース不可）</small>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">姓（本名） <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name_2nd" required placeholder="例: 山田">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">名（本名） <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name_1st" required placeholder="例: 太郎">
                    </div>
                </div>
                <div class="mb-2">
                    <small class="text-muted">氏名は本名を入力してください。</small>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">店舗名 <span class="text-muted small">(販売者の場合)</span></label>
                        <input type="text" class="form-control" id="shop_name" placeholder="例: 学食A店舗">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">LINE ID</label>
                        <input type="text" class="form-control" id="line_id">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">学生ID</label>
                        <input type="text" class="form-control" id="student_id">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">パスワード <span class="text-danger" id="password-required">*</span><span class="text-muted small" id="password-hint" style="display:none;"> (編集時は変更する場合のみ入力)</span></label>
                        <input type="password" class="form-control" id="password">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">ステータス</label>
                        <select class="form-control" id="status">
                            <option value="student">student</option>
                            <option value="teacher">teacher</option>
                            <option value="seller">seller</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">管理者権限</label>
                        <select class="form-control" id="is_admin">
                            <option value="0">×</option>
                            <option value="1">○</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="submit-btn">
                        <i class="fas fa-save me-1"></i>登録
                    </button>
                    <button type="button" class="btn btn-secondary" id="cancel-btn" style="display: none;" onclick="resetForm(); switchToListView();">
                        <i class="fas fa-times me-1"></i>キャンセル
                    </button>
                </div>
            </form>
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

    function normalizeUsername(value) {
        return (value || '').replace(/[^\x21-\x7E]/g, '');
    }

    // ユーザー一覧を読み込み（高速化版）
    let currentPage = 1;
    let totalPages = 1;
    
    async function loadUsers(page = 1) {
        try {
            const response = await fetch(`/api/master/users?per_page=100&page=${page}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                currentPage = result.pagination.current_page;
                totalPages = result.pagination.last_page;
                displayUsers(result.data);
                updatePagination();
            } else {
                const errorData = await response.text();
                console.error('API Error Status:', response.status);
                console.error('API Error Response:', errorData);
                showAlert('danger', `ユーザー情報の読み込みに失敗しました (${response.status})`);
                document.getElementById('users-table-body').innerHTML = 
                    '<tr><td colspan="8" class="text-center text-danger">エラーが発生しました。コンソールを確認してください。</td></tr>';
            }
        } catch (error) {
            console.error('ユーザーの読み込みエラー:', error);
            showAlert('danger', 'ネットワークエラーが発生しました');
        }
    }
    
    function updatePagination() {
        const paginationDiv = document.getElementById('user-pagination');
        if (!paginationDiv) return;
        
        let html = `<div class="d-flex justify-content-between align-items-center mt-3">`;
        html += `<div>ページ ${currentPage} / ${totalPages}</div>`;
        html += `<div class="btn-group">`;
        
        if (currentPage > 1) {
            html += `<button class="btn btn-sm btn-outline-primary" onclick="loadUsers(${currentPage - 1})">前へ</button>`;
        }
        if (currentPage < totalPages) {
            html += `<button class="btn btn-sm btn-outline-primary" onclick="loadUsers(${currentPage + 1})">次へ</button>`;
        }
        
        html += `</div></div>`;
        paginationDiv.innerHTML = html;
    }

    // ユーザー一覧を表示
    function displayUsers(users) {
        const tbody = document.getElementById('users-table-body');
        
        if (users.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">ユーザーが見つかりません</td></tr>';
            return;
        }

        tbody.innerHTML = users.map(user => `
            <tr>
                <td>${user.id}</td>
                <td>${user.username || ''}</td>
                <td>${user.name_2nd || ''} ${user.name_1st || ''}</td>
                <td>${user.shop_name || '-'}</td>
                <td>${user.line_id || '-'}</td>
                <td>${user.student_id || '-'}</td>
                <td>${user.status || '-'}</td>
                <td>${user.is_admin ? '○' : '×'}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editUser(${user.id})">
                        <i class="fas fa-edit"></i> 編集
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">
                        <i class="fas fa-trash"></i> 削除
                    </button>
                </td>
            </tr>
        `).join('');
    }

    // ユーザーを編集
    async function editUser(id) {
        try {
            const response = await fetch('/api/master/users', {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                const user = result.data.find(u => u.id === id);
                
                if (user) {
                    document.getElementById('user_id').value = user.id;
                    document.getElementById('username').value = user.username || '';
                    document.getElementById('name_2nd').value = user.name_2nd || '';
                    document.getElementById('shop_name').value = user.shop_name || '';
                    document.getElementById('name_1st').value = user.name_1st || '';
                    document.getElementById('line_id').value = user.line_id || '';
                    document.getElementById('student_id').value = user.student_id || '';
                    document.getElementById('status').value = user.status || 'student';
                    document.getElementById('is_admin').value = user.is_admin ? '1' : '0';
                    document.getElementById('password').value = '';
                    
                    document.getElementById('form-title').innerHTML = '<i class="fas fa-edit me-2"></i>ユーザー編集';
                    document.getElementById('submit-btn').innerHTML = '<i class="fas fa-save me-1"></i>更新';
                    document.getElementById('cancel-btn').style.display = 'inline-block';
                    
                    // パスワードフィールドの表示を切り替え
                    document.getElementById('password-required').style.display = 'none';
                    document.getElementById('password-hint').style.display = 'inline';
                    switchToFormView(true);
                }
            }
        } catch (error) {
            console.error('ユーザー情報の読み込みエラー:', error);
        }
    }

    // ユーザーを保存（新規登録・更新）
    async function saveUser(event) {
        event.preventDefault();

        const userId = document.getElementById('user_id').value;
        const usernameInput = document.getElementById('username');
        usernameInput.value = normalizeUsername(usernameInput.value);
        if (!usernameInput.value) {
            showAlert('warning', 'ユーザー名は半角英数字と記号で入力してください（スペース不可）');
            return;
        }

        const formData = {
            username: usernameInput.value,
            shop_name: document.getElementById('shop_name').value || null,
            name_2nd: document.getElementById('name_2nd').value,
            name_1st: document.getElementById('name_1st').value,
            line_id: document.getElementById('line_id').value || null,
            student_id: document.getElementById('student_id').value || null,
            status: document.getElementById('status').value,
            is_admin: document.getElementById('is_admin').value === '1',
        };

        // パスワードが入力されている場合のみ追加
        const password = document.getElementById('password').value;
        if (password) {
            formData.password = password;
        }

        try {
            let url, method;
            
            if (userId) {
                // 更新
                url = `/api/master/users/${userId}`;
                method = 'PUT';
            } else {
                // 新規登録
                if (!password) {
                    showAlert('warning', 'パスワードを入力してください');
                    return;
                }
                url = `/api/master/users`;
                method = 'POST';
            }

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (response.ok) {
                showAlert('success', result.message || 'ユーザーを保存しました');
                resetForm();
                loadUsers();
                switchToListView();
            } else {
                showAlert('danger', result.message || '保存に失敗しました');
            }
        } catch (error) {
            console.error('保存エラー:', error);
            showAlert('danger', 'エラーが発生しました');
        }
    }

    // ユーザーを削除
    async function deleteUser(id) {
        if (!confirm('本当にこのユーザーを削除しますか？')) {
            return;
        }

        try {
            const response = await fetch(`/api/master/users/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                showAlert('success', 'ユーザーを削除しました');
                loadUsers();
            } else {
                const result = await response.json();
                showAlert('danger', result.message || '削除に失敗しました');
            }
        } catch (error) {
            console.error('削除エラー:', error);
            showAlert('danger', 'エラーが発生しました');
        }
    }

    function resetForm() {
        document.getElementById('userForm').reset();
        document.getElementById('user_id').value = '';
        document.getElementById('form-title').innerHTML = '<i class="fas fa-plus me-2"></i>ユーザー登録';
        document.getElementById('submit-btn').innerHTML = '<i class="fas fa-save me-1"></i>登録';
        document.getElementById('cancel-btn').style.display = 'none';
        
        // パスワードフィールドの表示を切り替え
        document.getElementById('password-required').style.display = 'inline';
        document.getElementById('password-hint').style.display = 'none';
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
    document.getElementById('username').addEventListener('input', (event) => {
        event.target.value = normalizeUsername(event.target.value);
    });

    switchToListView();
    loadUsers();
</script>
@endsection
