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
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(90deg, #14532d 0%, #22c55e 100%);">
        <div class="container-fluid">
            <a class="navbar-brand" href="/seller">
                <i class="fas fa-store me-2"></i>学食システム - 販売者管理
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3 text-white" id="seller-display-name">販売者</span>
                <button onclick="handleLogout()" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i>ログアウト
                </button>
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
    </script>
    @yield('scripts')
</body>
</html>
