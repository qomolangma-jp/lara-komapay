@extends('layouts.master_layout')

@section('title', 'クラス管理')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">クラス管理</h1>
</div>

<div id="alert-area"></div>

<div class="card mb-3">
    <div class="card-body">
        <form id="classProfileForm" class="row g-3 align-items-end" onsubmit="saveClassProfile(event)">
            <div class="col-md-3">
                <label class="form-label">ユーザーID</label>
                <input type="text" class="form-control" id="userId" maxlength="50" required placeholder="例: 100001">
            </div>
            <div class="col-md-2">
                <label class="form-label">クラス</label>
                <input type="text" class="form-control" id="classCode" maxlength="2" pattern="[0-9]{2}" required placeholder="例: 18">
                <small class="text-muted">学年と組を2桁で入力</small>
            </div>
            <div class="col-md-2">
                <label class="form-label">番号</label>
                <input type="number" class="form-control" id="studentNumber" min="1" max="99" required placeholder="例: 1">
            </div>
            <div class="col-md-3">
                <label class="form-label">名前</label>
                <input type="text" class="form-control" id="studentName" maxlength="100" required placeholder="例: 山田 太郎">
            </div>
            <div class="col-md-2 d-grid gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>保存</button>
                <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">入力クリア</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>クラス一覧</h5>
        <div class="input-group" style="max-width: 360px;">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
            <input type="search" class="form-control" id="searchInput" placeholder="ユーザーID・クラス・名前で検索">
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ユーザーID</th>
                        <th>クラス</th>
                        <th>番号</th>
                        <th>名前</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="classProfileTableBody">
                    <tr><td colspan="5" class="text-center">読み込み中...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let classProfiles = [];

    function showAlert(type, message) {
        document.getElementById('alert-area').innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    }

    function resetForm() {
        document.getElementById('classProfileForm').reset();
        document.getElementById('userId').focus();
    }

    function normalizeText(value) {
        return String(value ?? '').toLowerCase();
    }

    function getFilteredProfiles() {
        const search = normalizeText(document.getElementById('searchInput').value.trim());
        if (!search) {
            return classProfiles;
        }

        return classProfiles.filter((item) => {
            return [item.user_id, item.class_code, item.student_name]
                .some((field) => normalizeText(field).includes(search));
        });
    }

    function renderTable() {
        const tbody = document.getElementById('classProfileTableBody');
        const items = getFilteredProfiles();

        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">データがありません</td></tr>';
            return;
        }

        tbody.innerHTML = items.map((item) => `
            <tr>
                <td>${item.user_id}</td>
                <td>${item.class_code}</td>
                <td>${item.student_number}</td>
                <td>${item.student_name}</td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary" onclick="editClassProfile(${item.id})">編集</button>
                        <button type="button" class="btn btn-outline-danger" onclick="deleteClassProfile(${item.id})">削除</button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function editClassProfile(id) {
        const profile = classProfiles.find((item) => item.id === id);
        if (!profile) {
            return;
        }

        document.getElementById('userId').value = profile.user_id;
        document.getElementById('classCode').value = profile.class_code;
        document.getElementById('studentNumber').value = profile.student_number;
        document.getElementById('studentName').value = profile.student_name;
        document.getElementById('userId').focus();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    async function loadClassProfiles() {
        try {
            const response = await fetch('/api/master/class-profiles', {
                headers: { 'Accept': 'application/json' }
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                showAlert('danger', result.message || 'クラス情報の取得に失敗しました。');
                return;
            }

            classProfiles = Array.isArray(result.data) ? result.data : [];
            renderTable();
        } catch (error) {
            console.error(error);
            showAlert('danger', 'クラス情報の取得中にエラーが発生しました。');
        }
    }

    async function saveClassProfile(event) {
        event.preventDefault();

        const payload = {
            user_id: document.getElementById('userId').value.trim(),
            class_code: document.getElementById('classCode').value.trim(),
            student_number: Number(document.getElementById('studentNumber').value),
            student_name: document.getElementById('studentName').value.trim(),
        };

        if (!/^[0-9]{2}$/.test(payload.class_code)) {
            showAlert('warning', 'クラスは2桁の数字で入力してください。');
            return;
        }

        if (!payload.user_id || !payload.student_name || !payload.student_number) {
            showAlert('warning', '必須項目を入力してください。');
            return;
        }

        try {
            const response = await fetch('/api/master/class-profiles', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                showAlert('danger', result.message || '保存に失敗しました。');
                return;
            }

            showAlert('success', result.message || '保存しました。');
            resetForm();
            await loadClassProfiles();
        } catch (error) {
            console.error(error);
            showAlert('danger', '保存中にエラーが発生しました。');
        }
    }

    async function deleteClassProfile(id) {
        if (!confirm('このクラス情報を削除しますか？')) {
            return;
        }

        try {
            const response = await fetch(`/api/master/class-profiles/${id}`, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json' }
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                showAlert('danger', result.message || '削除に失敗しました。');
                return;
            }

            showAlert('success', result.message || '削除しました。');
            await loadClassProfiles();
        } catch (error) {
            console.error(error);
            showAlert('danger', '削除中にエラーが発生しました。');
        }
    }

    document.getElementById('searchInput').addEventListener('input', renderTable);

    loadClassProfiles();
</script>
@endsection
