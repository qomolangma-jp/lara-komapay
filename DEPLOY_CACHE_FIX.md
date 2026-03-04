# 本番サーバーでのキャッシュクリア手順

## 問題の原因
Docker開発環境のパス `/var/www/html` がキャッシュされていました。

## ✅ 実行済み（ローカル）
- bootstrap/cache/*.php を削除
- storage/framework/views/*.php を削除

## 📤 本番サーバーで実行すべきコマンド

サーバーにアップロード後、SSH接続またはcPanelのTerminalで以下を実行：

```bash
# プロジェクトディレクトリに移動
cd /home/bungoyoshiba/public_html

# キャッシュをすべてクリア
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# オプション：本番環境用に最適化（推奨）
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 🔧 .user.ini ファイルも忘れずに

以下のファイルをアップロード：
- `.user.ini` (ルート)
- `public/.user.ini` (publicフォルダ)

## ⚠️ 権限設定

storage と bootstrap/cache フォルダに書き込み権限を付与：
```bash
chmod -R 775 storage bootstrap/cache
```

これで `open_basedir` エラーが解決するはずです！
