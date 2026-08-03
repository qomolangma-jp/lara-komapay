@extends('layouts.master_layout')

@section('title', 'クラス管理')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">クラス管理</h1>
</div>

<div id="alert-area"></div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-7">
                <label class="form-label">CSV一括登録（約1000件対応）</label>
                <input type="file" class="form-control" id="csvFile" accept=".csv,text/csv,text/plain">
                <small class="text-muted">1列目: student_id、2列目: class（例: 2025281,3-2）。ヘッダー行あり/なし両対応。</small>
            </div>
            <div class="col-md-5 d-flex gap-2">
                <button type="button" class="btn btn-primary" onclick="importCsv()"><i class="fas fa-file-upload me-1"></i>CSV取込</button>
                <button type="button" class="btn btn-outline-secondary" onclick="downloadTemplate()"><i class="fas fa-download me-1"></i>テンプレート</button>
                <a href="/master/users" class="btn btn-outline-success"><i class="fas fa-user-cog me-1"></i>個別設定へ</a>
            </div>
        </div>
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
                        <th>student_id</th>
                        <th>クラス</th>
                        <th>名前</th>
                        <th>ユーザーID</th>
                    </tr>
                </thead>
                <tbody id="classProfileTableBody">
                    <tr><td colspan="4" class="text-center">読み込み中...</td></tr>
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

    function normalizeText(value) {
        return String(value ?? '').toLowerCase();
    }

    function getFilteredProfiles() {
        const search = normalizeText(document.getElementById('searchInput').value.trim());
        if (!search) {
            return classProfiles;
        }

        return classProfiles.filter((item) => {
            return [item.student_id, item.class, item.student_name, item.username]
                .some((field) => normalizeText(field).includes(search));
        });
    }

    function renderTable() {
        const tbody = document.getElementById('classProfileTableBody');
        const items = getFilteredProfiles();

        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">データがありません</td></tr>';
            return;
        }

        tbody.innerHTML = items.map((item) => `
            <tr>
                <td>${item.student_id}</td>
                <td>${item.class}</td>
                <td>${item.student_name || '-'}</td>
                <td>${item.username || '-'}</td>
            </tr>
        `).join('');
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

    async function importCsv() {
        const fileInput = document.getElementById('csvFile');
        const file = fileInput.files[0];
        if (!file) {
            showAlert('warning', 'CSVファイルを選択してください。');
            return;
        }

        const formData = new FormData();
        formData.append('file', file);

        try {
            const response = await fetch('/api/master/class-profiles/import-csv', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                showAlert('danger', result.message || 'CSV取込に失敗しました。');
                return;
            }

            showAlert('success', result.message || 'CSVを取込ました。');
            fileInput.value = '';
            await loadClassProfiles();
        } catch (error) {
            console.error(error);
            showAlert('danger', 'CSV取込中にエラーが発生しました。');
        }
    }

    function downloadTemplate() {
        const lines = [
            'student_id,class',
            '2025281,3-2',
            '2025282,3-2',
            '2025283,3-1'
        ];
        const blob = new Blob(['\uFEFF' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = 'class_profiles_template.csv';
        document.body.appendChild(anchor);
        anchor.click();
        anchor.remove();
        URL.revokeObjectURL(url);
    }

    document.getElementById('searchInput').addEventListener('input', renderTable);

    loadClassProfiles();
</script>
@endsection
