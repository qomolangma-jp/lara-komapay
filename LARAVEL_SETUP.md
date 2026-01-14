# Laravel 12 学校食堂注文システム - セットアップガイド

## プロジェクト概要

このプロジェクトは、元のPHPベースの学校食堂注文システムをLaravel 12にマイグレーションしたものです。

### 主な特徴

- **Laravel 12フレームワーク**: 最新のPHPフレームワーク
- **Eloquent ORM**: データベース操作の簡略化
- **REST API**: JSON形式のAPI提供
- **Laravel Sanctum**: トークンベースの認証
- **Docker環境**: 一貫した開発環境

## ディレクトリ構造

```
laravel-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/    # APIコントローラー
│   │   └── Middleware/          # カスタムミドルウェア
│   └── Models/                  # Eloquentモデル
├── database/
│   ├── migrations/              # データベーススキーマ
│   └── seeders/                 # 初期データ
├── routes/
│   └── api.php                  # APIルート定義
├── docker-compose.yml           # Docker設定
├── Dockerfile                   # PHPコンテナ定義
└── composer.json                # PHP依存関係
```

## セットアップ手順

### 1. 依存関係のインストール

```bash
# Windows PowerShellの場合
cd laravel-app
composer install
```

### 2. 環境設定

```bash
# .env.exampleをコピーして.envを作成
cp .env.example .env

# APPキーを生成（Linuxの場合）
php artisan key:generate

# または手動で .env に設定
APP_KEY=base64:abcdef...
```

### 3. Docker環境の起動

```bash
docker-compose up -d
```

### 4. データベース初期化

コンテナが完全に起動してから実行：

```bash
# マイグレーション実行
docker-compose exec web php artisan migrate

# 初期データを投入
docker-compose exec web php artisan db:seed
```

### 5. アクセス確認

- **APIエンドポイント**: http://localhost:8000/api
- **phpMyAdmin**: http://localhost:8081

## Docker環境の管理

### 起動

```bash
docker-compose up -d
```

### 停止

```bash
docker-compose down
```

### 再起動

```bash
docker-compose restart
```

### ログ確認

```bash
docker-compose logs -f web
```

### データベースのリセット

```bash
docker-compose down -v
docker-compose up -d
docker-compose exec web php artisan migrate --seed
```

## APIエンドポイント

### 認証

#### ログイン
```
POST /api/auth/login
Content-Type: application/json

{
  "username": "admin",
  "password": "admin"
}
```

#### 登録
```
POST /api/auth/register
Content-Type: application/json

{
  "username": "newuser",
  "password": "password123"
}
```

#### ログアウト
```
POST /api/auth/logout
Authorization: Bearer {token}
```

### 商品管理

#### 全商品取得
```
GET /api/products
Authorization: Bearer {token}

// クエリパラメータ
?category=定食      // カテゴリでフィルタリング
?available=true     // 在庫がある商品のみ
```

#### 商品詳細
```
GET /api/products/{id}
Authorization: Bearer {token}
```

#### 商品作成（管理者）
```
POST /api/products
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "新商品",
  "price": 500,
  "stock": 20,
  "category": "定食",
  "description": "説明",
  "image_url": "URL"
}
```

#### 商品更新（管理者）
```
PUT /api/products/{id}
Authorization: Bearer {token}
Content-Type: application/json
```

#### 商品削除（管理者）
```
DELETE /api/products/{id}
Authorization: Bearer {token}
```

### 注文管理

#### 注文作成
```
POST /api/orders
Authorization: Bearer {token}
Content-Type: application/json

{
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    },
    {
      "product_id": 3,
      "quantity": 1
    }
  ]
}
```

#### 自分の注文一覧
```
GET /api/orders/my/list
Authorization: Bearer {token}
```

#### 注文詳細
```
GET /api/orders/{id}
Authorization: Bearer {token}
```

#### 全注文を取得（管理者）
```
GET /api/orders
Authorization: Bearer {token}

// クエリパラメータ
?status=調理中       // ステータスでフィルタリング
?date=2024-01-01    // 日付でフィルタリング
?sort_by=created_at // ソートキー
?sort_dir=desc      // asc または desc
```

#### ステータス更新（管理者）
```
PUT /api/orders/{id}/status
Authorization: Bearer {token}
Content-Type: application/json

{
  "status": "完了"
}
```

#### 今日の統計情報（管理者）
```
GET /api/stats/today
Authorization: Bearer {token}
```

## デフォルトアカウント

| 役割 | ユーザー名 | パスワード |
|------|-----------|----------|
| 管理者 | admin | admin |
| 学生 | student | 1234 |

## 既存PHPプロジェクトからの変更点

### 利点

1. **セキュリティ**: CSRF保護、SQL Injection対策が自動で適用
2. **パスワード暗号化**: BCryptによる安全なハッシュ化
3. **データベース抽象化**: SQLを直接書かずにEloquentで操作可能
4. **トランザクション管理**: 自動ロールバック機能
5. **バージョン管理**: マイグレーションで履歴を管理
6. **テストフレームワーク**: PHPUnitが統合

### マイグレーションガイド

既存PHPコードをLaravelに移行する場合：

1. **ビジネスロジック**: コントローラーに記述
2. **データアクセス**: Modelクラスで実装
3. **バリデーション**: `Validation` で集中管理
4. **エラーハンドリング**: `Exception` で一元化

## トラブルシューティング

### Composerコマンドが見つからない場合

PHPがパスに登録されていない場合は、PHPのフルパスを使用：

```bash
C:\path\to\php\php.exe -v
```

### データベース接続エラー

```bash
# コンテナが完全に起動するまで待機（30秒程度）
# その後、以下を実行
docker-compose exec web php artisan migrate
```

### ポートの競合

別のアプリケーションがポート8000を使用している場合、`docker-compose.yml` を編集：

```yaml
ports:
  - "8001:80"  # ホストポートを8001に変更
```

## ローカル開発時の便利なコマンド

```bash
# キャッシュのクリア
docker-compose exec web php artisan cache:clear
docker-compose exec web php artisan config:clear

# ルートのキャッシュ生成
docker-compose exec web php artisan route:cache

# データベースのリセット（開発用）
docker-compose exec web php artisan migrate:refresh --seed

# Tinker（PHPコンソール）起動
docker-compose exec web php artisan tinker
```

## 本番環境への展開

本番環境での推奨設定：

```env
APP_ENV=production
APP_DEBUG=false
LOG_CHANNEL=stack
DB_PASSWORD=strong_password_here
```

詳細は[PRODUCTION_SWITCH_GUIDE.md](../PRODUCTION_SWITCH_GUIDE.md)を参照。

## サポート

問題が発生した場合は、以下を確認：

1. Dockerが正常に起動しているか
2. MySQLが完全に起動したか（ログで確認）
3. .envファイルが正しく設定されているか
4. コンテナのログを確認

```bash
docker-compose logs web
docker-compose logs db
```
