<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '管理画面') - 学校食堂注文システム</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            overflow-x: hidden;
            padding-top: 56px;
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
            background-color: #343a40;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 100;
        }
        .sidebar .nav-link {
            color: #fff;
            padding: 12px 20px;
            white-space: nowrap;
        }
        .sidebar .nav-link:hover {
            background-color: #495057;
        }
        .sidebar .nav-link.active {
            background-color: #007bff;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            max-width: calc(100vw - 250px);
            overflow-x: hidden;
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/master">
                <i class="fas fa-utensils me-2"></i>学食システム - マスター管理
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3 text-white">管理者</span>
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
                            <a class="nav-link" href="/master/news">
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
    @yield('scripts')
</body>
</html>
