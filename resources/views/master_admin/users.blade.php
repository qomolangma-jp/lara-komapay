@extends('layouts.master_layout')

@section('title', 'ユーザー管理')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">ユーザー管理</h1>
    <button class="btn btn-primary" onclick="resetForm()"><i class="fas fa-plus me-1"></i>新規登録</button>
</div>

<div id="alert-area"></div>

<!-- ユーザー登録・編集フォーム -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0" id="form-title"><i class="fas fa-plus me-2"></i>ユーザー登録</h5>
    </div>
    <div class="card-body">
        <form id="userForm" onsubmit="saveUser(event)">
            <input type="hidden" id="user_id">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">ユーザー名 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="username" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">姓 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name_2nd" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">名 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name_1st" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">LINE ID</label>
                    <input type="text" class="form-control" id="line_id">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">学生ID</label>
                    <input type="text" class="form-control" id="student_id">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">パスワード<span class="text-muted small"> (編集時は変更する場合のみ入力)</span></label>
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
                <button type="button" class="btn btn-secondary" id="cancel-btn" style="display: none;" onclick="resetForm()">
                    <i class="fas fa-times me-1"></i>キャンセル
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ユーザー一覧 -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-users me-2"></i>ユーザー一覧</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ユーザー名</th>
                        <th>氏名</th>
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
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const token = localStorage.getItem('token');
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');

    // 管理者権限確認
    if (!token || !currentUser.is_admin) {
        window.location.href = '/login';
    }

    // ユーザー一覧を読み込み
    async function loadUsers() {
        try {
            const response = await fetch('/api/auth/users', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                displayUsers(result.data);
            }
        } catch (error) {
            console.error('ユーザーの読み込みエラー:', error);
        }
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
                <td>${user.line_id || '-'}</td>
                <td>${user.student_id || '-'}</td>
                <td>${user.status || '-'}</td>
                <td>${user.is_admin ? '○' : '×'}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editUser(${user.id})">
                        <i class="fas fa-edit"></i> 編集
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})" 
                        ${user.id === currentUser.id ? 'disabled title="自分自身は削除できません"' : ''}>
                        <i class="fas fa-trash"></i> 削除
                    </button>
                </td>
            </tr>
        `).join('');
    }

    // ユーザーを編集
    async function editUser(id) {
        try {
            const response = await fetch('/api/auth/users', {
                headers: {
                    'Authorization': `Bearer ${token}`,
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
                    document.getElementById('name_1st').value = user.name_1st || '';
                    document.getElementById('line_id').value = user.line_id || '';
                    document.getElementById('student_id').value = user.student_id || '';
                    document.getElementById('status').value = user.status || 'student';
                    document.getElementById('is_admin').value = user.is_admin ? '1' : '0';
                    document.getElementById('password').value = '';
                    
                    document.getElementById('form-title').innerHTML = '<i class="fas fa-edit me-2"></i>ユーザー編集';
                    document.getElementById('submit-btn').innerHTML = '<i class="fas fa-save me-1"></i>更新';
                    document.getElementById('cancel-btn').style.display = 'inline-block';
                    
                    window.scrollTo({ top: 0, behavior: 'smooth' });
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
        const formData = {
            username: document.getElementById('username').value,
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
                url = `/api/auth/users/${userId}`;
                method = 'PUT';
            } else {
                // 新規登録（registerエンドポイントを使用）
                showAlert('warning', '新規ユーザー登録はLINEログインから行ってください');
                return;
            }

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (response.ok) {
                showAlert('success', result.message || 'ユーザーを保存しました');
                resetForm();
                loadUsers();
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
        if (id === currentUser.id) {
            showAlert('warning', '自分自身を削除することはできません');
            return;
        }

        if (!confirm('本当にこのユーザーを削除しますか？')) {
            return;
        }

        try {
            const response = await fetch(`/api/auth/users/${id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${token}`,
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
    loadUsers();
</script>
@endsection
