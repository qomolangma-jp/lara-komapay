<?php
/**
 * 販売者ユーザー作成スクリプト
 * アクセス: http://localhost:8000/quick-create-seller.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>";
echo "<html lang='ja'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Create Seller User</title>";
echo "<style>body { font-family: Arial; max-width: 800px; margin: 50px auto; padding: 20px; } .success { color: green; } .error { color: red; } .info { background: #e3f2fd; padding: 15px; margin: 15px 0; border-radius: 5px; }</style>";
echo "</head>";
echo "<body>";
echo "<h1>🏪 販売者ユーザー作成</h1>";

try {
    // 既存のsellerユーザーをチェック
    $existingSeller = User::where('username', 'seller')->first();
    
    if ($existingSeller) {
        echo "<div class='info'>";
        echo "<strong>⚠️ seller ユーザーは既に存在します</strong><br><br>";
        echo "ID: {$existingSeller->id}<br>";
        echo "Username: {$existingSeller->username}<br>";
        echo "Name: {$existingSeller->name_2nd} {$existingSeller->name_1st}<br>";
        echo "Shop Name: " . ($existingSeller->shop_name ?? 'N/A') . "<br>";
        echo "Status: " . ($existingSeller->status ?? 'N/A') . "<br>";
        echo "Created: {$existingSeller->created_at}<br>";
        echo "</div>";
        
        echo "<h3>パスワードをリセットしますか？</h3>";
        echo "<form method='POST'>";
        echo "<button type='submit' name='reset_password' style='background: #ff9800; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;'>パスワードを 'seller' にリセット</button>";
        echo "</form>";
    } else {
        // sellerユーザーを作成
        $seller = User::create([
            'username' => 'seller',
            'password' => Hash::make('seller'),
            'is_admin' => false,
            'name_2nd' => '販売',
            'name_1st' => '次郎',
            'shop_name' => '学食A店舗',
            'status' => 'seller',
        ]);
        
        echo "<div class='success'>";
        echo "<h2>✅ seller ユーザーを作成しました！</h2>";
        echo "<strong>ログイン情報:</strong><br>";
        echo "Username: <strong>seller</strong><br>";
        echo "Password: <strong>seller</strong><br>";
        echo "Shop Name: 学食A店舗<br>";
        echo "</div>";
    }
    
    // パスワードリセット処理
    if (isset($_POST['reset_password']) && $existingSeller) {
        $existingSeller->password = Hash::make('seller');
        $existingSeller->save();
        
        echo "<div class='success'>";
        echo "<h2>✅ パスワードをリセットしました！</h2>";
        echo "Username: <strong>seller</strong><br>";
        echo "Password: <strong>seller</strong><br>";
        echo "</div>";
    }
    
    // 全ユーザー一覧
    echo "<h3>📋 全ユーザー一覧</h3>";
    $users = User::all();
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Username</th><th>Name</th><th>Shop Name</th><th>Status</th><th>Admin</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user->id}</td>";
        echo "<td><strong>{$user->username}</strong></td>";
        echo "<td>{$user->name_2nd} {$user->name_1st}</td>";
        echo "<td>" . ($user->shop_name ?? '-') . "</td>";
        echo "<td>" . ($user->status ?? 'N/A') . "</td>";
        echo "<td>" . ($user->is_admin ? '✓' : '') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<div class='info' style='margin-top: 30px;'>";
    echo "<strong>💡 次のステップ:</strong><br>";
    echo "1. <a href='/login'>ログインページ</a>にアクセス<br>";
    echo "2. 「販売者」ボタンをクリック（自動入力）<br>";
    echo "3. または手動で Username: <code>seller</code> / Password: <code>seller</code> を入力<br>";
    echo "4. ログイン成功後、販売者管理画面にリダイレクト<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ エラーが発生しました</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "</body>";
echo "</html>";
