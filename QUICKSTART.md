# 🚀 クイックスタートガイド - Windows 用

## ⏱️ 所要時間: 約5分

このガイドに従って、学校食堂注文システムをDocker上で動かしましょう！

## 📋 前提条件

- ✅ Docker Desktop がインストール済み
- ✅ Windows 10/11

## 🎯 3ステップで起動

### ステップ 1️⃣: Docker Desktop を起動（初回のみ）

1. **スタートメニュー** を開く
2. **Docker Desktop** を検索・クリック
3. アイコンが点灯するまで待機（約30秒）

### ステップ 2️⃣: バッチファイルをダブルクリック

`laravel-app` フォルダにある **`start-docker.bat`** をダブルクリック

```
📁 laravel-app/
  📄 start-docker.bat  ← これをダブルクリック！
  📄 stop-docker.bat
  📄 logs-docker.bat
```

**ウィンドウが表示され**:
```
[✓] Docker が起動中
[✓] コンテナ起動完了
[✓] データベース初期化完了
セットアップ完了！
```

### ステップ 3️⃣: ブラウザでアクセス

セットアップ完了後、以下にアクセス：

| 用途 | URL | ユーザー | パスワード |
|------|-----|---------|----------|
| **API/管理画面** | http://localhost:8000 | admin | admin |
| **学生画面** | http://localhost:8000 | student | 1234 |
| **phpMyAdmin** | http://localhost:8081 | root | rootpassword |

## 📚 API を試す

### 1️⃣ ログインして トークン取得

**Postman** または **curl** で実行：

```bash
POST http://localhost:8000/api/auth/login

{
  "username": "student",
  "password": "1234"
}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 2,
      "username": "student",
      "is_admin": false
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."  ← このトークンを保存
  }
}
```

### 2️⃣ 商品一覧を取得

```bash
GET http://localhost:8000/api/products

Headers:
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

### 3️⃣ 注文を作成

```bash
POST http://localhost:8000/api/orders

Headers:
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...

Body:
{
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    }
  ]
}
```

## 🔧 便利なコマンド

### コンテナを停止

**`stop-docker.bat`** をダブルクリック

または：

```cmd
cd laravel-app
docker-compose down
```

### ログを確認

**`logs-docker.bat`** をダブルクリック

または：

```cmd
cd laravel-app
docker-compose logs -f web
```

### データベースをリセット

```cmd
cd laravel-app
docker-compose down -v
docker-compose up -d
docker-compose exec -T web php artisan migrate --seed
```

## 🎨 Admin 画面で注文管理

1. http://localhost:8000 にアクセス
2. ユーザー名: `admin`、パスワード: `admin` でログイン
3. 注文状況の確認・ステータス更新が可能

## 📱 Student 画面で注文

1. http://localhost:8000 にアクセス
2. ユーザー名: `student`、パスワード: `1234` でログイン
3. 商品を選んで注文

## ❌ よくあるエラー

### ❌ "Docker Desktop が起動していません"

**対処法**:
1. スタートメニューから Docker Desktop を起動
2. 30秒待つ
3. `start-docker.bat` を再度実行

### ❌ "ポート 8000 は既に使用中"

**対処法**: `docker-compose.yml` を編集

```yaml
ports:
  - "8001:80"  # 8000 → 8001 に変更
```

### ❌ "database is not accessible"

**対処法**: コンテナが完全に起動するまで数秒待つ

```cmd
docker-compose logs db
```

でエラーを確認

## 📞 さらに詳しく

詳細なドキュメント：

- [DOCKER_SETUP.md](./DOCKER_SETUP.md) - Docker詳細設定
- [API_SPEC.md](./API_SPEC.md) - API仕様書
- [README.md](./README.md) - プロジェクト概要

## 💡 便利なツール

### Postman でAPI テスト

1. [Postman インストール](https://www.postman.com/downloads/)
2. Request → POST → `http://localhost:8000/api/auth/login`
3. Body → JSON → ユーザー情報入力
4. Send ボタンで実行

### コマンドラインで curl テスト

```bash
# ログイン
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"student","password":"1234"}'

# 商品一覧（トークン必要）
curl -X GET http://localhost:8000/api/products \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 🎓 次のステップ

✅ Docker で起動完了したら：

1. **API仕様書を読む** → [API_SPEC.md](./API_SPEC.md)
2. **Postman で各APIを試す**
3. **フロントエンド開発を開始**（Vue.js / React 推奨）

## 🔄 更新・再起動

### コンテナの再起動

```cmd
docker-compose restart
```

### 最新コードでビルド（コード変更後）

```cmd
docker-compose up -d --build
```

---

**何か質問があれば** `DOCKER_SETUP.md` または `README.md` を参照してください！

## 🧑‍🌾 販売者（Seller）向け操作マニュアル

販売者アカウントでログインすると、出品および自分の商品管理を行うための専用画面が利用できます。主な操作手順は以下の通りです。

1. ログイン
  - 管理画面（http://localhost:8000）にアクセスして販売者アカウントでログインします。

2. 販売者ダッシュボードに移動
  - 「商品管理」→「登録・編集画面」を選択すると、商品登録フォームが表示されます。

3. 商品登録/編集
  - 必須項目: 商品名、価格、メイン画像（新規登録時）
  - 任意項目: 在庫、カテゴリ、ラベル（短い自由テキスト）、説明、追加画像、アレルギー情報（タグ形式）
  - 画像はドラッグ＆ドロップでアップロードできます。アップロード進捗が表示されます。
  - アレルギーはタグ入力（Enter／カンマで追加）で管理します。保存時にはカンマ区切りの文字列としてサーバに送信されます。

4. 即時バリデーション
  - 商品名や価格、ラベル長などの簡易バリデーションは入力時に即時表示されます。エラーがある場合は画面上にメッセージが表示されます。

5. API を使った登録（開発用）
  - Postman / curl を使って直接 API を叩く場合、ログインして取得したトークンを `Authorization: Bearer {token}` ヘッダーに設定してください。

```bash
# 販売者として商品を作成（サーバ側は販売者が自分の商品を作成可能に実装されています）
POST http://localhost:8000/api/products
Headers: Authorization: Bearer {token}

Body (JSON):
{
  "name": "販売者の新商品",
  "price": 800,
  "stock": 10,
  "category": "サラダ",
  "label": "おすすめ",
  "description": "販売者側の説明",
  "allergens": "小麦, 卵"
}
```

問題が発生した場合は、フロントのコンソールログや `docker-compose logs -f web` を確認してください。
