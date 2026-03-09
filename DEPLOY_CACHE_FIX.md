# 本番サーバーでのキャッシュクリア手順

## 問題の原因
Docker開発環境のパス `/var/www/html` がキャッシュされていました。

## ✅ 実行済み（ローカル）
- bootstrap/cache/*.php を削除
- storage/framework/views/*.php を削除
- `.user.ini` の open_basedir 制限を無効化
- `APP_DEBUG=false` に設定

## 📤 本番サーバーでの手順

### 🌐 方法1: ブラウザから実行（最も簡単）

1. `public/clear-cache.php` をサーバーにアップロード
2. ファイルを開き、`SECRET_KEY` を好きな文字列に変更
3. ブラウザで以下にアクセス：
   ```
   https://komapay.p-kmt.com/clear-cache.php?key=your-secret-key-2024
   ```
   （`your-secret-key-2024` はファイル内で設定したキーに変更）
4. **実行後、必ずファイルを削除！**

### 💻 方法2: スクリプトを使用（SSH必要）

1. `clear-cache.sh` をサーバーにアップロード
2. SSH で以下を実行：

```bash
cd /home/bungoyoshiba/domains/komapay.p-kmt.com/public_html
bash clear-cache.sh
```

### 🔧 方法3: 手動実行

SSHまたはcPanelのTerminalで以下を**順番に**実行：

```bash
cd /home/bungoyoshiba/domains/komapay.p-kmt.com/public_html

# 全キャッシュをクリア
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan cache:clear

# 手動でキャッシュファイルを削除
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/packages.php
rm -f bootstrap/cache/services.php
rm -f storage/framework/views/*.php

# Composerオートロード再生成
composer dump-autoload

# 権限設定
chmod -R 775 storage bootstrap/cache

# 本番用に最適化（任意）
php artisan config:cache
php artisan route:cache
```

## 🔧 必要なファイル

以下のファイルを**必ず**アップロード：
- ✅ `.env` (APP_DEBUG=false に更新済み)
- ✅ `.user.ini` (open_basedir 制限を無効化)
- ✅ `public/.user.ini` (open_basedir 制限を無効化)
- ✅ `clear-cache.sh` (スクリプト)

## ⚠️ 注意事項

1. `.user.ini` ファイルは反映に数分かかる場合があります
2. PHPプロセスが再起動されるまで待つ必要があります
3. それでも解決しない場合は、サーバーのPHP設定で `open_basedir` を管理者に無効化してもらう必要があります

## 🆘 サポートが必要な場合

cPanel → MultiPHP INI Editor から `open_basedir` を **Off** に設定してもらうよう、ホスティング会社に問い合わせてください。
