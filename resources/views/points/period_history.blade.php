@extends('layouts.master_layout')

@section('title', $pageTitle ?? 'ポイント履歴（日次 / 月次）')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom gap-2">
    <h1 class="h2 mb-0">{{ $pageTitle ?? 'ポイント履歴（日次 / 月次）' }}</h1>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ $pointsIndexUrl ?? '/master/points' }}" class="btn btn-outline-secondary btn-sm">申請一覧TOP</a>
        <a href="{{ $personalHistoryUrl ?? '/master/points/history/personal' }}" class="btn btn-outline-primary btn-sm">個人履歴</a>
        <a href="{{ $periodHistoryUrl ?? '/master/points/history/period' }}" class="btn btn-primary btn-sm">日次 / 月次履歴</a>
    </div>
</div>

<div id="alert-area"></div>

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

    function togglePeriodInputs() {
        const mode = document.getElementById('period-mode').value;
        const dateWrap = document.getElementById('period-date-wrap');
        const monthWrap = document.getElementById('period-month-wrap');
        dateWrap.classList.toggle('d-none', mode !== 'daily');
        monthWrap.classList.toggle('d-none', mode !== 'monthly');
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

    (function initializeFilters() {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        document.getElementById('period-date').value = `${yyyy}-${mm}-${dd}`;
        document.getElementById('period-month').value = `${yyyy}-${mm}`;
    })();

    togglePeriodInputs();
    loadPeriodHistory();
</script>
@endsection
