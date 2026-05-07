<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '販売者画面') - 学校食堂注文システム</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --font-sans: 'Noto Sans JP', 'Segoe UI', sans-serif;
            --color-bg: #f6faf6;
            --color-surface: #ffffff;
            --color-surface-muted: #eef8ef;
            --color-text: #1f2937;
            --color-text-muted: #64748b;
            --color-border: #d7e8d8;
            --color-primary: #16a34a;
            --color-primary-strong: #15803d;
            --color-primary-soft: #dcfce7;
            --color-accent: #2dd4bf;
            --color-success: #22c55e;
            --color-danger: #ef4444;
            --color-warning: #f59e0b;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 18px;
            --space-1: 4px;
            --space-2: 8px;
            --space-3: 12px;
            --space-4: 16px;
            --space-5: 20px;
            --space-6: 24px;
            --shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.06);
            --shadow-md: 0 8px 24px rgba(15, 23, 42, 0.08);
        }

        body {
            background-color: var(--color-bg);
            color: var(--color-text);
            font-family: var(--font-sans);
            overflow-x: hidden;
            padding-top: 56px;
            line-height: 1.6;
        }
        .skip-link {
            position: absolute;
            left: 12px;
            top: -40px;
            z-index: 3000;
            background: #fff;
            color: #111827;
            border: 2px solid var(--color-primary);
            border-radius: 10px;
            padding: 10px 14px;
            font-weight: 700;
            transition: top 0.2s ease;
        }
        .skip-link:focus {
            top: 12px;
        }
        a:focus-visible,
        button:focus-visible,
        input:focus-visible,
        select:focus-visible,
        textarea:focus-visible {
            outline: 3px solid rgba(22, 163, 74, 0.4);
            outline-offset: 2px;
        }
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
        }
        .sidebar {
            position: fixed;
            top: 56px;
            left: 0;
            bottom: 0;
            width: 250px;
            background: linear-gradient(180deg, #14532d 0%, #166534 100%);
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 100;
            box-shadow: var(--shadow-md);
        }
        .sidebar .nav-link {
            color: #fff;
            padding: 12px 18px;
            white-space: nowrap;
            border-radius: var(--radius-sm);
            margin: 4px 10px;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }
        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.08);
            transform: translateX(2px);
        }
        .sidebar .nav-link.active {
            background-color: var(--color-primary);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.12);
        }
        .sidebar .nav-link:focus-visible {
            outline-color: rgba(255, 255, 255, 0.65);
        }
        .main-content {
            margin-left: 250px;
            padding: var(--space-6);
            max-width: calc(100vw - 250px);
            overflow-x: hidden;
        }
        .page-guide {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 12px;
            align-items: center;
            margin-bottom: var(--space-5);
            padding: 14px 16px;
            background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);
            border: 1px solid #bbf7d0;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }
        .page-guide__label {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 78px;
            padding: 4px 10px;
            border-radius: 999px;
            background: var(--color-primary-soft);
            color: var(--color-primary-strong);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.04em;
        }
        .page-guide__text {
            margin: 0;
            color: var(--color-text);
            font-size: 15px;
            line-height: 1.7;
        }
        .page-sequence {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 6px;
        }
        .page-sequence__item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid #d7e8d8;
            color: var(--color-text-muted);
            font-size: 12px;
            font-weight: 700;
        }
        .container-fluid {
            padding-left: 0;
            padding-right: 0;
        }
        .card {
            border: 1px solid var(--color-border);
            padding: 0.7rem 1rem;
            font-weight: 700;
            font-size: 0.95rem;
            line-height: 1.2;
        }
        .btn-sm {
            padding: 0.45rem 0.8rem;
            font-size: 0.875rem;
        }
        .btn-lg {
            padding: 0.85rem 1.15rem;
            font-size: 1rem;
        }
        .btn-outline-primary,
        .btn-outline-secondary,
        .btn-outline-success,
        .btn-outline-danger,
        .btn-outline-warning,
        .btn-outline-info,
        .btn-outline-dark,
        .btn-outline-light {
            border-width: 1.5px;
        }
        .badge {
            border-radius: 999px;
            padding: 0.4rem 0.65rem;
            font-size: 0.8rem;
        }
        .badge.bg-success,
        .badge.text-bg-success {
            background-color: #dcfce7 !important;
            color: #166534 !important;
        }
        .badge.bg-warning,
        .badge.text-bg-warning {
            background-color: #fef3c7 !important;
            color: #92400e !important;
        }
        .badge.bg-danger,
        .badge.text-bg-danger {
            background-color: #fee2e2 !important;
            color: #b91c1c !important;
        }
        .badge.bg-secondary,
        .badge.text-bg-secondary,
        .badge.bg-info,
        .badge.text-bg-info {
            background-color: #e2e8f0 !important;
            color: #334155 !important;
        }
        .alert {
            border-radius: var(--radius-md);
            padding: 0.9rem 1rem;
            font-size: 0.95rem;
        }
        .alert-success {
            background-color: #f0fdf4;
            border-color: #bbf7d0;
            color: #166534;
        }
        .alert-info {
            background-color: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
        }
        .alert-warning {
            background-color: #fffbeb;
            border-color: #fde68a;
            color: #92400e;
        }
        .alert-danger {
            background-color: #fef2f2;
            border-color: #fecaca;
            color: #b91c1c;
            box-shadow: var(--shadow-sm);
            border-bottom: 1px solid var(--color-border);
            padding: var(--space-4) var(--space-5);
        }
        .card-body {
            padding: var(--space-5);
        }
        .btn {
            border-radius: 999px;
            padding-inline: 1rem;
        }
        .navbar .logout-btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 108px;
            padding-left: 2rem;
            padding-right: 1rem;
        }
        .navbar .logout-btn .logout-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
        }
        .navbar .logout-btn .logout-label {
            display: inline-block;
            width: 100%;
            text-align: center;
            line-height: 1.2;
        }
        .btn-primary,
        .btn-success {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
        }
        .btn-primary:hover,
        .btn-primary:focus,
        .btn-success:hover,
        .btn-success:focus {
            background-color: var(--color-primary-strong);
            border-color: var(--color-primary-strong);
        }
        .btn-warning {
            background-color: var(--color-warning);
            border-color: var(--color-warning);
            color: #1f2937;
        }
        .btn-warning:hover,
        .btn-warning:focus {
            color: #111827;
        }
        .form-control,
        .form-select {
            border-radius: var(--radius-md);
            border-color: var(--color-border);
            padding: 0.7rem 0.9rem;
        }
        .form-control:focus,
        .form-select:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 0.2rem rgba(22, 163, 74, 0.15);
        }
        .table {
            color: var(--color-text);
        }
        .table thead th {
            background: var(--color-surface-muted);
            border-bottom: 1px solid var(--color-border);
            color: var(--color-text-muted);
            font-weight: 700;
        }
        .badge {
            border-radius: 999px;
            padding: 0.35rem 0.6rem;
        }
        .loading-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.35);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            backdrop-filter: blur(1px);
        }
        .loading-overlay.is-visible {
            display: flex;
        }
        .loading-card {
            min-width: 220px;
            max-width: 85vw;
            background: #fff;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            padding: var(--space-5);
            text-align: center;
            border: 1px solid var(--color-border);
        }
        .loading-card .spinner-border {
            width: 2rem;
            height: 2rem;
            color: var(--color-primary);
        }
        .app-toast-container {
            z-index: 2100;
        }
        .feedback-message {
            margin-bottom: var(--space-4);
        }
        .sr-only-focusable:active,
        .sr-only-focusable:focus {
            position: static;
            width: auto;
            height: auto;
            margin: 0;
            overflow: visible;
            clip: auto;
            white-space: normal;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            .main-content {
                margin-left: 200px;
                max-width: calc(100vw - 200px);
            }
        }
    </style>
</head>
<body>
    <a href="#main-content" class="skip-link sr-only-focusable">メインコンテンツへスキップ</a>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(90deg, #14532d 0%, #22c55e 100%);" aria-label="販売者管理のメインナビゲーション">
        <div class="container-fluid">
            <a class="navbar-brand" href="/seller">
                <i class="fas fa-store me-2"></i>学食システム - 販売者管理
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3 text-white" id="seller-display-name">販売者</span>
                <button onclick="handleLogout()" class="btn btn-outline-light btn-sm logout-btn" aria-label="ログアウト">
                    <i class="fas fa-sign-out-alt logout-icon" aria-hidden="true"></i>
                    <span class="logout-label">ログアウト</span>
                </button>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- サイドバー -->
            <nav class="sidebar" aria-label="販売者メニュー">
                <div class="pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="/seller">
                                <i class="fas fa-tachometer-alt me-2"></i>ダッシュボード
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/seller/products">
                                <i class="fas fa-shopping-bag me-2"></i>商品管理
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/seller/orders">
                                <i class="fas fa-receipt me-2"></i>注文管理
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/seller/news">
                                <i class="fas fa-newspaper me-2"></i>ニュース管理
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/seller/reports">
                                <i class="fas fa-chart-line me-2"></i>売上・履歴レポート
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/seller/help">
                                <i class="fas fa-circle-question me-2"></i>使い方
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- メインコンテンツ -->
            <main class="main-content" id="main-content" role="main" tabindex="-1" aria-label="@yield('title', 'メインコンテンツ')">
                <div class="page-guide" aria-label="画面案内">
                    <div class="page-guide__label">目的</div>
                    <p class="page-guide__text">販売商品の管理、注文確認、ニュース更新、売上確認を行います。操作方法: 左のメニューで画面を切り替え、各画面のボタンで保存・戻る・再読み込みを行ってください。</p>
                    <div class="page-sequence" aria-label="画面の見方">
                        <span class="page-sequence__item">1. タイトル</span>
                        <span class="page-sequence__item">2. 現在の状態</span>
                        <span class="page-sequence__item">3. 操作エリア</span>
                        <span class="page-sequence__item">4. 補足説明</span>
                    </div>
                </div>
                <div id="app-feedback-message" class="feedback-message" role="status" aria-live="polite" aria-atomic="true"></div>
                @yield('content')
            </main>
        </div>
    </div>

    <div id="global-loading-overlay" class="loading-overlay" aria-live="polite" aria-busy="false" aria-hidden="true">
        <div class="loading-card" role="status" aria-live="assertive" aria-label="処理中です">
            <div class="spinner-border" aria-hidden="true"></div>
            <div id="global-loading-text" class="mt-3 fw-semibold">読み込み中...</div>
        </div>
    </div>

    <div id="app-toast-container" class="toast-container position-fixed top-0 end-0 p-3 app-toast-container" aria-live="polite" aria-atomic="true"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const loadingOverlay = document.getElementById('global-loading-overlay');
            const loadingText = document.getElementById('global-loading-text');
            const toastContainer = document.getElementById('app-toast-container');
            const defaultMessageContainer = document.getElementById('app-feedback-message');

            function normalizeType(type) {
                if (type === 'error' || type === 'danger') {
                    return 'danger';
                }
                if (type === 'warn') {
                    return 'warning';
                }
                return type || 'info';
            }

            function showLoading(message) {
                if (!loadingOverlay) return;
                loadingOverlay.classList.add('is-visible');
                loadingOverlay.setAttribute('aria-busy', 'true');
                loadingOverlay.setAttribute('aria-hidden', 'false');
                if (loadingText) {
                    loadingText.textContent = message || '読み込み中...';
                }
            }

            function hideLoading() {
                if (!loadingOverlay) return;
                loadingOverlay.classList.remove('is-visible');
                loadingOverlay.setAttribute('aria-busy', 'false');
                loadingOverlay.setAttribute('aria-hidden', 'true');
            }

            function showToast(message, type = 'success', delay = 2500) {
                if (!toastContainer || !window.bootstrap || !message) return;
                const resolvedType = normalizeType(type);
                const toastEl = document.createElement('div');
                toastEl.className = 'toast align-items-center text-bg-' + resolvedType + ' border-0';
                toastEl.setAttribute('role', 'alert');
                toastEl.setAttribute('aria-live', 'assertive');
                toastEl.setAttribute('aria-atomic', 'true');
                toastEl.innerHTML = '' +
                    '<div class="d-flex">' +
                    '<div class="toast-body">' + message + '</div>' +
                    '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
                    '</div>';
                toastContainer.appendChild(toastEl);

                const toast = new bootstrap.Toast(toastEl, { delay: delay });
                toastEl.addEventListener('hidden.bs.toast', function () {
                    toastEl.remove();
                });
                toast.show();
            }

            function showMessage(message, type = 'success', containerId = 'app-feedback-message') {
                const resolvedType = normalizeType(type);
                const container = document.getElementById(containerId) || defaultMessageContainer;
                if (!container) return;
                if (!message) {
                    container.innerHTML = '';
                    return;
                }
                container.innerHTML = '<div class="alert alert-' + resolvedType + ' alert-dismissible fade show" role="alert">' +
                    '<span>' + message + '</span>' +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                    '</div>';
            }

            async function withFeedback(task, options = {}) {
                const loadingMessage = options.loadingMessage || '処理中...';
                const successMessage = options.successMessage || '';
                const errorMessage = options.errorMessage || '処理に失敗しました';
                const toastOnSuccess = options.toastOnSuccess !== false;
                const toastOnError = options.toastOnError !== false;
                const messageContainerId = options.messageContainerId || 'app-feedback-message';

                showLoading(loadingMessage);
                try {
                    const result = await task();
                    hideLoading();
                    if (successMessage) {
                        showMessage(successMessage, 'success', messageContainerId);
                        if (toastOnSuccess) showToast(successMessage, 'success');
                    }
                    return result;
                } catch (error) {
                    hideLoading();
                    showMessage(errorMessage, 'danger', messageContainerId);
                    if (toastOnError) showToast(errorMessage, 'danger');
                    throw error;
                }
            }

            window.UIFeedback = {
                showLoading,
                hideLoading,
                showToast,
                showMessage,
                withFeedback,
            };

            window.addEventListener('pageshow', hideLoading);

            function applyCurrentNavState() {
                const currentPath = window.location.pathname.replace(/\/$/, '') || '/';
                document.querySelectorAll('.sidebar .nav-link').forEach((link) => {
                    const linkPath = new URL(link.href, window.location.origin).pathname.replace(/\/$/, '') || '/';
                    const isActive = currentPath === linkPath;
                    link.classList.toggle('active', isActive);
                    if (isActive) {
                        link.setAttribute('aria-current', 'page');
                    } else {
                        link.removeAttribute('aria-current');
                    }
                });
            }

            applyCurrentNavState();

            document.addEventListener('submit', function (event) {
                const form = event.target;
                if (!(form instanceof HTMLFormElement)) return;
                if (form.matches('[data-feedback-submit], .js-feedback-form')) {
                    const submitMessage = form.getAttribute('data-feedback-loading') || '保存中...';
                    showLoading(submitMessage);
                }
            });
        })();

        function resolveSellerDisplayName() {
            let user = {};
            try {
                user = JSON.parse(localStorage.getItem('user') || '{}');
            } catch (error) {
                user = {};
            }

            const fullName = `${user.name_2nd || ''} ${user.name_1st || ''}`.trim();
            return fullName || user.name || user.displayName || user.username || '販売者';
        }

        const sellerNameElement = document.getElementById('seller-display-name');
        if (sellerNameElement) {
            sellerNameElement.textContent = resolveSellerDisplayName();
        }

        // ログアウト処理
        async function handleLogout() {
            const token = localStorage.getItem('token') || localStorage.getItem('authToken');
            
            if (token) {
                try {
                    await fetch('/api/auth/logout', {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json',
                        }
                    });
                } catch (error) {
                    console.error('ログアウトエラー:', error);
                }
            }
            
            // トークンとセッションをクリア
            localStorage.removeItem('token');
            localStorage.removeItem('authToken');
            localStorage.removeItem('user');
            
            // ログインページにリダイレクト
            window.location.href = '/login';
        }

        const flashSuccessMessage = @json(session('success'));
        const flashErrorMessage = @json(session('error'));
        const flashWarningMessage = @json(session('warning'));

        if (window.UIFeedback) {
            if (flashSuccessMessage) {
                window.UIFeedback.showMessage(flashSuccessMessage, 'success');
                window.UIFeedback.showToast(flashSuccessMessage, 'success');
            }
            if (flashErrorMessage) {
                window.UIFeedback.showMessage(flashErrorMessage, 'danger');
                window.UIFeedback.showToast(flashErrorMessage, 'danger');
            }
            if (flashWarningMessage) {
                window.UIFeedback.showMessage(flashWarningMessage, 'warning');
                window.UIFeedback.showToast(flashWarningMessage, 'warning');
            }
        }
    </script>
    @yield('scripts')
</body>
</html>
