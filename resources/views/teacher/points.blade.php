@extends('layouts.master_layout')

@section('title', '先生ポイント承認')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">先生ポイント承認</h1>
</div>

<div id="alert-area"></div>

<div class="card mb-3">
    <div class="card-body py-3">
        <p class="mb-1 fw-semibold">先生向けルール</p>
        <ul class="mb-0">
            <li>生徒・先生のポイント購入申請を承認できます。</li>
            <li>申請者本人による承認はできません。</li>
            <li>承認者名は履歴に保存されます。</li>
        </ul>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-coins me-2"></i>チャージ申請一覧</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>申請者</th>
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
@endsection

@section('scripts')
<script>
    const alertArea = document.getElementById('alert-area');
    const body = document.getElementById('points-requests-body');

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

    function formatStatus(status) {
        const normalized = String(status || '').toLowerCase();
        if (normalized === 'pending') {
            return '申請中';
        }
        if (normalized === 'approved') {
            return '承認済み';
        }
        if (normalized === 'rejected') {
            return '却下';
        }
        return status || '-';
    }

    async function loadRequests() {
        try {
            const response = await fetch('/api/master/points/requests', {
                headers: getAuthHeaders()
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || '読み込みに失敗しました');
            }

            const rows = result.data || [];
            if (!rows.length) {
                body.innerHTML = '<tr><td colspan="8" class="text-center">申請はありません</td></tr>';
                return;
            }

            body.innerHTML = rows.map((request) => {
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
                        <td>${request.user?.display_name || request.user?.name || request.user?.username || '-'}</td>
                        <td>${Number(request.amount || 0).toLocaleString()} pts</td>
                        <td><span class="badge bg-secondary">${formatStatus(request.status)}</span></td>
                        <td>${request.created_at ? new Date(request.created_at).toLocaleString('ja-JP') : '-'}</td>
                        <td>${request.approver_name || request.approver?.display_name || request.approver?.username || '-'}</td>
                        <td>${request.approved_at ? new Date(request.approved_at).toLocaleString('ja-JP') : '-'}</td>
                        <td>${actionHtml}</td>
                    </tr>
                `;
            }).join('');
        } catch (error) {
            console.error(error);
            if (error.message && error.message.includes('認証')) {
                window.location.href = '/login';
                return;
            }
            body.innerHTML = '<tr><td colspan="8" class="text-center text-danger">読み込みに失敗しました</td></tr>';
        }
    }

    async function approveRequest(id) {
        try {
            const response = await fetch(`/api/master/points/requests/${id}/approve`, {
                method: 'POST',
                headers: getAuthHeaders()
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || '承認に失敗しました');
            }
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
            if (!response.ok || !result.success) {
                throw new Error(result.message || '却下に失敗しました');
            }
            showAlert('warning', '却下しました');
            loadRequests();
        } catch (error) {
            showAlert('danger', error.message);
        }
    }

    loadRequests();
</script>
@endsection
