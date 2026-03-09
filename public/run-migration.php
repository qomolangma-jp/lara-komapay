<?php
/**
 * 本番サーバー用マイグレーション実行スクリプト
 * 
 * アクセス: https://komapay.p-kmt.com/run-migration.php
 * 
 * セキュリティ: Basic認証
 * ユーザー名: admin
 * パスワード: migrate2026
 */

// Basic認証
$username = 'admin';
$password = 'migrate2026';

if (!isset($_SERVER['PHP_AUTH_USER']) || 
    $_SERVER['PHP_AUTH_USER'] !== $username || 
    $_SERVER['PHP_AUTH_PW'] !== $password) {
    header('WWW-Authenticate: Basic realm="Migration Tool"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Authentication required';
    exit;
}

// Laravelのブートストラップ
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "<!DOCTYPE html>";
echo "<html lang='ja'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Migration Runner</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }";
echo ".container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo "h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }";
echo "pre { background: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 5px; overflow-x: auto; }";
echo ".output { margin-top: 20px; }";
echo ".success { color: #4CAF50; font-weight: bold; }";
echo ".error { color: #f44336; font-weight: bold; }";
echo ".info { color: #2196F3; }";
echo ".warning { background: #fff3cd; border-left: 4px solid #ffeb3b; padding: 15px; margin: 20px 0; }";
echo "</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";
echo "<h1>🚀 Laravel Migration Runner</h1>";

if (isset($_POST['run_migration'])) {
    echo "<div class='output'>";
    echo "<h2 class='info'>マイグレーション実行中...</h2>";
    echo "<pre>";
    
    // 出力バッファリングをオフにして、リアルタイムで表示
    ob_start();
    
    try {
        // マイグレーションコマンド実行
        $exitCode = $kernel->call('migrate', [
            '--force' => true,
        ]);
        
        $output = $kernel->output();
        
        if ($exitCode === 0) {
            echo "<span class='success'>✅ マイグレーション成功</span>\n\n";
        } else {
            echo "<span class='error'>❌ マイグレーション失敗 (Exit Code: {$exitCode})</span>\n\n";
        }
        
        echo htmlspecialchars($output);
        
    } catch (Exception $e) {
        echo "<span class='error'>❌ エラーが発生しました:</span>\n";
        echo htmlspecialchars($e->getMessage()) . "\n";
        echo htmlspecialchars($e->getTraceAsString());
    }
    
    ob_end_flush();
    
    echo "</pre>";
    echo "</div>";
    
    // キャッシュクリアリンク
    echo "<div class='warning'>";
    echo "<strong>⚠️ 次のステップ:</strong><br>";
    echo "マイグレーション後は、必ず<a href='/clear-cache.php'>キャッシュクリア</a>を実行してください。";
    echo "</div>";
    
} else {
    // マイグレーション実行フォーム
    echo "<div class='warning'>";
    echo "<strong>⚠️ 注意事項:</strong><br>";
    echo "このスクリプトはデータベースマイグレーションを実行します。<br>";
    echo "本番環境で実行する前に、必ずデータベースのバックアップを取得してください。";
    echo "</div>";
    
    echo "<form method='POST'>";
    echo "<h3>実行するマイグレーション</h3>";
    echo "<p>shop_name カラムを users テーブルに追加します。</p>";
    echo "<button type='submit' name='run_migration' style='background-color: #4CAF50; color: white; padding: 15px 32px; font-size: 16px; border: none; border-radius: 4px; cursor: pointer;'>";
    echo "🚀 マイグレーションを実行";
    echo "</button>";
    echo "</form>";
    
    // 現在のマイグレーション状態を表示
    echo "<h3 style='margin-top: 30px;'>現在のマイグレーション状態</h3>";
    echo "<pre>";
    try {
        ob_start();
        $kernel->call('migrate:status');
        $status = $kernel->output();
        echo htmlspecialchars($status);
        ob_end_flush();
    } catch (Exception $e) {
        echo "マイグレーション状態の取得に失敗しました: " . htmlspecialchars($e->getMessage());
    }
    echo "</pre>";
}

echo "</div>";
echo "</body>";
echo "</html>";
