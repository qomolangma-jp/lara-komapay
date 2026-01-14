# Docker Desktopセットアップガイド - Windows

## 📋 現在のシステム状態

- ✅ Docker がインストール済み
- ✅ docker.exe が利用可能
- ⚠️ PowerShellに不安定性を確認（コンソール再起動推奨）

## 🚀 Docker Desktop を接続するステップ

### 1. Docker Desktop を起動

**Windows 11/10**:
1. **スタートメニュー** を開く
2. **Docker Desktop** を検索
3. アプリケーションをクリックして起動

起動後、画面右下のシステムトレイにDockerアイコンが表示されます。

```
システムトレイ: 🐳 Docker
```

### 2. Docker が起動完了を待つ

Docker Desktopが完全に起動するまで **30〜60秒** 待ちます。

以下のコマンドで確認（PowerShellまたはコマンドプロンプト）:

```powershell
docker ps
```

**期待される出力**:
```
CONTAINER ID   IMAGE     COMMAND   CREATED   STATUS    PORTS     NAMES
```

### 3. Laravelコンテナを起動

```powershell
cd c:\Users\ko2020risu\Desktop\php-komatsu_caffe\laravel-app
docker-compose up -d
```

**期待される出力**:
```
Creating network "laravel-app_cafeteria_network" with driver "bridge"
Creating cafeteria_laravel_db    ... done
Creating cafeteria_laravel_web   ... done
Creating cafeteria_laravel_phpmyadmin ... done
```

### 4. コンテナが起動したことを確認

```powershell
docker-compose ps
```

**期待される出力**:
```
NAME                          COMMAND                  SERVICE             STATUS              PORTS
cafeteria_laravel_web         "apache2-foreground"     web                 Up 2 minutes        0.0.0.0:8000->80/tcp
cafeteria_laravel_db          "docker-entrypoint.s…"   db                  Up 2 minutes        0.0.0.0:3306->3306/tcp
cafeteria_laravel_phpmyadmin  "/docker-entrypoint.…"   phpmyadmin          Up 2 minutes        0.0.0.0:8081->80/tcp
```

### 5. データベース初期化

```powershell
# Webコンテナに接続してマイグレーション実行
docker-compose exec web php artisan migrate --seed
```

**期待される出力**:
```
Migrated: 2024_01_01_000000_create_users_table
Migrated: 2024_01_01_000100_create_products_table
Migrated: 2024_01_01_000200_create_orders_table
Migrated: 2024_01_01_000300_create_order_details_table
Seeding: Database\Seeders\DatabaseSeeder
```

## ✅ セットアップ完了

すべてのコンテナが起動し、データベース初期化完了後、以下にアクセスできます：

- **API**: http://localhost:8000/api
- **phpMyAdmin**: http://localhost:8081

## 🔍 トラブルシューティング

### Docker Desktop が起動しない

**Windows 10 の場合 - WSL2 (Windows Subsystem for Linux 2) が必要**:

1. PowerShell（管理者）を開く
2. 以下を実行:
   ```powershell
   wsl --install
   ```
3. マシンを再起動
4. Docker Desktop を再度起動

### ポート 8000 / 3306 が既に使用中

別のアプリケーションがポートを占有しています。

**解決策 A**: ポートを変更する

`docker-compose.yml` を編集:

```yaml
services:
  web:
    ports:
      - "8001:80"    # 8000 → 8001 に変更
  db:
    ports:
      - "3307:3306"  # 3306 → 3307 に変更
```

その後再起動:
```powershell
docker-compose down
docker-compose up -d
```

**解決策 B**: 使用中のアプリケーションを特定する

```powershell
# Windows のコマンドプロンプトで
netstat -ano | findstr :8000
```

### "Cannot connect to Docker daemon"

Docker Desktop が起動していない、または WSL2 に問題があります。

```powershell
# Docker daemon の状態確認
docker version
```

**エラーが出る場合**:
1. Docker Desktop を再起動
2. スタートメニュー → 設定 → アプリケーション
3. Docker Desktop をアンインストール後、再インストール

### PowerShell が不安定

コマンドプロンプト（cmd）を使用してください：

```cmd
cd c:\Users\ko2020risu\Desktop\php-komatsu_caffe\laravel-app
docker-compose up -d
docker-compose exec web php artisan migrate --seed
```

## 📊 Docker Desktop ダッシュボード

Docker Desktop には視覚的なダッシュボードがあります：

1. システムトレイの Docker アイコンをクリック
2. **Docker Dashboard** を選択
3. **Containers** タブでコンテナの状態・ログ確認可能

## 🔧 便利なコマンド

```powershell
# コンテナ起動
docker-compose up -d

# コンテナ停止
docker-compose down

# コンテナの状態確認
docker-compose ps

# Web コンテナのログ確認
docker-compose logs -f web

# DB コンテナのログ確認
docker-compose logs -f db

# コンテナ内でコマンド実行
docker-compose exec web php artisan tinker
docker-compose exec db mysql -u cafeteria_user -p

# 全データ削除してリセット
docker-compose down -v
docker-compose up -d
docker-compose exec web php artisan migrate --seed
```

## 📞 Docker Desktop リソース設定

**パフォーマンスが遅い場合**:

1. システムトレイの Docker アイコン → **Settings**
2. **Resources** タブ
3. 以下を設定：
   - **CPUs**: 4コア以上推奨
   - **Memory**: 4GB 以上推奨
   - **Disk image size**: 30GB 以上推奨

## 🎯 次のステップ

Docker が正常に起動したら：

1. [API仕様書](./API_SPEC.md) を確認
2. テストクライアント（Postman等）でAPI呼び出し
3. フロントエンド開発開始

## 💾 Docker イメージとボリューム管理

```powershell
# 使用中でないイメージを削除
docker image prune -a

# 使用中でないボリュームを削除
docker volume prune

# すべてのリソース削除（注意！）
docker system prune -a
```

---

**最終更新**: 2024年12月26日
