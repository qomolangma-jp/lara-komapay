# 本番環境デプロイ手順

## 本番環境情報
- サーバー: komapay.p-kmt.com
- パス: /home/bungoyoshiba/domains/komapay.p-kmt.com/public_html

## デプロイ手順

### 1. ローカルの変更をGitにコミット（既にGitを使用している場合）

```powershell
git add .
git commit -m "販売者機能追加: seller_idカラムとユーザー管理編集機能"
git push origin main
```

### 2. 本番サーバーにSSH接続

```bash
# SSHで本番サーバーに接続
ssh ユーザー名@komapay.p-kmt.com
```

### 3. 本番環境でコードを更新

```bash
# プロジェクトディレクトリに移動
cd /home/bungoyoshiba/domains/komapay.p-kmt.com/public_html

# Gitを使用している場合
git pull origin main

# Gitを使用していない場合は、FTPなどで以下のファイルをアップロード：
# - database/migrations/2026_02_10_000000_add_seller_to_products_table.php
# - app/Http/Controllers/Api/AuthController.php
# - app/Http/Controllers/Api/ProductController.php
# - app/Models/Product.php
# - app/Models/User.php
# - resources/views/master_admin/users.blade.php
# - resources/views/master_admin/products.blade.php
# - routes/api.php
# - app/Http/Controllers/MasterController.php
```

### 4. 本番環境でマイグレーションを実行

```bash
# プロジェクトディレクトリにいることを確認
cd /home/bungoyoshiba/domains/komapay.p-kmt.com/public_html

# マイグレーションを実行
php artisan migrate

# キャッシュをクリア
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# キャッシュを再生成（本番環境では推奨）
php artisan config:cache
php artisan route:cache
```

### 5. データベース確認（オプション）

```bash
# MySQLにログイン
mysql -u データベースユーザー名 -p

# データベースを選択
USE school_cafeteria;

# productsテーブルの構造を確認
DESCRIBE products;

# seller_idカラムが存在することを確認
# 出力に以下のような行があればOK：
# | seller_id | bigint unsigned | YES  | MUL | NULL    |       |

# 終了
exit;
```

### 6. ファイルの権限を確認

```bash
# storageディレクトリの権限を確認
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# 所有者を変更（Webサーバーのユーザーに）
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache
```

## トラブルシューティング

### エラー: "Permission denied"
```bash
# 権限を再設定
chmod -R 775 storage bootstrap/cache
```

### エラー: "Class not found"
```bash
# Composerの依存関係を更新
composer install --no-dev --optimize-autoloader
```

### エラー: "SQLSTATE[HY000] [2002] Connection refused"
```bash
# .envファイルのデータベース設定を確認
cat .env | grep DB_

# 必要に応じて、.envを編集
nano .env
```

## 確認方法

1. ブラウザで https://komapay.p-kmt.com/master/products にアクセス
2. 商品編集で販売者が選択できることを確認
3. 商品一覧で販売者名が表示されることを確認
4. ブラウザで https://komapay.p-kmt.com/master/users にアクセス
5. ユーザー編集ボタンが動作することを確認

## 注意事項

⚠️ **本番環境での作業前に必ずバックアップを取ってください**

```bash
# データベースのバックアップ
mysqldump -u ユーザー名 -p school_cafeteria > backup_$(date +%Y%m%d_%H%M%S).sql

# ファイルのバックアップ
tar -czf backup_files_$(date +%Y%m%d_%H%M%S).tar.gz .
```
