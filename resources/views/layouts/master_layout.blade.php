<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '管理画面') - 学校食堂注文システム</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --font-sans: 'Noto Sans JP', 'Segoe UI', sans-serif;
            --color-bg: #f5f7fb;
            --color-surface: #ffffff;
            --color-surface-muted: #f1f5f9;
            --color-text: #1f2937;
            --color-text-muted: #64748b;
            --color-border: #dbe4ee;
            --color-primary: #2563eb;
            --color-primary-strong: #1d4ed8;
            --color-primary-soft: #dbeafe;
            --color-accent: #f59e0b;
            --color-success: #16a34a;
            --color-danger: #dc2626;
            --color-warning: #d97706;
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
            background: linear-gradient(180deg, #111827 0%, #1f2937 100%);
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
        .main-content {
            margin-left: 250px;
            padding: var(--space-6);
            max-width: calc(100vw - 250px);
            overflow-x: hidden;
        }
        .container-fluid {
            padding-left: 0;
            padding-right: 0;
        }
        .card {
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }
        .card-header {
            background: var(--color-surface);
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
        .btn-primary {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
        }
        .btn-primary:hover,
        .btn-primary:focus {
            background-color: var(--color-primary-strong);
            border-color: var(--color-primary-strong);
        }
        .btn-success {
            background-color: var(--color-success);
            border-color: var(--color-success);
        }
        .btn-warning {
            background-color: var(--color-warning);
            border-color: var(--color-warning);
            color: #fff;
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
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.15);
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
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(90deg, #0f172a 0%, #1d4ed8 100%);">
        <div class="container-fluid">
            <a class="navbar-brand" href="/master">
                <i class="fas fa-utensils me-2"></i>学食システム - マスター管理
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3 text-white" id="master-display-name">管理者</span>
                <a href="/login" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i>ログアウト
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- サイドバー -->
            <nav class="sidebar">
                <div class="pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="/master">
                                <i class="fas fa-tachometer-alt me-2"></i>ダッシュボード
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/master/stats">
                                <i class="fas fa-chart-line me-2"></i>売上統計
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/master/users">
                                <i class="fas fa-users me-2"></i>ユーザー管理
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/master/products">
                                <i class="fas fa-shopping-bag me-2"></i>商品管理
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/master/orders">
                                <i class="fas fa-receipt me-2"></i>注文管理
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/master/order-windows">
                                <i class="fas fa-calendar-alt me-2"></i>注文可能時間設定
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/master/cart">
                                <i class="fas fa-shopping-cart me-2"></i>カート管理
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/master/news">
                                <i class="fas fa-newspaper me-2"></i>ニュース管理
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/master/logs">
                                <i class="fas fa-file-alt me-2"></i>ログ管理
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/master/migration">
                                <i class="fas fa-database me-2"></i>マイグレーション
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- メインコンテンツ -->
            <main class="main-content">
                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resolveMasterDisplayName() {
            let user = {};
            try {
                user = JSON.parse(localStorage.getItem('user') || '{}');
            } catch (error) {
                user = {};
            }

            const fullName = `${user.name_2nd || ''} ${user.name_1st || ''}`.trim();
            return fullName || user.name || user.displayName || user.username || '管理者';
        }

        const masterNameElement = document.getElementById('master-display-name');
        if (masterNameElement) {
            masterNameElement.textContent = resolveMasterDisplayName();
        }
    </script>
    @yield('scripts')
</body>
</html>
