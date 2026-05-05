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
            <div class="row g-2 mb-3 align-items-end">
                    <div class="col-md-3">
                    <label class="form-label mb-1">検索</label>
                    <input type="search" id="userSearchInput" class="form-control" placeholder="ユーザーID・氏名・学生IDで検索">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">ステータス</label>
                    <select id="userStatusFilter" class="form-select">
                        <option value="">すべて</option>
                        <option value="student">student</option>
                        <option value="teacher">teacher</option>
                        <option value="seller">seller</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">権限</label>
                    <select id="userAdminFilter" class="form-select">
                        <option value="">すべて</option>
                        <option value="1">管理者</option>
                        <option value="0">一般</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">並び替え</label>
                    <select id="userSortSelect" class="form-select">
                        <option value="id-desc">ID 降順</option>
                        <option value="id-asc">ID 昇順</option>
                        <option value="username-asc">ユーザーID 昇順</option>
                        <option value="username-desc">ユーザーID 降順</option>
                        <option value="created-desc">登録日 新しい順</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label mb-1">件数</label>
                    <select id="userPageSize" class="form-select">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="dropdown w-100">
                        <button class="btn btn-outline-secondary dropdown-toggle w-100" type="button" data-bs-toggle="dropdown" aria-label="表示列"><i class="fas fa-th-large" aria-hidden="true"></i></button>
                        <div class="dropdown-menu p-3 w-100" style="min-width: 240px;">
                            <div class="form-check"><input class="form-check-input user-column-toggle" type="checkbox" data-column="id" id="user-col-id" checked><label class="form-check-label" for="user-col-id">ID</label></div>
                            <div class="form-check"><input class="form-check-input user-column-toggle" type="checkbox" data-column="username" id="user-col-username" checked><label class="form-check-label" for="user-col-username">ユーザーID</label></div>
                            <div class="form-check"><input class="form-check-input user-column-toggle" type="checkbox" data-column="name" id="user-col-name" checked><label class="form-check-label" for="user-col-name">氏名</label></div>
                            <div class="form-check"><input class="form-check-input user-column-toggle" type="checkbox" data-column="shop" id="user-col-shop" checked><label class="form-check-label" for="user-col-shop">店舗名</label></div>
                            <div class="form-check"><input class="form-check-input user-column-toggle" type="checkbox" data-column="line" id="user-col-line" checked><label class="form-check-label" for="user-col-line">LINE ID</label></div>
                            <div class="form-check"><input class="form-check-input user-column-toggle" type="checkbox" data-column="student" id="user-col-student" checked><label class="form-check-label" for="user-col-student">学生ID</label></div>
                            <div class="form-check"><input class="form-check-input user-column-toggle" type="checkbox" data-column="status" id="user-col-status" checked><label class="form-check-label" for="user-col-status">ステータス</label></div>
                            <div class="form-check"><input class="form-check-input user-column-toggle" type="checkbox" data-column="admin" id="user-col-admin" checked><label class="form-check-label" for="user-col-admin">管理者</label></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th data-column="id">ID</th>
                            <th data-column="username">ユーザーID</th>
                            <th data-column="name">氏名</th>
                            <th data-column="shop">店舗名</th>
                            <th data-column="line">LINE ID</th>
                            <th data-column="student">学生ID</th>
                            <th data-column="status">ステータス</th>
                            <th data-column="admin">管理者</th>
                            <th data-column="actions">操作</th>
                        </tr>
                    </thead>
                    <tbody id="users-table-body">
                        <tr>
                            <td colspan="9" class="text-center">読み込み中...</td>
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
                        <label class="form-label">ユーザーID（メールアドレス） <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="username" required placeholder="例: user@example.com">
                        <small class="form-text text-muted">メールアドレスの形式で入力してください（例: user@example.com）</small>
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
    let allUsers = [];
    let filteredUsers = [];
    let userCurrentPage = 1;
    let userPageSize = 10;
    let userSort = 'id-desc';
    const userVisibleColumns = {
        id: true,
        username: true,
        name: true,
        shop: true,
        line: true,
        student: true,
        status: true,
        admin: true,
        actions: true,
    };

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

    function normalizeText(value) {
        return String(value ?? '').toLowerCase();
    }

    function getUserFullName(user) {
        return `${user.name_2nd || ''} ${user.name_1st || ''}`.trim();
    }

    function getUserDisplayName(user) {
        return getUserFullName(user) || user.display_name || user.name || user.username || '-';
    }

    function syncUserColumnVisibility() {
        document.querySelectorAll('table [data-column]').forEach((cell) => {
            const column = cell.getAttribute('data-column');
            const visible = userVisibleColumns[column] !== false;
            cell.classList.toggle('d-none', !visible);
        });
    }

    function renderUserPagination() {
        const paginationDiv = document.getElementById('user-pagination');
        const totalPages = Math.max(1, Math.ceil(filteredUsers.length / userPageSize));

        if (totalPages <= 1) {
            paginationDiv.innerHTML = '';
            return;
        }

        const start = (userCurrentPage - 1) * userPageSize + 1;
        const end = Math.min(filteredUsers.length, userCurrentPage * userPageSize);
        paginationDiv.innerHTML = `
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                <div class="text-muted small">${filteredUsers.length}件中 ${start}-${end}件を表示</div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item ${userCurrentPage === 1 ? 'disabled' : ''}"><button class="page-link" type="button" onclick="goUserPage(${userCurrentPage - 1})">前へ</button></li>
                        <li class="page-item active"><span class="page-link">${userCurrentPage} / ${totalPages}</span></li>
                        <li class="page-item ${userCurrentPage === totalPages ? 'disabled' : ''}"><button class="page-link" type="button" onclick="goUserPage(${userCurrentPage + 1})">次へ</button></li>
                    </ul>
                </nav>
            </div>
        `;
    }

    function goUserPage(page) {
        const totalPages = Math.max(1, Math.ceil(filteredUsers.length / userPageSize));
        userCurrentPage = Math.max(1, Math.min(page, totalPages));
        renderUsers(filteredUsers);
        renderUserPagination();
        syncUserColumnVisibility();
    }

    function renderUsers(users) {
        const tbody = document.getElementById('users-table-body');
        const startIndex = (userCurrentPage - 1) * userPageSize;
        const visibleUsers = users.slice(startIndex, startIndex + userPageSize);

        if (visibleUsers.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center">ユーザーが見つかりません</td></tr>';
            renderUserPagination();
            return;
        }

        tbody.innerHTML = visibleUsers.map(user => `
            <tr>
                <td data-column="id">${user.id}</td>
                <td data-column="username">${user.username || ''}</td>
                <td data-column="name">${getUserDisplayName(user)}</td>
                <td data-column="shop">${user.shop_name || '-'}</td>
                <td data-column="line">${user.line_id || '-'}</td>
                <td data-column="student">${user.student_id || '-'}</td>
                <td data-column="status"><span class="badge bg-secondary">${user.status || '-'}</span></td>
                <td data-column="admin">${user.is_admin ? '○' : '×'}</td>
                <td data-column="actions">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">操作</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><button class="dropdown-item" type="button" onclick="editUser(${user.id})"><i class="fas fa-edit me-2"></i>編集</button></li>
                            <li><button class="dropdown-item" type="button" onclick="resetPassword(${user.id})"><i class="fas fa-key me-2"></i>パスワード再発行（LINE送信）</button></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><button class="dropdown-item text-danger" type="button" onclick="deleteUser(${user.id})"><i class="fas fa-trash me-2"></i>削除</button></li>
                        </ul>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function applyUserFilters() {
        const searchTerm = normalizeText(document.getElementById('userSearchInput')?.value || '');
        const statusFilter = document.getElementById('userStatusFilter')?.value || '';
        const adminFilter = document.getElementById('userAdminFilter')?.value || '';

        filteredUsers = allUsers.filter((user) => {
            const matchesSearch = !searchTerm || [user.username, getUserDisplayName(user), user.student_id, user.shop_name]
                .some((field) => normalizeText(field).includes(searchTerm));
            const matchesStatus = !statusFilter || user.status === statusFilter;
            const matchesAdmin = !adminFilter || String(user.is_admin ? '1' : '0') === adminFilter;
            return matchesSearch && matchesStatus && matchesAdmin;
        });

        const [sortKey, sortDirection] = (userSort || 'id-desc').split('-');
        filteredUsers.sort((left, right) => {
            let a;
            let b;
            switch (sortKey) {
                case 'username':
                    a = normalizeText(left.username || '');
                    b = normalizeText(right.username || '');
                    break;
                case 'created':
                    a = new Date(left.created_at || 0).getTime();
                    b = new Date(right.created_at || 0).getTime();
                    break;
                case 'id':
                default:
                    a = Number(left.id || 0);
                    b = Number(right.id || 0);
                    break;
            }
            if (a < b) return sortDirection === 'desc' ? 1 : -1;
            if (a > b) return sortDirection === 'desc' ? -1 : 1;
            return 0;
        });

        const totalPages = Math.max(1, Math.ceil(filteredUsers.length / userPageSize));
        userCurrentPage = Math.min(userCurrentPage, totalPages);
        renderUsers(filteredUsers);
        renderUserPagination();
        syncUserColumnVisibility();
    }

    function attachUserTableControls() {
        document.getElementById('userSearchInput').addEventListener('input', () => {
            userCurrentPage = 1;
            applyUserFilters();
        });
        document.getElementById('userStatusFilter').addEventListener('change', () => {
            userCurrentPage = 1;
            applyUserFilters();
        });
        document.getElementById('userAdminFilter').addEventListener('change', () => {
            userCurrentPage = 1;
            applyUserFilters();
        });
        document.getElementById('userSortSelect').addEventListener('change', (event) => {
            userSort = event.target.value;
            userCurrentPage = 1;
            applyUserFilters();
        });
        document.getElementById('userPageSize').addEventListener('change', (event) => {
            userPageSize = parseInt(event.target.value, 10) || 10;
            userCurrentPage = 1;
            applyUserFilters();
        });
        document.querySelectorAll('.user-column-toggle').forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                const column = checkbox.getAttribute('data-column');
                userVisibleColumns[column] = checkbox.checked;
                syncUserColumnVisibility();
            });
        });
    }

    async function loadUsers(page = 1) {
        try {
            const response = await fetch(`/api/master/users?per_page=1000&page=${page}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                allUsers = Array.isArray(result.data) ? result.data : [];
                displayUsers(allUsers);
            } else {
                const errorData = await response.text();
                console.error('API Error Status:', response.status);
                console.error('API Error Response:', errorData);
                showAlert('danger', `ユーザー情報の読み込みに失敗しました (${response.status})`);
                document.getElementById('users-table-body').innerHTML = 
                    '<tr><td colspan="9" class="text-center text-danger">エラーが発生しました。コンソールを確認してください。</td></tr>';
            }
        } catch (error) {
            console.error('ユーザーの読み込みエラー:', error);
            showAlert('danger', 'ネットワークエラーが発生しました');
        }
    }
    
    function updatePagination() {
        const paginationDiv = document.getElementById('user-pagination');
        if (!paginationDiv) return;
        renderUserPagination();
    }

    // ユーザー一覧を表示
    function displayUsers(users) {
        allUsers = Array.isArray(users) ? users : [];
        filteredUsers = [...allUsers];
        userCurrentPage = 1;
        applyUserFilters();
    }

    // ユーザーを編集
    async function editUser(id) {
        try {
            const user = allUsers.find(item => item.id === id);

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
        // メールアドレス形式チェック
        const email = (usernameInput.value || '').trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email || !emailRegex.test(email)) {
            showAlert('warning', 'ユーザーIDには有効なメールアドレスを入力してください（例: user@example.com）');
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

    // パスワード再発行を実行してLINEへ送信
    async function resetPassword(id) {
        if (!confirm('本当にこのユーザーのパスワードを再発行してLINEで送信しますか？')) {
            return;
        }

        try {
            const response = await fetch(`/api/master/users/${id}/reset-password`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (response.ok) {
                showAlert('success', result.message || 'パスワードを再発行し、LINEへ送信しました');
            } else {
                showAlert('danger', result.message || 'パスワード再発行に失敗しました');
                console.error('resetPassword error', result);
            }
        } catch (error) {
            console.error('resetPassword exception', error);
            showAlert('danger', 'ネットワークエラーが発生しました');
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

    attachUserTableControls();
    switchToListView();
    loadUsers();
</script>
@endsection
