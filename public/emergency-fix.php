<?php
/**
 * 緊急キャッシュクリアツール
 * https://komapay.p-kmt.com/emergency-fix.php でアクセス
 * 
 * 実行後、必ずこのファイルを削除してください！
 */

// Basic認証（セキュリティ対策）
$username = 'admin';
$password = 'emergency2024'; // このパスワードを変更してください

if (!isset($_SERVER['PHP_AUTH_USER']) || 
    $_SERVER['PHP_AUTH_USER'] !== $username || 
    $_SERVER['PHP_AUTH_PW'] !== $password) {
    header('WWW-Authenticate: Basic realm="Emergency Fix"');
    header('HTTP/1.0 401 Unauthorized');
    echo '認証が必要です';
    exit;
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>緊急修正ツール</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .warning { background: #fff3cd; border: 1px solid #ffeeba; padding: 15px; margin: 10px 0; border-radius: 5px; }
        pre { background: #f4f4f4; padding: 15px; overflow-x: auto; border-radius: 5px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn-danger { background: #dc3545; }
    </style>
</head>
<body>
    <h1>🚨 緊急修正ツール</h1>
    
    <?php
    if (isset($_GET['action'])) {
        $laravelRoot = dirname(__DIR__);
        chdir($laravelRoot);
        
        echo '<div class="warning"><strong>実行中...</strong></div>';
        
        if ($_GET['action'] === 'clear_all') {
            echo '<h2>📋 実行ログ</h2><pre>';
            
            // 1. bootstrap/cache を削除
            echo "\n=== bootstrap/cache をクリア ===\n";
            $files = [
                'bootstrap/cache/config.php',
                'bootstrap/cache/packages.php',
                'bootstrap/cache/services.php'
            ];
            
            foreach ($files as $file) {
                if (file_exists($file)) {
                    if (unlink($file)) {
                        echo "✅ 削除成功: $file\n";
                    } else {
                        echo "❌ 削除失敗: $file\n";
                    }
                } else {
                    echo "⚠️  存在しない: $file\n";
                }
            }
            
            // 2. storage/framework/views を削除
            echo "\n=== storage/framework/views をクリア ===\n";
            $viewsDir = 'storage/framework/views';
            if (is_dir($viewsDir)) {
                $count = 0;
                foreach (glob("$viewsDir/*.php") as $file) {
                    if (unlink($file)) {
                        $count++;
                    }
                }
                echo "✅ 削除: $count ファイル\n";
            }
            
            // 3. storage/framework/cache を削除
            echo "\n=== storage/framework/cache をクリア ===\n";
            $cacheDir = 'storage/framework/cache/data';
            if (is_dir($cacheDir)) {
                $count = 0;
                foreach (glob("$cacheDir/*") as $file) {
                    if (is_file($file) && unlink($file)) {
                        $count++;
                    }
                }
                echo "✅ 削除: $count ファイル\n";
            }
            
            // 4. .env の確認
            echo "\n=== .env の確認 ===\n";
            if (file_exists('.env')) {
                $env = file_get_contents('.env');
                if (strpos($env, 'APP_DEBUG=false') !== false) {
                    echo "✅ APP_DEBUG=false に設定済み\n";
                } else {
                    echo "⚠️  APP_DEBUG=true になっています。false に変更してください。\n";
                }
                if (strpos($env, 'APP_ENV=production') !== false) {
                    echo "✅ APP_ENV=production に設定済み\n";
                } else {
                    echo "⚠️  APP_ENV が production ではありません\n";
                }
            }
            
            // 5. Artisan コマンドを試行
            echo "\n=== Artisan コマンドを実行 ===\n";
            $commands = ['config:clear', 'view:clear', 'route:clear', 'cache:clear'];
            foreach ($commands as $cmd) {
                $output = [];
                exec("php artisan $cmd 2>&1", $output, $return);
                if ($return === 0) {
                    echo "✅ php artisan $cmd: 成功\n";
                } else {
                    echo "⚠️  php artisan $cmd: " . implode(", ", $output) . "\n";
                }
            }
            
            echo "\n=== 完了 ===\n";
            echo "✅ すべての処理が完了しました\n";
            echo "\n次のステップ:\n";
            echo "1. ブラウザで https://komapay.p-kmt.com/ にアクセス\n";
            echo "2. まだエラーが出る場合は、サーバーを5分待ってから再度アクセス\n";
            echo "3. このファイル (emergency-fix.php) を必ず削除してください\n";
            
            echo '</pre>';
            echo '<div class="success"><strong>✅ 処理完了！</strong><br>ページを更新してエラーが解消されたか確認してください。</div>';
        }
        
        if ($_GET['action'] === 'info') {
            echo '<h2>📊 システム情報</h2><pre>';
            echo "PHP Version: " . phpversion() . "\n";
            echo "Current Directory: " . getcwd() . "\n";
            echo "Laravel Root: " . $laravelRoot . "\n\n";
            
            echo "=== open_basedir 設定 ===\n";
            $openBasedir = ini_get('open_basedir');
            echo "open_basedir: " . ($openBasedir ?: '(設定なし)') . "\n\n";
            
            echo "=== 重要なファイルの存在確認 ===\n";
            $checkFiles = [
                '.env',
                '.user.ini',
                'bootstrap/cache/config.php',
                'artisan',
                'public/index.php'
            ];
            foreach ($checkFiles as $file) {
                echo ($file . ": " . (file_exists($file) ? "✅ 存在" : "❌ なし") . "\n");
            }
            
            echo "\n=== ディレクトリ権限 ===\n";
            $checkDirs = ['storage', 'bootstrap/cache'];
            foreach ($checkDirs as $dir) {
                if (is_dir($dir)) {
                    echo "$dir: " . substr(sprintf('%o', fileperms($dir)), -4) . "\n";
                }
            }
            
            echo '</pre>';
        }
    } else {
        ?>
        <div class="warning">
            <strong>⚠️  注意</strong><br>
            このツールは緊急時のみ使用してください。<br>
            実行後、必ずこのファイルを削除してください。
        </div>
        
        <h2>🔧 実行メニュー</h2>
        <a href="?action=clear_all" class="btn">🧹 すべてのキャッシュをクリア</a>
        <a href="?action=info" class="btn">📊 システム情報を表示</a>
        
        <h2>📝 手順</h2>
        <ol>
            <li>「すべてのキャッシュをクリア」をクリック</li>
            <li>完了メッセージが表示されるまで待つ</li>
            <li>メインサイト (https://komapay.p-kmt.com/) にアクセスして確認</li>
            <li>エラーが解消されたら、このファイルを削除</li>
        </ol>
        
        <div class="error">
            <strong>🔒 セキュリティ</strong><br>
            このファイルは必ず実行後に削除してください！<br>
            ファイル名: <code>public/emergency-fix.php</code>
        </div>
        <?php
    }
    ?>
</body>
</html>
