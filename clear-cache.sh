#!/bin/bash

# Laravel キャッシュクリアスクリプト（本番サーバー用）
# 使用方法: bash clear-cache.sh

echo "=== Laravel キャッシュクリア開始 ==="

# 設定キャッシュをクリア
echo "1. 設定キャッシュをクリア..."
php artisan config:clear

# ビューキャッシュをクリア
echo "2. ビューキャッシュをクリア..."
php artisan view:clear

# ルートキャッシュをクリア
echo "3. ルートキャッシュをクリア..."
php artisan route:clear

# アプリケーションキャッシュをクリア
echo "4. アプリケーションキャッシュをクリア..."
php artisan cache:clear

# Composerのオートロードを再生成
echo "5. Composerオートロード再生成..."
composer dump-autoload

# 手動でbootstrap/cacheを削除（念のため）
echo "6. bootstrap/cache を手動削除..."
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/packages.php
rm -f bootstrap/cache/services.php

# storage/framework/views を削除
echo "7. storage/framework/views を削除..."
rm -f storage/framework/views/*.php

# 権限を設定
echo "8. 権限を設定..."
chmod -R 775 storage bootstrap/cache

echo ""
echo "=== クリア完了！ ==="
echo ""
echo "次に本番用に最適化する場合は以下を実行："
echo "php artisan config:cache"
echo "php artisan route:cache"
echo "php artisan view:cache"
