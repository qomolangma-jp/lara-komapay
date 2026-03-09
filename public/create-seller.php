<?php
/**
 * ユーザー確認・作成スクリプト
 * アクセス: http://localhost:8000/create-seller.php
 * または: https://komapay.p-kmt.com/create-seller.php
 */

// Basic認証
$username = 'admin';
$password = 'admin2026';

if (!isset($_SERVER['PHP_AUTH_USER']) || 
    $_SERVER['PHP_AUTH_USER'] !== $username || 
    $_SERVER['PHP_AUTH_PW'] !== $password) {
    header('WWW-Authenticate: Basic realm="Create Seller Tool"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Authentication required';
    exit;
}

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Seller User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">🏪 販売者ユーザー管理</h4>
                    </div>
                    <div class="card-body">

<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'create_seller') {
        // 既存のsellerユーザーをチェック
        $existingSeller = User::where('username', 'seller')->first();
        
        if ($existingSeller) {
            echo '<div class="alert alert-warning">';
            echo '<strong>⚠️ seller ユーザーは既に存在します</strong><br>';
            echo 'ID: ' . $existingSeller->id . '<br>';
            echo 'Username: ' . $existingSeller->username . '<br>';
            echo 'Status: ' . ($existingSeller->status ?? 'N/A') . '<br>';
            echo 'Shop Name: ' . ($existingSeller->shop_name ?? 'N/A') . '<br>';
            echo '</div>';
        } else {
            // sellerユーザーを作成
            try {
                $seller = User::create([
                    'username' => 'seller',
                    'password' => Hash::make('seller'),
                    'is_admin' => false,
                    'name_2nd' => '販売',
                    'name_1st' => '次郎',
                    'shop_name' => '学食A店舗',
                    'status' => 'seller',
                ]);
                
                echo '<div class="alert alert-success">';
                echo '<strong>✅ seller ユーザーを作成しました！</strong><br>';
                echo 'Username: seller<br>';
                echo 'Password: seller<br>';
                echo 'Shop Name: 学食A店舗<br>';
                echo '</div>';
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">';
                echo '<strong>❌ エラーが発生しました</strong><br>';
                echo htmlspecialchars($e->getMessage());
                echo '</div>';
            }
        }
    }
    
    if ($_POST['action'] === 'reset_password') {
        $username = $_POST['username'] ?? '';
        $user = User::where('username', $username)->first();
        
        if ($user) {
            $newPassword = 'seller';
            $user->password = Hash::make($newPassword);
            $user->save();
            
            echo '<div class="alert alert-success">';
            echo '<strong>✅ パスワードをリセットしました</strong><br>';
            echo 'Username: ' . htmlspecialchars($username) . '<br>';
            echo 'New Password: ' . $newPassword . '<br>';
            echo '</div>';
        } else {
            echo '<div class="alert alert-danger">';
            echo '<strong>❌ ユーザーが見つかりません</strong>';
            echo '</div>';
        }
    }
}

// 全ユーザーを表示
$users = User::all();

echo '<h5 class="mt-4">📋 既存ユーザー一覧</h5>';
echo '<div class="table-responsive">';
echo '<table class="table table-striped table-hover">';
echo '<thead class="table-dark">';
echo '<tr>';
echo '<th>ID</th>';
echo '<th>Username</th>';
echo '<th>姓名</th>';
echo '<th>Shop Name</th>';
echo '<th>Status</th>';
echo '<th>Admin</th>';
echo '<th>操作</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

foreach ($users as $user) {
    echo '<tr>';
    echo '<td>' . $user->id . '</td>';
    echo '<td><strong>' . htmlspecialchars($user->username) . '</strong></td>';
    echo '<td>' . htmlspecialchars($user->name_2nd . ' ' . $user->name_1st) . '</td>';
    echo '<td>' . htmlspecialchars($user->shop_name ?? '-') . '</td>';
    echo '<td>';
    if ($user->status === 'seller') {
        echo '<span class="badge bg-success">seller</span>';
    } elseif ($user->status === 'student') {
        echo '<span class="badge bg-primary">student</span>';
    } else {
        echo '<span class="badge bg-secondary">' . ($user->status ?? 'N/A') . '</span>';
    }
    echo '</td>';
    echo '<td>' . ($user->is_admin ? '<span class="badge bg-danger">Admin</span>' : '-') . '</td>';
    echo '<td>';
    echo '<form method="POST" style="display:inline;">';
    echo '<input type="hidden" name="action" value="reset_password">';
    echo '<input type="hidden" name="username" value="' . htmlspecialchars($user->username) . '">';
    echo '<button type="submit" class="btn btn-sm btn-warning">Reset Password</button>';
    echo '</form>';
    echo '</td>';
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';
echo '</div>';

?>

                        <hr>

                        <h5>🔧 操作</h5>
                        
                        <form method="POST" class="mb-3">
                            <input type="hidden" name="action" value="create_seller">
                            <button type="submit" class="btn btn-success btn-lg">
                                🏪 seller ユーザーを作成
                            </button>
                            <p class="text-muted mt-2 mb-0">
                                Username: seller / Password: seller / Shop Name: 学食A店舗
                            </p>
                        </form>

                        <div class="alert alert-info">
                            <strong>💡 使い方</strong><br>
                            1. 上記のボタンで seller ユーザーを作成<br>
                            2. または、既存ユーザーのパスワードをリセット<br>
                            3. ログイン画面で username と password を入力してログイン
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
