<?php
// 一時的なウェルカムページ（vendor/autoload.phpがインストールされるまで）

if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    // Laravelフレームワークを読み込み
    require __DIR__.'/../vendor/autoload.php';
    
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle($request = \Illuminate\Http\Request::capture())->send();
    $kernel->terminate($request, $response);
} else {
    // Composer依存関係がまだインストールされていない場合、HTMLページを表示
    if (file_exists(__DIR__.'/welcome.html')) {
        readfile(__DIR__.'/welcome.html');
    } else {
        echo '<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel セットアップ中</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body p-5 text-center">
                        <h1 class="mb-4">⚙️ Laravel セットアップ中</h1>
                        <div class="alert alert-warning">
                            <strong>Composer 依存関係をインストールしてください</strong>
                        </div>
                        <h5 class="mb-3">セットアップ手順:</h5>
                        <ol class="text-start">
                            <li><strong>laravel-app</strong> フォルダを開く</li>
                            <li><strong>install-composer.bat</strong> をダブルクリック</li>
                            <li>インストール完了後、このページを再読み込み</li>
                        </ol>
                        <hr class="my-4">
                        <p class="text-muted">または、コマンドプロンプトで以下を実行:</p>
                        <pre class="bg-dark text-white p-3 rounded">docker exec cafeteria_laravel_web composer install --working-dir=/var/www/html</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>';
    }
}
