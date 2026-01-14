# 学校食堂注文システム - Laravel 12版

![Laravel](https://img.shields.io/badge/Laravel-12.x-orange)
![PHP](https://img.shields.io/badge/PHP-8.2-blue)
![MySQL](https://img.shields.io/badge/MySQL-8.0-darkblue)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED)

学校食堂の注文・管理システムをLaravel 12を使用してフルスタック開発したプロジェクトです。

## 📖 概要

このプロジェクトは、従来のPHPで開発された学校食堂注文システムを、最新のLaravel 12フレームワークにマイグレーションしたものです。RESTful APIとして設計され、フロントエンドとバックエンドが完全に分離されています。

### 主な特徴

- ✅ **モダンPHP**：PHP 8.2の最新機能を活用
- ✅ **セキュアな認証**：Laravel SanctumによるトークンベースAPI認証
- ✅ **Eloquent ORM**：SQLを書かずにデータベース操作
- ✅ **REST API**：JSON形式で完全なAPI提供
- ✅ **Docker対応**：一貫した開発環境
- ✅ **強力なセキュリティ**：CSRF、SQL Injection自動防御

## 🎯 システム要件

### ローカル開発環境

- **PHP**: 8.2以上
- **Composer**: 最新版
- **Docker**: 20.10以上
- **Docker Compose**: 2.0以上

### 本番環境

- **サーバーOS**: Linux (Ubuntu 22.04推奨)
- **Webサーバー**: Nginx / Apache
- **PHP**: 8.2以上
- **MySQL**: 8.0以上
- **Redis**: オプション（キャッシュ用）

## 🚀 クイックスタート

### 1. リポジトリのクローン

```bash
cd laravel-app
```

### 2. 環境セットアップ

```bash
# Composerで依存パッケージをインストール
composer install

# .envファイルを設定
cp .env.example .env

# アプリケーションキーを生成
php artisan key:generate
```

### 3. Docker環境の起動

```bash
# コンテナをバックグラウンドで起動
docker-compose up -d

# ホスト名がない場合は以下を実行
docker-compose ps  # コンテナ確認
```

### 4. データベースセットアップ

```bash
# マイグレーション実行
docker-compose exec web php artisan migrate

# 初期データを投入
docker-compose exec web php artisan db:seed
```

### 5. ブラウザでアクセス

```
API: http://localhost:8000/api
phpMyAdmin: http://localhost:8081
```

## 📚 ドキュメント

詳細なドキュメントは以下を参照してください：

- [セットアップガイド](./LARAVEL_SETUP.md) - インストール・設定方法
- [API仕様書](./API_SPEC.md) - REST APIの完全な仕様
- [移行ガイド](./MIGRATION_GUIDE.md) - 既存システムからの移行方法

## 🔧 主要コマンド

### プロジェクト管理

```bash
# マイグレーション実行
docker-compose exec web php artisan migrate

# マイグレーション削除（ロールバック）
docker-compose exec web php artisan migrate:rollback

# データベースリセット
docker-compose exec web php artisan migrate:refresh --seed

# 初期データ再投入
docker-compose exec web php artisan db:seed
```

### コード生成

```bash
# コントローラー生成
docker-compose exec web php artisan make:controller Api/ProductController --api

# モデル + マイグレーション生成
docker-compose exec web php artisan make:model Product -m

# ミドルウェア生成
docker-compose exec web php artisan make:middleware AdminMiddleware
```

### デバッグ

```bash
# Tinkerコンソール起動（対話型PHP）
docker-compose exec web php artisan tinker

# ルート一覧表示
docker-compose exec web php artisan route:list

# キャッシュクリア
docker-compose exec web php artisan cache:clear
docker-compose exec web php artisan config:clear
```

## 📋 API エンドポイント一覧

### 認証
- `POST /api/auth/login` - ログイン
- `POST /api/auth/register` - ユーザー登録
- `GET /api/auth/me` - 現在のユーザー情報
- `POST /api/auth/logout` - ログアウト

### 商品管理
- `GET /api/products` - 全商品取得
- `GET /api/products/:id` - 商品詳細
- `GET /api/products/categories/list` - カテゴリ一覧
- `POST /api/products` - 商品作成（管理者）
- `PUT /api/products/:id` - 商品更新（管理者）
- `DELETE /api/products/:id` - 商品削除（管理者）

### 注文管理
- `POST /api/orders` - 注文作成
- `GET /api/orders/my/list` - 自分の注文一覧
- `GET /api/orders/:id` - 注文詳細
- `GET /api/orders` - 全注文（管理者）
- `PUT /api/orders/:id/status` - ステータス更新（管理者）
- `GET /api/stats/today` - 本日の統計（管理者）

詳細は [API_SPEC.md](./API_SPEC.md) を参照してください。

## 👥 デフォルトアカウント

| 役割 | ユーザー名 | パスワード |
|------|-----------|----------|
| 管理者 | admin | admin |
| 学生 | student | 1234 |

**⚠️ 本番環境では必ず変更してください。**

## 📁 プロジェクト構造

```
laravel-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/               # APIコントローラー
│   │   └── Middleware/            # カスタムミドルウェア
│   ├── Models/                    # Eloquentモデル
│   └── Policies/                  # 認可ポリシー
├── database/
│   ├── migrations/                # スキーママイグレーション
│   └── seeders/                   # 初期データ
├── routes/
│   └── api.php                    # APIルート定義
├── resources/
│   └── views/                     # Blade テンプレート
├── public/                        # 公開フォルダ
├── config/                        # 設定ファイル
├── bootstrap/
│   └── cache/                     # ブートストラップキャッシュ
├── storage/
│   ├── logs/                      # ログファイル
│   └── app/                       # アップロードファイル
├── tests/                         # ユニットテスト
├── docker-compose.yml             # Docker Compose設定
├── Dockerfile                     # PHPコンテナ定義
├── composer.json                  # PHP依存管理
└── .env                           # 環境変数
```

## 🔐 セキュリティ機能

### 自動保護機能

1. **CSRF保護** - トークンベースの自動検証
2. **SQL Injection防止** - Eloquent + バインディング
3. **XSS対策** - `{{ }}` による自動エスケープ
4. **パスワード暗号化** - BCrypt推奨
5. **認可（Authorization）** - ポリシークラス

### ベストプラクティス

- センシティブ情報は `.env` で管理
- パスワードは必ずハッシュ化
- APIレート制限を設定
- HTTPSを使用（本番環境）

## 🧪 テスト

```bash
# ユニットテスト実行
docker-compose exec web php artisan test

# 特定のテストのみ実行
docker-compose exec web php artisan test tests/Feature/OrderTest.php

# カバレッジレポート生成
docker-compose exec web php artisan test --coverage
```

## 📊 パフォーマンス最適化

```bash
# ルートキャッシュ生成
docker-compose exec web php artisan route:cache

# 設定キャッシュ生成
docker-compose exec web php artisan config:cache

# モデルのクエリ最適化
# database/factories に Factory を定義してN+1問題を回避
```

## 🐛 トラブルシューティング

### データベース接続エラー

```bash
# DBコンテナのログ確認
docker-compose logs db

# 再起動
docker-compose restart db
```

### ポート競合エラー

`docker-compose.yml` でポート番号を変更：

```yaml
ports:
  - "8001:80"  # 8000 → 8001に変更
```

### マイグレーションエラー

```bash
# マイグレーション状態確認
docker-compose exec web php artisan migrate:status

# 最後のマイグレーションをロールバック
docker-compose exec web php artisan migrate:rollback
```

## 📖 参考資料

- [Laravel 12 公式ドキュメント](https://laravel.com/docs/12.x)
- [Eloquent ORM ガイド](https://laravel.com/docs/12.x/eloquent)
- [Laravel API リソース](https://laravel.com/docs/12.x/eloquent-resources)
- [Docker 公式ドキュメント](https://docs.docker.com/)

## 🎓 学習リソース

### Laravel学習パス

1. **基礎概念**
   - Laravelのライフサイクル
   - Service Container（依存性注入）
   - Service Provider

2. **Web開発**
   - ルーティング
   - Middleware（ミドルウェア）
   - Controller（コントローラー）

3. **DB操作**
   - Eloquent ORM
   - Query Builder
   - Relationships（リレーション）

4. **API開発**
   - RESTful設計
   - Authentication（認証）
   - Authorization（認可）

5. **高度なトピック**
   - テスト駆動開発（TDD）
   - デザインパターン
   - パフォーマンス最適化

## 🤝 貢献ガイドライン

チームメンバーが開発に参加する場合：

1. **ブランチ戦略**: `git flow` を採用
   - `develop` - 開発ブランチ
   - `feature/xxx` - 機能開発ブランチ

2. **コーディング規約**: Laravel Pint で自動整形

3. **コミットメッセージ**: 英語で簡潔に記述

4. **プルリクエスト**: コードレビュー後にマージ

## 📞 サポート・質問

問題や質問がある場合：

1. [Issues](https://github.com/your-repo/issues) で既存の関連Issue確認
2. ドキュメントを再読
3. Docker ログで詳細エラー確認
4. 新しい Issue を作成

## 📄 ライセンス

MIT License - 詳細は LICENSE ファイルを参照

## 🎉 謝辞

このプロジェクトはLaravelコミュニティと多くのOSSプロジェクトに支えられています。

---

**最終更新**: 2024年12月26日  
**バージョン**: 1.0.0-beta
