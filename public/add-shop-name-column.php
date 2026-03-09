<?php
/**
 * shop_nameカラムを直接追加するスクリプト
 * 
 * アクセス: https://komapay.p-kmt.com/add-shop-name-column.php
 * またはローカル: http://localhost:8000/add-shop-name-column.php
 * 
 * セキュリティ: Basic認証
 * ユーザー名: admin
 * パスワード: addcolumn2026
 */

// Basic認証
$username = 'admin';
$password = 'addcolumn2026';

if (!isset($_SERVER['PHP_AUTH_USER']) || 
    $_SERVER['PHP_AUTH_USER'] !== $username || 
    $_SERVER['PHP_AUTH_PW'] !== $password) {
    header('WWW-Authenticate: Basic realm="Add Column Tool"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Authentication required';
    exit;
}

// データベース接続設定を読み込む
require __DIR__ . '/../vendor/autoload.php';

// .envファイルから設定を読み込む
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$host = $_ENV['DB_HOST'] ?? 'localhost';
$database = $_ENV['DB_DATABASE'] ?? 'cafeteria';
$dbUsername = $_ENV['DB_USERNAME'] ?? 'root';
$dbPassword = $_ENV['DB_PASSWORD'] ?? '';

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add shop_name Column</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px; 
            margin: 50px auto; 
            padding: 20px; 
            background: #f5f5f5; 
        }
        .container { 
            background: white; 
            padding: 30px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        h1 { 
            color: #333; 
            border-bottom: 3px solid #4CAF50; 
            padding-bottom: 10px; 
        }
        .success { 
            background: #d4edda; 
            border: 1px solid #c3e6cb; 
            color: #155724; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 20px 0; 
        }
        .error { 
            background: #f8d7da; 
            border: 1px solid #f5c6cb; 
            color: #721c24; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 20px 0; 
        }
        .info { 
            background: #d1ecf1; 
            border: 1px solid #bee5eb; 
            color: #0c5460; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 20px 0; 
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        button { 
            background-color: #4CAF50; 
            color: white; 
            padding: 15px 32px; 
            font-size: 16px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            margin: 10px 5px;
        }
        button:hover { 
            background-color: #45a049; 
        }
        .danger { 
            background-color: #f44336; 
        }
        .danger:hover { 
            background-color: #da190b; 
        }
        pre { 
            background: #f4f4f4; 
            padding: 15px; 
            border-radius: 5px; 
            overflow-x: auto;
            border-left: 4px solid #4CAF50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Add shop_name Column</h1>

<?php

if (isset($_POST['add_column'])) {
    try {
        // データベース接続
        $pdo = new PDO(
            "mysql:host={$host};dbname={$database};charset=utf8mb4",
            $dbUsername,
            $dbPassword,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        echo "<div class='info'><strong>📊 データベース接続成功</strong><br>";
        echo "Host: {$host}<br>Database: {$database}</div>";

        // カラムが既に存在するかチェック
        $checkSql = "SELECT COLUMN_NAME 
                     FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_SCHEMA = '{$database}' 
                     AND TABLE_NAME = 'users' 
                     AND COLUMN_NAME = 'shop_name'";
        
        $stmt = $pdo->query($checkSql);
        $exists = $stmt->fetch();

        if ($exists) {
            echo "<div class='warning'><strong>⚠️ shop_nameカラムは既に存在します</strong></div>";
        } else {
            // カラムを追加
            $alterSql = "ALTER TABLE users 
                         ADD COLUMN shop_name VARCHAR(100) NULL 
                         AFTER name_1st";
            
            $pdo->exec($alterSql);
            
            echo "<div class='success'><strong>✅ shop_nameカラムを追加しました！</strong></div>";
            
            // マイグレーションテーブルにも記録
            $migrationName = '2026_03_09_000000_add_shop_name_to_users_table';
            $batch = $pdo->query("SELECT MAX(batch) as max_batch FROM migrations")->fetch();
            $nextBatch = ($batch['max_batch'] ?? 0) + 1;
            
            $insertMigration = "INSERT INTO migrations (migration, batch) 
                               VALUES ('{$migrationName}', {$nextBatch})";
            $pdo->exec($insertMigration);
            
            echo "<div class='info'><strong>📝 マイグレーション記録を追加しました</strong><br>";
            echo "Migration: {$migrationName}<br>Batch: {$nextBatch}</div>";
        }

        // テーブル構造を表示
        echo "<h3>📋 users テーブルの構造</h3>";
        $columns = $pdo->query("SHOW COLUMNS FROM users");
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($col = $columns->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td><strong>{$col['Field']}</strong></td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";

    } catch (PDOException $e) {
        echo "<div class='error'><strong>❌ エラーが発生しました</strong><br>";
        echo "Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }

} else {
    // 実行前の確認画面
    ?>
    <div class="warning">
        <strong>⚠️ 注意事項</strong><br>
        このスクリプトは users テーブルに shop_name カラムを直接追加します。<br>
        <ul>
            <li>カラム名: shop_name</li>
            <li>データ型: VARCHAR(100)</li>
            <li>NULL許可: YES</li>
            <li>位置: name_1st カラムの後</li>
        </ul>
        本番環境で実行する前に、必ずデータベースのバックアップを取得してください。
    </div>

    <form method="POST">
        <button type="submit" name="add_column">
            🚀 shop_name カラムを追加
        </button>
    </form>

    <h3>📊 データベース接続情報</h3>
    <pre>Host: <?php echo htmlspecialchars($host); ?>
Database: <?php echo htmlspecialchars($database); ?>
Username: <?php echo htmlspecialchars($dbUsername); ?></pre>

    <?php
    try {
        $pdo = new PDO(
            "mysql:host={$host};dbname={$database};charset=utf8mb4",
            $dbUsername,
            $dbPassword,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // 現在のテーブル構造を表示
        echo "<h3>📋 現在の users テーブルの構造</h3>";
        $columns = $pdo->query("SHOW COLUMNS FROM users");
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($col = $columns->fetch(PDO::FETCH_ASSOC)) {
            $highlight = ($col['Field'] === 'shop_name') ? 'style="background-color: #ffeb3b;"' : '';
            echo "<tr {$highlight}>";
            echo "<td><strong>{$col['Field']}</strong></td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";

    } catch (PDOException $e) {
        echo "<div class='error'>データベース接続エラー: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
?>

        <div class="info" style="margin-top: 30px;">
            <strong>💡 次のステップ</strong><br>
            カラム追加後は、以下を実行してください：<br>
            1. <a href="/clear-cache.php">キャッシュクリア</a>でアプリケーションキャッシュをクリア<br>
            2. ユーザー登録画面で shop_name を入力してテスト
        </div>

    </div>
</body>
</html>
