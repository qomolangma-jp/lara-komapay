# 🚀 Docker Desktop 接続 - セットアップ手順書

## 📍 現在のステータス

✅ **Laravel 12プロジェクト完成**  
✅ **Docker環境設定完了**  
✅ **バッチスクリプト作成済み**

---

## 🎯 Docker Desktop を接続する

### ステップ 1: Docker Desktop をインストール（未インストール時）

**Windows 10/11 用**:

1. [Docker Desktop for Windows](https://www.docker.com/products/docker-desktop) をダウンロード
2. インストーラーを実行
3. 指示に従ってインストール
4. マシンを再起動

### ステップ 2: Docker Desktop を起動

```
👉 スタートメニュー → Docker Desktop → クリック
```

**起動完了の確認**:
- システムトレイに 🐳 Docker アイコンが表示される
- アイコンがアニメーションしなくなる（約30秒）

### ステップ 3: Laravelプロジェクトを起動（最も簡単な方法）

**Windows エクスプローラーで**:

```
📁 C:\Users\ko2020risu\Desktop\php-komatsu_caffe\laravel-app\
  📄 start-docker.bat  ← これを「ダブルクリック」
```

**コマンドラインで（コマンドプロンプトまたはPowerShell）**:

```powershell
# laravel-app フォルダに移動
cd c:\Users\ko2020risu\Desktop\php-komatsu_caffe\laravel-app

# Docker コンテナを起動
docker-compose up -d
```

### ステップ 4: 初期化完了を確認

```powershell
docker-compose ps
```

**期待される出力**:
```
NAME                          STATUS
cafeteria_laravel_web         Up ...
cafeteria_laravel_db          Up ...
cafeteria_laravel_phpmyadmin  Up ...
```

3つすべてが「Up」なら成功！

---

## 🌐 ブラウザでアクセス

| 用途 | URL | ログイン情報 |
|------|-----|-----------|
| **API / Web** | http://localhost:8000 | admin / admin |
| **API / Web** | http://localhost:8000 | student / 1234 |
| **phpMyAdmin** | http://localhost:8081 | root / rootpassword |

---

## 🔧 よく使うコマンド

### コンテナ操作

```powershell
# 起動
docker-compose up -d

# 停止
docker-compose down

# 再起動
docker-compose restart

# ステータス確認
docker-compose ps

# ログ確認
docker-compose logs -f web
```

### データベース操作

```powershell
# マイグレーション実行
docker-compose exec web php artisan migrate --seed

# データベースをリセット
docker-compose down -v
docker-compose up -d
docker-compose exec web php artisan migrate --seed

# Tinker（対話型PHP）起動
docker-compose exec web php artisan tinker
```

---

## 🧪 API をテストする

### 方法 1: curl コマンド（シンプル）

```powershell
# ログイン（トークン取得）
curl -X POST http://localhost:8000/api/auth/login `
  -H "Content-Type: application/json" `
  -d '{"username":"student","password":"1234"}'
```

### 方法 2: Postman（推奨）

1. [Postman をダウンロード](https://www.postman.com/downloads/)
2. インストール
3. Postman を開く
4. 新しい Request を作成
5. Method: `POST`
6. URL: `http://localhost:8000/api/auth/login`
7. Body (JSON): 
```json
{
  "username": "student",
  "password": "1234"
}
```
8. **Send** ボタンをクリック

**レスポンス例**:
```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
  }
}
```

このトークンを使用して他のAPIを呼び出せます。

---

## ❌ トラブルシューティング

### ❌ "Docker Desktop が起動していない"

**確認**:
- システムトレイに 🐳 アイコンが見えるか？
- アイコンをクリックして メニューが表示されるか？

**対処法**:
1. スタートメニューから Docker Desktop を起動
2. 30秒待つ
3. 再度コマンド実行

### ❌ "docker: not found" または "docker-compose not found"

**Windows 10 の場合 - WSL2 セットアップが必要**:

```powershell
# PowerShell（管理者）で実行
wsl --install

# マシンを再起動
```

その後、Docker Desktop を再度インストール。

### ❌ "ポート 8000 は既に使用中"

**別のアプリがポートを占有しています**。

**解決策**: `docker-compose.yml` を編集

```yaml
services:
  web:
    ports:
      - "8001:80"    # 8000 → 8001 に変更
```

変更後：
```powershell
docker-compose down
docker-compose up -d
```

その後、`http://localhost:8001` にアクセス。

### ❌ "database is not accessible"

**対処法**:

```powershell
# DBコンテナのログ確認
docker-compose logs db

# 再起動
docker-compose restart db

# 30秒待つ
```

---

## 📚 ドキュメント ナビゲーション

| ファイル | 用途 |
|---------|------|
| **QUICKSTART.md** | 5分で始める簡易ガイド |
| **DOCKER_SETUP.md** | Docker詳細設定・トラブル対応 |
| **API_SPEC.md** | 全API仕様書（詳細） |
| **LARAVEL_SETUP.md** | インストール・初期設定 |
| **MIGRATION_GUIDE.md** | 既存PHPからの移行方法 |
| **README.md** | プロジェクト概要・コマンドリファレンス |

---

## 💾 重要な設定ファイル

| ファイル | 役割 |
|---------|------|
| **.env** | 環境変数（DB接続情報等） |
| **docker-compose.yml** | Docker コンテナ構成 |
| **Dockerfile** | PHP コンテナイメージ定義 |
| **composer.json** | PHP パッケージ管理 |
| **routes/api.php** | APIルート定義 |

---

## 🎯 初回セットアップ チェックリスト

- [ ] Docker Desktop をダウンロード・インストール
- [ ] Docker Desktop を起動（アイコン確認）
- [ ] `start-docker.bat` をダブルクリック
- [ ] 3つのコンテナが起動（`docker-compose ps`）
- [ ] `http://localhost:8000` でアクセス確認
- [ ] Postman でログインテスト
- [ ] 商品一覧API呼び出し確認

---

## 🚀 次のステップ

### フロントエンド開発を開始

1. **API仕様書を読む**: [API_SPEC.md](./API_SPEC.md)
2. **フレームワークを選択**: 
   - Vue.js
   - React
   - Svelte
3. **開発開始**: API エンドポイント連携

### 本番環境へのデプロイ

1. **サーバーをレンタル**: 
   - AWS EC2
   - DigitalOcean
   - Heroku
2. **設定を本番向けに修正**:
   - `APP_ENV=production`
   - HTTPS 設定
   - DB パスワード変更
3. **デプロイ実行**: Docker イメージをプッシュ

---

## 💡 便利な Tips

### ホットリロード（ファイル変更時に自動リロード）

Laravel は開発中もコンテナ内で自動的に変更を反映します。

```powershell
# ログを監視しながら開発
docker-compose logs -f web
```

### コードを共有

```powershell
# 別のPCで同じ環境を使用したい場合
# docker-compose.yml と composer.json をコピー
# その後:
docker-compose up -d
```

### ディスク容量の確認

```powershell
# Docker が使用しているディスク量
docker system df
```

### クリーンアップ

```powershell
# 不要なイメージ・ボリュームを削除
docker system prune -a
```

---

## 📞 サポート

### よくある質問

**Q: Docker Desktop って何ですか？**  
A: 開発用マシンで Linux サーバー環境をシミュレートするツール。本番環境と同じ環境で開発できます。

**Q: Internet 接続が必要ですか？**  
A: 初回のみ必要（イメージダウンロード）。その後はオフラインでも動作します。

**Q: Windows Defender がブロックしますか？**  
A: 初回インストール時に許可ダイアログが出ます。「許可」を選択してください。

### チェックリスト：全て「はい」ならセットアップ完了

- [ ] Docker Desktop がインストールされている
- [ ] Docker Desktop が起動している（アイコン確認）
- [ ] 3つのコンテナが「Up」状態
- [ ] `http://localhost:8000` にアクセスできる
- [ ] Postman で API テストできる
- [ ] ドキュメントが読める

---

## 🎉 セットアップ完了！

これで Laravel 12 学校食堂注文システムが完全に動作する環境が完成しました！

**楽しい開発を！** 🚀

