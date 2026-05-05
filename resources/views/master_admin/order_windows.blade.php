@extends('layouts.master_layout')

@section('title', '注文可能時間設定')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">注文可能時間設定</h1>
    <button class="btn btn-sm btn-primary" onclick="reloadMonth()">
        <i class="fas fa-sync me-1"></i>更新
    </button>
</div>

<div id="alert-area"></div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">表示月</label>
                <input type="month" class="form-control" id="monthPicker" onchange="reloadMonth()">
            </div>
            <div class="col-md-3">
                <label class="form-label">開始時刻</label>
                <input type="time" class="form-control" id="startTime" value="10:00">
            </div>
            <div class="col-md-3">
                <label class="form-label">終了時刻</label>
                <input type="time" class="form-control" id="endTime" value="14:00">
            </div>
            <div class="col-md-3">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" id="isClosed" onchange="toggleTimeInputs()">
                    <label class="form-check-label" for="isClosed">選択日を休止日にする</label>
                </div>
            </div>
            <div class="col-md-9">
                <label class="form-label">メモ（任意）</label>
                <input type="text" class="form-control" id="note" maxlength="255" placeholder="例: 学園祭準備のため受付停止">
            </div>
            <div class="col-md-3">
                <label class="form-label">選択日数</label>
                <div id="selectedCount" class="form-control bg-light">0日</div>
            </div>
            <div class="col-md-6">
                <button class="btn btn-success w-100" onclick="saveSelectedDates()">
                    <i class="fas fa-save me-1"></i>選択日に一括保存
                </button>
            </div>
            <div class="col-md-6">
                <button class="btn btn-outline-danger w-100" onclick="clearSelectedDates()">
                    <i class="fas fa-trash-alt me-1"></i>選択日の設定を解除
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <p class="mb-2">
            <span class="badge bg-success">営業日</span>
            <span class="ms-2 badge bg-danger">休止日</span>
            <span class="ms-2 badge bg-secondary">未設定（常時受付）</span>
            <span class="ms-2 badge bg-primary">選択中</span>
        </p>
        <div class="table-responsive">
            <table class="table table-bordered text-center align-middle mb-0" id="calendarTable"></table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">設定一覧（表示月）</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>日付</th>
                        <th>状態</th>
                        <th>受付時間</th>
                        <th>メモ</th>
                    </tr>
                </thead>
                <tbody id="windowListBody">
                    <tr><td colspan="4" class="text-center">読み込み中...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const settingsByDate = new Map();
    const selectedDates = new Set();

    function showAlert(type, message) {
        const alertArea = document.getElementById('alert-area');
        alertArea.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        setTimeout(() => {
            alertArea.innerHTML = '';
        }, 4000);
    }

    function toDateString(date) {
        // ISO形式を使って確実に日付を取得
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const result = `${year}-${month}-${day}`;
        // デバッグ: 日付オブジェクトの詳細ログ
        // console.log(`toDateString: year=${year}, month=${month}, day=${day}, result=${result}, original=${date.toString()}`);
        return result;
    }

    function normalizeDateString(value) {
        if (!value) return '';
        return String(value).slice(0, 10);
    }

    function updateSelectedCount() {
        document.getElementById('selectedCount').textContent = `${selectedDates.size}日`;
    }

    function toggleTimeInputs() {
        const closed = document.getElementById('isClosed').checked;
        document.getElementById('startTime').disabled = closed;
        document.getElementById('endTime').disabled = closed;
    }

    async function reloadMonth() {
        const month = document.getElementById('monthPicker').value;
        if (!month) {
            return;
        }

        try {
            const response = await fetch(`/api/master/order-windows?month=${encodeURIComponent(month)}`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                const text = await response.text();
                console.error(text);
                showAlert('danger', '設定の取得に失敗しました。');
                return;
            }

            const result = await response.json();
            settingsByDate.clear();
            (result.data || []).forEach(item => {
                const key = normalizeDateString(item.target_date);
                settingsByDate.set(key, {
                    ...item,
                    target_date: key,
                });
            });

            selectedDates.clear();
            updateSelectedCount();
            renderCalendar();
            renderList();
        } catch (error) {
            console.error(error);
            showAlert('danger', '設定の取得中にエラーが発生しました。');
        }
    }

    function renderCalendar() {
        const monthValue = document.getElementById('monthPicker').value;
        if (!monthValue) return;
        
        const [yearText, monthText] = monthValue.split('-');
        const year = Number(yearText);
        const monthIndex = Number(monthText) - 1;

        // 月の最初の日と最後の日を計算
        const firstDay = new Date(year, monthIndex, 1);
        const lastDayOfMonth = new Date(year, monthIndex + 1, 0).getDate();
        const firstDayOfWeek = firstDay.getDay();

        const dayHeaders = ['日', '月', '火', '水', '木', '金', '土'];
        const table = document.getElementById('calendarTable');

        let html = '<thead><tr>' + dayHeaders.map(d => `<th>${d}</th>`).join('') + '</tr></thead><tbody>';

        // 前月の最終日
        const prevMonthLastDay = new Date(year, monthIndex, 0).getDate();
        const startDay = prevMonthLastDay - firstDayOfWeek + 1;

        const totalCells = 42; // 6週 × 7日
        let displayMonth = monthIndex - 1;
        let displayYear = year;
        if (displayMonth < 0) {
            displayMonth = 11;
            displayYear--;
        }

        let currentDay = startDay;

        for (let cellIndex = 0; cellIndex < totalCells; cellIndex++) {
            if (cellIndex % 7 === 0) {
                html += '<tr>';
            }

            // 月をまたぐ処理
            const daysInCurrentMonth = new Date(displayYear, displayMonth + 1, 0).getDate();
            if (currentDay > daysInCurrentMonth) {
                currentDay = 1;
                displayMonth++;
                if (displayMonth > 11) {
                    displayMonth = 0;
                    displayYear++;
                }
            }

            // 当月判定
            const inMonth = displayMonth === monthIndex && displayYear === year;

            // 日付文字列を直接生成（Date オブジェクトを避ける）
            const dateStr = createDateString(displayYear, displayMonth, currentDay);
            
            const setting = settingsByDate.get(dateStr);
            const isSelected = selectedDates.has(dateStr);

            let badgeClass = 'bg-secondary';
            let badgeText = '未設定';
            if (setting) {
                if (setting.is_closed) {
                    badgeClass = 'bg-danger';
                    badgeText = '休止';
                } else {
                    badgeClass = 'bg-success';
                    badgeText = `${String(setting.start_time || '').slice(0,5)}-${String(setting.end_time || '').slice(0,5)}`;
                }
            }

            const selectedStyle = isSelected ? 'border border-3 border-primary' : '';
            const fadedStyle = inMonth ? '' : 'text-muted bg-light';

            html += `
                <td class="${selectedStyle} ${fadedStyle}" style="cursor:${inMonth ? 'pointer' : 'default'}" ${inMonth ? `onclick="toggleDate('${dateStr}')"` : ''}>
                    <div class="fw-bold">${currentDay}</div>
                    <span class="badge ${isSelected ? 'bg-primary' : badgeClass} mt-1">${isSelected ? '選択中' : badgeText}</span>
                </td>
            `;

            if ((cellIndex + 1) % 7 === 0) {
                html += '</tr>';
            }

            currentDay++;
        }

        html += '</tbody>';
        table.innerHTML = html;
    }

    function createDateString(year, monthIndex, day) {
        const month = String(monthIndex + 1).padStart(2, '0');
        const dayStr = String(day).padStart(2, '0');
        const result = `${year}-${month}-${dayStr}`;
        console.log(`createDateString: year=${year}, monthIndex=${monthIndex}, day=${day} -> ${result}`);
        return result;
    }

    function renderList() {
        const tbody = document.getElementById('windowListBody');
        const items = Array.from(settingsByDate.values()).sort((a, b) => (a.target_date < b.target_date ? -1 : 1));

        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">この月の設定はありません</td></tr>';
            return;
        }

        tbody.innerHTML = items.map(item => {
            const state = item.is_closed ? '<span class="badge bg-danger">休止日</span>' : '<span class="badge bg-success">営業日</span>';
            const time = item.is_closed ? '-' : `${String(item.start_time || '').slice(0,5)} - ${String(item.end_time || '').slice(0,5)}`;
            return `
                <tr>
                    <td>${normalizeDateString(item.target_date)}</td>
                    <td>${state}</td>
                    <td>${time}</td>
                    <td>${item.note || ''}</td>
                </tr>
            `;
        }).join('');
    }

    function toggleDate(dateStr) {
        console.log(`toggleDate called with: ${dateStr}`);
        if (selectedDates.has(dateStr)) {
            selectedDates.delete(dateStr);
            console.log(`Deselected: ${dateStr}`);
        } else {
            selectedDates.add(dateStr);
            console.log(`Selected: ${dateStr}, Total selected: ${selectedDates.size}`);
        }
        updateSelectedCount();
        renderCalendar();
    }

    async function saveSelectedDates() {
        if (selectedDates.size === 0) {
            showAlert('warning', '日付を選択してください。');
            return;
        }

        const isClosed = document.getElementById('isClosed').checked;
        const startTime = document.getElementById('startTime').value;
        const endTime = document.getElementById('endTime').value;
        const note = document.getElementById('note').value.trim();

        if (!isClosed && (!startTime || !endTime)) {
            showAlert('warning', '営業日にする場合は開始時刻と終了時刻を入力してください。');
            return;
        }

        if (!isClosed && startTime >= endTime) {
            showAlert('warning', '終了時刻は開始時刻より後にしてください。');
            return;
        }

        const datesArray = Array.from(selectedDates);
        console.log('=== DEBUG: saveSelectedDates ===');
        console.log('Selected dates:', datesArray);
        console.log('isClosed:', isClosed);
        console.log('startTime:', startTime);
        console.log('endTime:', endTime);

        try {
            const requestBody = {
                dates: datesArray,
                is_closed: isClosed,
                start_time: startTime,
                end_time: endTime,
                note: note || null,
            };
            console.log('Request body:', JSON.stringify(requestBody, null, 2));

            const response = await fetch('/api/master/order-windows', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestBody)
            });

            console.log('Response status:', response.status);
            const result = await response.json();
            console.log('Response data:', result);

            if (!response.ok || !result.success) {
                showAlert('danger', result.message || '保存に失敗しました。');
                return;
            }

            showAlert('success', result.message || '保存しました。');
            await reloadMonth();
        } catch (error) {
            console.error(error);
            showAlert('danger', '保存中にエラーが発生しました。');
        }
    }

    async function clearSelectedDates() {
        if (selectedDates.size === 0) {
            showAlert('warning', '解除する日付を選択してください。');
            return;
        }

        if (!confirm('選択した日付の設定を解除しますか？')) {
            return;
        }

        try {
            const response = await fetch('/api/master/order-windows/clear', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ dates: Array.from(selectedDates) })
            });

            const result = await response.json();
            if (!response.ok || !result.success) {
                showAlert('danger', result.message || '設定解除に失敗しました。');
                return;
            }

            showAlert('success', result.message || '設定を解除しました。');
            await reloadMonth();
        } catch (error) {
            console.error(error);
            showAlert('danger', '設定解除中にエラーが発生しました。');
        }
    }

    function initialize() {
        const now = new Date();
        const defaultMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
        document.getElementById('monthPicker').value = defaultMonth;
        toggleTimeInputs();
        reloadMonth();
    }

    initialize();
</script>
@endsection
