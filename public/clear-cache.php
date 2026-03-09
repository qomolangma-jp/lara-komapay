<?php
/**
 * Laravel キャッシュクリアツール
 * ブラウザからアクセスして実行: https://komapay.p-kmt.com/clear-cache.php
 * 
 * セキュリティのため、実行後はこのファイルを削除してください
 */

// セキュリティキー（変更してください）
define('SECRET_KEY', 'your-secret-key-2024');

// GETパラメータでキーを確認
if (!isset($_GET['key']) || $_GET['key'] !== SECRET_KEY) {
    die('❌ アクセスが拒否されました。正しいキーを指定してください。');
}

echo '<h1>🧹 Laravel キャッシュクリア</h1>';
echo '<pre>';

// 現在のディレクトリを表示
echo "現在のディレクトリ: " . __DIR__ . "\n\n";

// Laravelのルートディレクトリ（publicの1つ上）
$laravelRoot = dirname(__DIR__);
chdir($laravelRoot);

echo "Laravelルート: " . getcwd() . "\n";
echo "========================================\n\n";

// Artisanコマンドを実行する関数
function runArtisan($command) {
    $output = [];
    $return = 0;
    exec("php artisan $command 2>&1", $output, $return);
    return [
        'output' => implode("\n", $output),
        'success' => $return === 0
    ];
}

// 1. 設定キャッシュをクリア
echo "1️⃣  設定キャッシュをクリア...\n";
$result = runArtisan('config:clear');
echo $result['output'] . "\n";
echo $result['success'] ? "✅ 成功\n\n" : "❌ 失敗\n\n";

// 2. ビューキャッシュをクリア
echo "2️⃣  ビューキャッシュをクリア...\n";
$result = runArtisan('view:clear');
echo $result['output'] . "\n";
echo $result['success'] ? "✅ 成功\n\n" : "❌ 失敗\n\n";

// 3. ルートキャッシュをクリア
echo "3️⃣  ルートキャッシュをクリア...\n";
$result = runArtisan('route:clear');
echo $result['output'] . "\n";
echo $result['success'] ? "✅ 成功\n\n" : "❌ 失敗\n\n";

// 4. アプリケーションキャッシュをクリア
echo "4️⃣  アプリケーションキャッシュをクリア...\n";
$result = runArtisan('cache:clear');
echo $result['output'] . "\n";
echo $result['success'] ? "✅ 成功\n\n" : "❌ 失敗\n\n";

// 5. 手動でファイルを削除
echo "5️⃣  キャッシュファイルを手動削除...\n";
$files = [
    'bootstrap/cache/config.php',
    'bootstrap/cache/packages.php',
    'bootstrap/cache/services.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        unlink($file);
        echo "  削除: $file\n";
    }
}
echo "✅ 完了\n\n";

// 6. storage/framework/views を削除
echo "6️⃣  コンパイル済みビューを削除...\n";
$viewsDir = 'storage/framework/views';
if (is_dir($viewsDir)) {
    $files = glob("$viewsDir/*.php");
    foreach ($files as $file) {
        unlink($file);
    }
    echo "  削除: " . count($files) . " ファイル\n";
}
echo "✅ 完了\n\n";

echo "========================================\n";
echo "✅ すべてのキャッシュをクリアしました！\n";
echo "========================================\n\n";

echo '<strong>⚠️  セキュリティのため、このファイル（clear-cache.php）を削除してください。</strong>';
echo '</pre>';
?>
