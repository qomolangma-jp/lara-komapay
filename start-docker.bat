@echo off
REM 学校食堂注文システム - Docker起動スクリプト

echo.
echo ======================================
echo Docker Compose 起動スクリプト
echo ======================================
echo.

REM カレントディレクトリをこのスクリプトのディレクトリに設定
cd /d %~dp0

echo [1/4] Docker Desktop の状態確認...
docker ps > nul 2>&1
if errorlevel 1 (
    echo.
    echo エラー: Docker Desktop が起動していません
    echo.
    echo 以下の手順で修正してください：
    echo 1. スタートメニューから Docker Desktop を起動
    echo 2. Docker が完全に起動するまで待つ（約30秒）
    echo 3. このスクリプトを再度実行
    echo.
    pause
    exit /b 1
)
echo [✓] Docker が起動中

echo.
echo [2/4] Docker Compose コンテナを起動...
docker-compose up -d
if errorlevel 1 (
    echo エラー: docker-compose up に失敗しました
    pause
    exit /b 1
)
echo [✓] コンテナ起動完了

echo.
echo [3/4] コンテナが起動するまで待機中...
timeout /t 10 /nobreak

echo.
echo [4/4] データベース初期化...
docker-compose exec -T web php artisan migrate --seed
if errorlevel 1 (
    echo.
    echo 注意: マイグレーション実行中にエラーが発生しました
    echo ブラウザでアクセス可能か確認してください
)

echo.
echo ======================================
echo セットアップ完了！
echo ======================================
echo.
echo アクセスURL:
echo   API:        http://localhost:8000/api
echo   phpMyAdmin: http://localhost:8081
echo.
echo デフォルトアカウント:
echo   管理者: admin / admin
echo   学生:  student / 1234
echo.
echo 停止する場合: docker-compose down
echo ログ確認:     docker-compose logs -f web
echo.
pause
