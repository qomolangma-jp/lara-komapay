@extends('layouts.master_layout')

@section('title', 'ポイント購入管理')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">ポイント購入管理</h1>
</div>

<div id="alert-area"></div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-coins me-2"></i>チャージ申請一覧</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ユーザー</th>
                        <th>申請ポイント</th>
                        <th>ステータス</th>
                        <th>申請日時</th>
                        <th>承認者</th>
                        <th>承認日時</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="points-requests-body">
                    <tr><td colspan="8" class="text-center">読み込み中...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
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

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>日次 / 月次ポイント履歴（全体）</h5>
    </div>
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-12 col-md-2">
                <label class="form-label" for="period-mode">集計モード</label>
                <select id="period-mode" class="form-select" onchange="togglePeriodInputs()">
                    <option value="daily">1日</option>
                    <option value="monthly">1か月</option>
                </select>
            </div>
            <div class="col-6 col-md-3" id="period-date-wrap">
                <label class="form-label" for="period-date">対象日</label>
                <input id="period-date" class="form-control" type="date">
            </div>
            <div class="col-6 col-md-3 d-none" id="period-month-wrap">
                <label class="form-label" for="period-month">対象月</label>
                <input id="period-month" class="form-control" type="month">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label" for="period-limit">詳細件数</label>
                <input id="period-limit" class="form-control" type="number" min="1" max="500" value="200">
            </div>
            <div class="col-6 col-md-2 d-grid align-self-end">
                <button class="btn btn-primary" type="button" onclick="loadPeriodHistory()">集計</button>
            </div>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-6 col-md-4">
                <div class="p-2 border rounded bg-light">
                    <div class="small text-muted">対象期間</div>
                    <div id="period-label" class="fw-semibold">-</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="p-2 border rounded bg-light">
                    <div class="small text-muted">履歴件数</div>
                    <div id="period-count" class="fw-semibold">0 件</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="p-2 border rounded bg-light">
                    <div class="small text-muted">合計ポイント</div>
                    <div id="period-total" class="fw-semibold">0 pts</div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-xl-6">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead>
                            <tr>
                                <th>取引種別</th>
                                <th>件数</th>
                                <th>合計</th>
                            </tr>
                        </thead>
                        <tbody id="period-type-body">
                            <tr><td colspan="3" class="text-center">データなし</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-12 col-xl-6">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ユーザー</th>
                                <th>件数</th>
                                <th>合計</th>
                            </tr>
                        </thead>
                        <tbody id="period-user-body">
                            <tr><td colspan="3" class="text-center">データなし</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-3 table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead>
                    <tr>
                        <th>日時</th>
                        <th>ユーザー</th>
                        <th>種別</th>
                        <th>増減</th>
                        <th>残高(前)</th>
                        <th>残高(後)</th>
                        <th>説明</th>
                    </tr>
                </thead>
                <tbody id="period-transactions-body">
                    <tr><td colspan="7" class="text-center">集計を実行してください</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const alertArea = document.getElementById('alert-area');
    const requestsBody = document.getElementById('points-requests-body');
    const personalBody = document.getElementById('personal-history-body');
    const periodTypeBody = document.getElementById('period-type-body');
    const periodUserBody = document.getElementById('period-user-body');
    const periodTransactionsBody = document.getElementById('period-transactions-body');

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

    function formatStatus(status) {
        const normalized = String(status || '').toLowerCase();
        if (normalized === 'pending') return '申請中';
        if (normalized === 'approved') return '承認済み';
        if (normalized === 'rejected') return '却下';
        return status || '-';
    }

    function togglePeriodInputs() {
        const mode = document.getElementById('period-mode').value;
        const dateWrap = document.getElementById('period-date-wrap');
        const monthWrap = document.getElementById('period-month-wrap');
        dateWrap.classList.toggle('d-none', mode !== 'daily');
        monthWrap.classList.toggle('d-none', mode !== 'monthly');
    }

    async function loadRequests() {
        try {
            const response = await fetch('/api/master/points/requests', { headers: getAuthHeaders() });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || '読み込みに失敗しました');

            const rows = result.data || [];
            if (!rows.length) {
                requestsBody.innerHTML = '<tr><td colspan="8" class="text-center">申請はありません</td></tr>';
                return;
            }

            requestsBody.innerHTML = rows.map((request) => {
                const isPending = String(request.status || '').toLowerCase() === 'pending';
                const actionHtml = isPending
                    ? `<div class="btn-group btn-group-sm">
                            <button class="btn btn-success" onclick="approveRequest(${request.id})">承認</button>
                            <button class="btn btn-danger" onclick="rejectRequest(${request.id})">却下</button>
                        </div>`
                    : '<span class="text-muted small">処理済み</span>';

                return `
                    <tr>
                        <td>${request.id}</td>
                        <td>${escapeHtml(request.user?.display_name || request.user?.name || request.user?.username || '-')}</td>
                        <td>${Number(request.amount || 0).toLocaleString()} pts</td>
                        <td><span class="badge bg-secondary">${escapeHtml(formatStatus(request.status))}</span></td>
                        <td>${formatDateTime(request.created_at)}</td>
                        <td>${escapeHtml(request.approver_name || request.approver?.display_name || request.approver?.username || '-')}</td>
                        <td>${formatDateTime(request.approved_at)}</td>
                        <td>${actionHtml}</td>
                    </tr>
                `;
            }).join('');
        } catch (error) {
            console.error(error);
            requestsBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">読み込みに失敗しました</td></tr>';
        }
    }

    async function approveRequest(id) {
        try {
            const response = await fetch(`/api/master/points/requests/${id}/approve`, {
                method: 'POST',
                headers: getAuthHeaders()
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || '承認に失敗しました');
            showAlert('success', '承認しました');
            loadRequests();
        } catch (error) {
            showAlert('danger', error.message);
        }
    }

    async function rejectRequest(id) {
        try {
            const response = await fetch(`/api/master/points/requests/${id}/reject`, {
                method: 'POST',
                headers: getAuthHeaders()
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || '却下に失敗しました');
            showAlert('warning', '却下しました');
            loadRequests();
        } catch (error) {
            showAlert('danger', error.message);
        }
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

    async function loadPeriodHistory() {
        const mode = document.getElementById('period-mode').value;
        const date = document.getElementById('period-date').value;
        const month = document.getElementById('period-month').value;
        const limit = document.getElementById('period-limit').value;

        const params = new URLSearchParams({ mode });
        if (mode === 'daily' && date) params.set('date', date);
        if (mode === 'monthly' && month) params.set('month', month);
        if (limit) params.set('limit', limit);

        try {
            const response = await fetch(`/api/master/points/history/period?${params.toString()}`, { headers: getAuthHeaders() });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || '期間履歴の取得に失敗しました');

            const data = result.data || {};
            const byType = data.by_type || [];
            const byUser = data.by_user || [];
            const txs = data.transactions || [];

            document.getElementById('period-label').textContent = data.label || '-';
            document.getElementById('period-count').textContent = `${Number(data.summary?.tx_count || 0).toLocaleString()} 件`;
            document.getElementById('period-total').textContent = `${Number(data.summary?.total_amount || 0).toLocaleString()} pts`;

            periodTypeBody.innerHTML = byType.length
                ? byType.map((row) => `
                    <tr>
                        <td>${escapeHtml(row.transaction_type || '-')}</td>
                        <td>${Number(row.tx_count || 0).toLocaleString()} 件</td>
                        <td>${Number(row.total_amount || 0).toLocaleString()} pts</td>
                    </tr>
                `).join('')
                : '<tr><td colspan="3" class="text-center">データなし</td></tr>';

            periodUserBody.innerHTML = byUser.length
                ? byUser.map((row) => `
                    <tr>
                        <td>${escapeHtml(row.display_name || row.username || '-')} (ID:${row.user_id})</td>
                        <td>${Number(row.tx_count || 0).toLocaleString()} 件</td>
                        <td>${Number(row.total_amount || 0).toLocaleString()} pts</td>
                    </tr>
                `).join('')
                : '<tr><td colspan="3" class="text-center">データなし</td></tr>';

            periodTransactionsBody.innerHTML = txs.length
                ? txs.map((tx) => `
                    <tr>
                        <td>${formatDateTime(tx.created_at)}</td>
                        <td>${escapeHtml(tx.user_display_name || tx.user_username || '-')} (ID:${tx.user_id})</td>
                        <td>${escapeHtml(tx.transaction_type || '-')}</td>
                        <td>${Number(tx.amount || 0).toLocaleString()} pts</td>
                        <td>${Number(tx.balance_before || 0).toLocaleString()}</td>
                        <td>${Number(tx.balance_after || 0).toLocaleString()}</td>
                        <td>${escapeHtml(tx.description || '-')}</td>
                    </tr>
                `).join('')
                : '<tr><td colspan="7" class="text-center">データなし</td></tr>';
        } catch (error) {
            showAlert('danger', error.message);
            periodTypeBody.innerHTML = '<tr><td colspan="3" class="text-center text-danger">読み込みに失敗しました</td></tr>';
            periodUserBody.innerHTML = '<tr><td colspan="3" class="text-center text-danger">読み込みに失敗しました</td></tr>';
            periodTransactionsBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">読み込みに失敗しました</td></tr>';
        }
    }

    document.getElementById('history-user-search').addEventListener('change', loadHistoryUsers);

    (function initializeFilters() {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        document.getElementById('period-date').value = `${yyyy}-${mm}-${dd}`;
        document.getElementById('period-month').value = `${yyyy}-${mm}`;
    })();

    togglePeriodInputs();
    loadRequests();
    loadHistoryUsers().then(loadPersonalHistory);
    loadPeriodHistory();
</script>
@endsection
