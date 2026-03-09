<?php
/**
 * productsテーブルにseller_idカラムを追加するツール
 * ブラウザから直接アクセス: https://komapay.p-kmt.com/add-seller-id-column.php
 */

// データベース接続設定
$host = 'localhost';
$dbname = 'xs524268_cafeteria';
$username = 'xs524268_cafe';
$password = 'cafeteria_pass2024';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>seller_idカラム追加ツール</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
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
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 4px; margin: 15px 0; }
        button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px 0 0;
        }
        button:hover { background: #45a049; }
        button.danger { background: #dc3545; }
        button.danger:hover { background: #c82333; }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            border: 1px solid #ddd;
        }
        .step {
            background: #e8f5e9;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #4CAF50;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 seller_idカラム追加ツール</h1>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            try {
                $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                if ($_POST['action'] === 'check') {
                    echo '<div class="info"><strong>📋 現在のテーブル構造を確認中...</strong></div>';
                    
                    $stmt = $pdo->query("DESCRIBE products");
                    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $hasSellerIdColumn = false;
                    foreach ($columns as $column) {
                        if ($column['Field'] === 'seller_id') {
                            $hasSellerIdColumn = true;
                            break;
                        }
                    }
                    
                    if ($hasSellerIdColumn) {
                        echo '<div class="success">✅ <strong>seller_idカラムは既に存在しています！</strong></div>';
                    } else {
                        echo '<div class="warning">⚠️ <strong>seller_idカラムが見つかりません。追加が必要です。</strong></div>';
                    }
                    
                    echo '<h3>現在のproductsテーブルの構造:</h3><pre>';
                    foreach ($columns as $column) {
                        echo sprintf(
                            "%-20s %-20s %-10s %-10s %s\n",
                            $column['Field'],
                            $column['Type'],
                            $column['Null'],
                            $column['Key'],
                            $column['Default'] ?? 'NULL'
                        );
                    }
                    echo '</pre>';
                    
                } elseif ($_POST['action'] === 'add') {
                    echo '<div class="info"><strong>🔨 seller_idカラムを追加中...</strong></div>';
                    
                    // seller_idカラムが既に存在するかチェック
                    $stmt = $pdo->query("DESCRIBE products");
                    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $hasSellerIdColumn = false;
                    foreach ($columns as $column) {
                        if ($column['Field'] === 'seller_id') {
                            $hasSellerIdColumn = true;
                            break;
                        }
                    }
                    
                    if ($hasSellerIdColumn) {
                        echo '<div class="warning">⚠️ seller_idカラムは既に存在しています。</div>';
                    } else {
                        // seller_idカラムを追加
                        $sql = "ALTER TABLE products ADD COLUMN seller_id BIGINT UNSIGNED NULL AFTER category";
                        $pdo->exec($sql);
                        
                        // 外部キー制約を追加
                        try {
                            $sql = "ALTER TABLE products ADD CONSTRAINT products_seller_id_foreign 
                                    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE SET NULL";
                            $pdo->exec($sql);
                            echo '<div class="success">✅ <strong>seller_idカラムと外部キー制約を追加しました！</strong></div>';
                        } catch (PDOException $e) {
                            echo '<div class="success">✅ <strong>seller_idカラムを追加しました！</strong></div>';
                            echo '<div class="warning">⚠️ 外部キー制約の追加に失敗しました（既に存在する可能性があります）: ' . htmlspecialchars($e->getMessage()) . '</div>';
                        }
                    }
                    
                    // 結果を表示
                    echo '<h3>更新後のテーブル構造:</h3>';
                    $stmt = $pdo->query("DESCRIBE products");
                    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo '<pre>';
                    foreach ($columns as $column) {
                        $highlight = $column['Field'] === 'seller_id' ? '→ ' : '  ';
                        echo sprintf(
                            "%s%-20s %-20s %-10s %-10s %s\n",
                            $highlight,
                            $column['Field'],
                            $column['Type'],
                            $column['Null'],
                            $column['Key'],
                            $column['Default'] ?? 'NULL'
                        );
                    }
                    echo '</pre>';
                }
                
            } catch (PDOException $e) {
                echo '<div class="error"><strong>❌ エラーが発生しました:</strong><br>' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
        ?>

        <div class="info">
            <strong>📖 このツールについて:</strong><br>
            productsテーブルに<code>seller_id</code>カラムを追加します。<br>
            このカラムは商品を登録した販売者を記録するために使用されます。
        </div>

        <div class="step">
            <strong>ステップ1:</strong> まず現在のテーブル構造を確認してください
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="check">
            <button type="submit">📋 テーブル構造を確認</button>
        </form>

        <div class="step">
            <strong>ステップ2:</strong> seller_idカラムが存在しない場合は追加してください
        </div>

        <form method="POST" onsubmit="return confirm('seller_idカラムを追加しますか？');">
            <input type="hidden" name="action" value="add">
            <button type="submit" class="danger">🔨 seller_idカラムを追加</button>
        </form>

        <div class="warning" style="margin-top: 30px;">
            <strong>⚠️ 注意:</strong><br>
            • このツールは本番環境のデータベースを変更します<br>
            • 必ず事前にバックアップを取得してください<br>
            • 作業完了後はこのファイルを削除することを推奨します
        </div>
    </div>
</body>
</html>
