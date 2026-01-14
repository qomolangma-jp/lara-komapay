@echo off
echo Composer依存関係をインストール中...
docker exec cafeteria_laravel_web composer install --working-dir=/var/www/html --no-interaction --prefer-dist --optimize-autoloader
echo.
echo 完了！ブラウザを再読み込みしてください。
pause
