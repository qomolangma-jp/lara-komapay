@echo off
REM Docker Compose ログ表示スクリプト

echo.
echo ======================================
echo Docker Compose ログ（リアルタイム）
echo ======================================
echo.
echo Ctrl+C で終了
echo.

cd /d %~dp0

docker-compose logs -f web
