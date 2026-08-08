@extends('layouts.master_layout')

@section('title', $pageTitle ?? 'ポイント履歴（個人）')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom gap-2">
    <h1 class="h2 mb-0">{{ $pageTitle ?? 'ポイント履歴（個人）' }}</h1>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ $pointsIndexUrl ?? '/master/points' }}" class="btn btn-outline-secondary btn-sm">申請一覧TOP</a>
        <a href="{{ $personalHistoryUrl ?? '/master/points/history/personal' }}" class="btn btn-primary btn-sm">個人履歴</a>
        <a href="{{ $periodHistoryUrl ?? '/master/points/history/period' }}" class="btn btn-outline-primary btn-sm">日次 / 月次履歴</a>
    </div>
</div>

<div id="alert-area"></div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-user-clock me-2"></i>個人ポイント履歴</h5>
    </div>
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-12 col-md-3">
                <label class="form-label" for="history-user-search">ユーザー検索</label>
                <input id="history-user-search" class="form-control" type="text" placeholder="名前・ユーザー名">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label" for="history-user-id">対象ユーザー</label>
                <select id="history-user-id" class="form-select"></select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label" for="personal-start-date">開始日</label>
                <input id="personal-start-date" class="form-control" type="date">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label" for="personal-end-date">終了日</label>
                <input id="personal-end-date" class="form-control" type="date">
            </div>
            <div class="col-6 col-md-1">
                <label class="form-label" for="personal-limit">件数</label>
                <input id="personal-limit" class="form-control" type="number" min="1" max="500" value="100">
            </div>
            <div class="col-6 col-md-1 d-grid align-self-end">
                <button class="btn btn-primary" type="button" onclick="loadPersonalHistory()">表示</button>
            </div>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-12 col-md-4">
                <div class="p-2 border rounded bg-light">
                    <div class="small text-muted">対象ユーザー</div>
                    <div id="personal-user-label" class="fw-semibold">-</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="p-2 border rounded bg-light">
                    <div class="small text-muted">履歴件数</div>
                    <div id="personal-count" class="fw-semibold">0 件</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="p-2 border rounded bg-light">
                    <div class="small text-muted">合計ポイント</div>
                    <div id="personal-total" class="fw-semibold">0 pts</div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead>
                    <tr>
                        <th>日時</th>
                        <th>種別</th>
                        <th>増減</th>
                        <th>残高(前)</th>
                        <th>残高(後)</th>
                        <th>状態</th>
                        <th>説明</th>
                    </tr>
                </thead>
                <tbody id="personal-history-body">
                    <tr><td colspan="7" class="text-center">ユーザーを選択して表示してください</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const alertArea = document.getElementById('alert-area');
    const personalBody = document.getElementById('personal-history-body');

    function showAlert(type, message) {
        alertArea.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        setTimeout(() => alertArea.innerHTML = '', 5000);
    }

    function getAuthHeaders() {
        const token = localStorage.getItem('token');
        return {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'Authorization': token ? `Bearer ${token}` : ''
        };
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>'"]/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        })[char]);
    }

    function formatDateTime(iso) {
        return iso ? new Date(iso).toLocaleString('ja-JP') : '-';
    }

    async function loadHistoryUsers() {
        const keyword = document.getElementById('history-user-search').value.trim();
        const params = new URLSearchParams();
        if (keyword !== '') params.set('keyword', keyword);
        params.set('limit', '50');

        const select = document.getElementById('history-user-id');
        const selectedBefore = select.value;

        try {
            const response = await fetch(`/api/master/points/history/users?${params.toString()}`, { headers: getAuthHeaders() });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'ユーザー候補の取得に失敗しました');

            const rows = result.data || [];
            if (!rows.length) {
                select.innerHTML = '<option value="">履歴ユーザーなし</option>';
                return;
            }

            select.innerHTML = rows.map((row, index) => {
                const name = row.user?.display_name || row.user?.username || `ID:${row.user_id}`;
                const optionValue = String(row.user_id);
                const selected = selectedBefore === optionValue || (!selectedBefore && index === 0) ? 'selected' : '';
                return `<option value="${optionValue}" ${selected}>${escapeHtml(name)} (ID:${row.user_id})</option>`;
            }).join('');
        } catch (error) {
            showAlert('danger', error.message);
        }
    }

    async function loadPersonalHistory() {
        const userId = document.getElementById('history-user-id').value;
        if (!userId) {
            showAlert('warning', '対象ユーザーを選択してください。');
            return;
        }

        const params = new URLSearchParams({ user_id: userId });
        const start = document.getElementById('personal-start-date').value;
        const end = document.getElementById('personal-end-date').value;
        const limit = document.getElementById('personal-limit').value;
        if (start) params.set('start_date', start);
        if (end) params.set('end_date', end);
        if (limit) params.set('limit', limit);

        try {
            const response = await fetch(`/api/master/points/history/personal?${params.toString()}`, { headers: getAuthHeaders() });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || '個人履歴の取得に失敗しました');

            const data = result.data || {};
            const user = data.user || {};
            const rows = data.transactions || [];

            document.getElementById('personal-user-label').textContent = `${user.display_name || user.username || '-'} (ID:${user.id || '-'})`;
            document.getElementById('personal-count').textContent = `${Number(data.summary?.tx_count || 0).toLocaleString()} 件`;
            document.getElementById('personal-total').textContent = `${Number(data.summary?.total_amount || 0).toLocaleString()} pts`;

            if (!rows.length) {
                personalBody.innerHTML = '<tr><td colspan="7" class="text-center">履歴はありません</td></tr>';
                return;
            }

            personalBody.innerHTML = rows.map((tx) => `
                <tr>
                    <td>${formatDateTime(tx.created_at)}</td>
                    <td>${escapeHtml(tx.transaction_type || '-')}</td>
                    <td>${Number(tx.amount || 0).toLocaleString()} pts</td>
                    <td>${Number(tx.balance_before || 0).toLocaleString()}</td>
                    <td>${Number(tx.balance_after || 0).toLocaleString()}</td>
                    <td>${escapeHtml(tx.status || '-')}</td>
                    <td>${escapeHtml(tx.description || '-')}</td>
                </tr>
            `).join('');
        } catch (error) {
            showAlert('danger', error.message);
            personalBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">読み込みに失敗しました</td></tr>';
        }
    }

    document.getElementById('history-user-search').addEventListener('change', loadHistoryUsers);

    loadHistoryUsers().then(loadPersonalHistory);
</script>
@endsection
