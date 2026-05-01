@extends('layouts.seller_layout')

@section('title', '売上・注文履歴レポート')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-1">売上・注文履歴レポート</h1>
        <div class="text-muted">販売者ごとの売上と注文履歴を期間指定で確認できます。</div>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" onclick="loadReport()">
            <i class="fas fa-sync me-1"></i>更新
        </button>
        <button type="button" class="btn btn-success" onclick="downloadReportCsv()">
            <i class="fas fa-download me-1"></i>CSVダウンロード
        </button>
    </div>
</div>

<div id="alert-area"></div>

<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">開始日</label>
                <input type="date" class="form-control" id="start-date">
            </div>
            <div class="col-md-3">
                <label class="form-label">終了日</label>
                <input type="date" class="form-control" id="end-date">
            </div>
            <div class="col-md-3">
                <label class="form-label">ステータス</label>
                <select class="form-select" id="status-filter">
                    <option value="all">すべて</option>
                    <option value="調理中">調理中</option>
                    <option value="完了">完了</option>
                    <option value="受渡済">受渡済</option>
                </select>
            </div>
            <div class="col-md-3 d-grid">
                <button type="button" class="btn btn-primary" onclick="loadReport()">
                    <i class="fas fa-search me-1"></i>表示
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4" id="summary-cards">
    <div class="col-md-3">
        <div class="card h-100 border-0 bg-success text-white">
            <div class="card-body">
                <div class="small opacity-75">売上合計</div>
                <div class="h3 mb-0" id="summary-total-sales">-</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 border-0 bg-primary text-white">
            <div class="card-body">
                <div class="small opacity-75">注文件数</div>
                <div class="h3 mb-0" id="summary-total-orders">-</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 border-0 bg-info text-white">
            <div class="card-body">
                <div class="small opacity-75">注文点数</div>
                <div class="h3 mb-0" id="summary-total-quantity">-</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 border-0 bg-warning text-dark">
            <div class="card-body">
                <div class="small opacity-75">平均注文額</div>
                <div class="h3 mb-0" id="summary-average-order-value">-</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="fas fa-chart-line me-2"></i>ステータス別集計</h5>
        <div class="small text-muted" id="summary-period">-</div>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2" id="status-summary"></div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>注文履歴</h5>
        <span class="badge bg-light text-dark" id="detail-count">0 件</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>注文ID</th>
                        <th>日時</th>
                        <th>顧客</th>
                        <th>ステータス</th>
                        <th>商品内容</th>
                        <th class="text-end">数量</th>
                        <th class="text-end">売上</th>
                    </tr>
                </thead>
                <tbody id="report-table-body">
                    <tr><td colspan="7" class="text-center text-muted py-4">読み込み中...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const token = localStorage.getItem('token') || '';
    const statusLabels = {
        '調理中': 'warning text-dark',
        '完了': 'info',
        '受渡済': 'success',
    };

    function getHeaders(contentType = null) {
        const headers = { 'Accept': 'application/json' };
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        if (contentType) {
            headers['Content-Type'] = contentType;
        }
        return headers;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatCurrency(amount) {
        return `¥${Number(amount || 0).toLocaleString()}`;
    }

    function getQueryParams() {
        const params = new URLSearchParams();
        const startDate = document.getElementById('start-date').value;
        const endDate = document.getElementById('end-date').value;
        const status = document.getElementById('status-filter').value;

        if (startDate) params.set('start_date', startDate);
        if (endDate) params.set('end_date', endDate);
        if (status && status !== 'all') params.set('status', status);

        return params;
    }

    function setDefaultDates() {
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const toIsoDate = (date) => date.toISOString().slice(0, 10);

        document.getElementById('start-date').value = toIsoDate(firstDay);
        document.getElementById('end-date').value = toIsoDate(today);
    }

    function renderStatusSummary(statusCounts) {
        const container = document.getElementById('status-summary');
        const statuses = ['調理中', '完了', '受渡済'];
        container.innerHTML = statuses.map((status) => {
            const count = Number(statusCounts?.[status] || 0);
            const badgeClass = statusLabels[status] || 'secondary';
            return `<span class="badge bg-${badgeClass}">${escapeHtml(status)}: ${count}件</span>`;
        }).join('');
    }

    function renderOrders(orders) {
        const tbody = document.getElementById('report-table-body');
        const detailCount = document.getElementById('detail-count');
        if (!orders || orders.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">対象データがありません</td></tr>';
            detailCount.textContent = '0 件';
            return;
        }

        detailCount.textContent = `${orders.length} 件`;
        tbody.innerHTML = orders.map((order) => {
            const badgeClass = statusLabels[order.status] || 'secondary';
            return `
                <tr>
                    <td>#${order.order_id}</td>
                    <td>${escapeHtml(order.order_created_at)}</td>
                    <td>${escapeHtml(order.customer_name)}</td>
                    <td><span class="badge bg-${badgeClass}">${escapeHtml(order.status)}</span></td>
                    <td>${escapeHtml(order.item_summary || '')}</td>
                    <td class="text-end">${Number(order.total_quantity || 0)}</td>
                    <td class="text-end fw-semibold">${formatCurrency(order.total_sales)}</td>
                </tr>
            `;
        }).join('');
    }

    async function loadReport() {
        const query = getQueryParams().toString();
        const url = `/api/seller/reports${query ? `?${query}` : ''}`;
        const response = await fetch(url, { headers: getHeaders() });
        const result = await response.json();

        if (!response.ok || !result.success) {
            const message = result.message || 'レポートの取得に失敗しました';
            document.getElementById('report-table-body').innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">${escapeHtml(message)}</td></tr>`;
            return;
        }

        const summary = result.data.summary || {};
        const filters = result.data.filters || {};
        const orders = result.data.orders || [];

        document.getElementById('summary-total-sales').textContent = formatCurrency(summary.total_sales);
        document.getElementById('summary-total-orders').textContent = Number(summary.total_orders || 0).toLocaleString();
        document.getElementById('summary-total-quantity').textContent = Number(summary.total_quantity || 0).toLocaleString();
        document.getElementById('summary-average-order-value').textContent = formatCurrency(summary.average_order_value);
        document.getElementById('summary-period').textContent = `${filters.start_date || '-'} 〜 ${filters.end_date || '-'}`;

        renderStatusSummary(summary.status_counts || {});
        renderOrders(orders);
    }

    async function downloadReportCsv() {
        const query = getQueryParams().toString();
        const url = `/api/seller/reports/export${query ? `?${query}` : ''}`;
        const response = await fetch(url, { headers: getHeaders() });

        if (!response.ok) {
            const error = await response.json().catch(() => ({}));
            alert(error.message || 'CSVのダウンロードに失敗しました');
            return;
        }

        const blob = await response.blob();
        const downloadUrl = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.download = 'seller_report.csv';
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(downloadUrl);
    }

    setDefaultDates();
    loadReport();
</script>
@endsection
