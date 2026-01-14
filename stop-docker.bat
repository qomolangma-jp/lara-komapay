@echo off
REM Docker Compose コンテナ停止スクリプト

echo.
echo ======================================
echo Docker Compose コンテナを停止...
echo ======================================
echo.

cd /d %~dp0

docker-compose down

echo.
echo [✓] コンテナを停止しました
echo.
pause
