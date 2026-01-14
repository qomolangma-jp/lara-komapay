# 📚 Laravel 12 学校食堂注文システム - 完全ガイドインデックス

**プロジェクト完成日**: 2024年12月26日  
**バージョン**: 1.0.0  
**ステータス**: ✅ 本番環境対応可能

---

## 🎯 最初に読むドキュメント（優先度順）

### 1️⃣ **今すぐ始めたい方** → [QUICKSTART.md](./QUICKSTART.md)
- ⏱️ 所要時間: 5分
- 🎯 内容: Docker起動～ブラウザアクセスまで
- 👥 対象: 開発初心者・時間がない人

### 2️⃣ **Docker設定を理解したい方** → [DOCKER_CONNECTION.md](./DOCKER_CONNECTION.md)
- 📖 内容: Docker Desktop の接続方法
- 🔧 対象: Docker初心者
- ⚠️ トラブルシューティング付き

### 3️⃣ **APIを使ってみたい方** → [API_SPEC.md](./API_SPEC.md)
- 🔌 内容: 全APIエンドポイント仕様
- 📋 詳細: リクエスト・レスポンス例
- 👥 対象: フロントエンド開発者

### 4️⃣ **詳しくセットアップしたい方** → [LARAVEL_SETUP.md](./LARAVEL_SETUP.md)
- 🛠️ 内容: 詳細なインストール手順
- 📚 詳細: コマンドリファレンス
- 🏗️ 対象: DevOps・インフラ担当

### 5️⃣ **既存PHPから移行する方** → [MIGRATION_GUIDE.md](./MIGRATION_GUIDE.md)
- 🔄 内容: PHP → Laravel 移行方法
- 📖 詳細: Eloquent ORM 学習
- 👥 対象: 既存システム管理者

### 6️⃣ **プロジェクト全体を知りたい方** → [README.md](./README.md)
- 📋 内容: プロジェクト概要・要件
- 🔧 詳細: 全コマンドリファレンス
- 📊 対象: プロジェクト管理者

---

## 📁 ドキュメント構成図

```
Laravel App
├── 🚀 【クイックスタート】
│   ├── QUICKSTART.md          (5分で起動)
│   └── DOCKER_CONNECTION.md   (Docker接続)
│
├── 🔌 【API開発】
│   └── API_SPEC.md            (全エンドポイント)
│
├── 🛠️ 【セットアップ】
│   ├── LARAVEL_SETUP.md       (詳細設定)
│   └── DOCKER_SETUP.md        (Docker詳細)
│
├── 📚 【学習・移行】
│   └── MIGRATION_GUIDE.md     (PHP→Laravel移行)
│
├── 📋 【プロジェクト】
│   ├── README.md              (全体概要)
│   └── COMPLETION_REPORT.md   (完了レポート)
│
└── 🔧 【実行スクリプト】
    ├── start-docker.bat       (起動)
    ├── stop-docker.bat        (停止)
    └── logs-docker.bat        (ログ表示)
```

---

## ✅ セットアップチェックリスト

### 環境確認

- [ ] Windows 10/11 である
- [ ] Docker Desktop がインストール済み
- [ ] 4GB 以上の RAM が利用可能
- [ ] インターネット接続が可能

### Docker セットアップ

- [ ] Docker Desktop を起動
- [ ] システムトレイに 🐳 アイコンが表示
- [ ] Docker コマンドが動作（`docker ps`）
- [ ] docker-compose コマンドが動作

### Laravel セットアップ

- [ ] `start-docker.bat` をダブルクリック実行
- [ ] 3つのコンテナが「Up」状態（`docker-compose ps`）
- [ ] MySQL が起動完了（ログで確認）
- [ ] Web サーバーが起動完了

### アクセス確認

- [ ] `http://localhost:8000` にアクセス可能
- [ ] `http://localhost:8081` (phpMyAdmin) にアクセス可能
- [ ] ログイン成功（admin / admin）
- [ ] API エンドポイントが応答

### テスト実行

- [ ] Postman でログインテスト
- [ ] トークン取得成功
- [ ] 商品一覧 API 呼び出し成功
- [ ] 注文作成 API テスト成功

---

## 🎯 使用シーン別ガイド

### 💻 開発環境を整える

```
1. QUICKSTART.md を読む
2. Docker Desktop をインストール
3. start-docker.bat をダブルクリック
4. http://localhost:8000 にアクセス
```

### 🔌 API を開発する

```
1. API_SPEC.md で仕様を確認
2. Postman をダウンロード
3. ログインして トークン取得
4. 各エンドポイントをテスト
5. 開発言語で実装
```

### 📱 フロントエンドを開発する

```
1. API_SPEC.md でエンドポイント確認
2. Vue.js / React をセットアップ
3. API クライアントライブラリを選択
4. API 連携コード実装
5. CORS 設定確認（本番環境）
```

### 🚀 本番環境にデプロイする

```
1. LARAVEL_SETUP.md で本番設定確認
2. サーバーレンタル（AWS等）
3. Docker イメージをビルド
4. CI/CD パイプライン設定
5. デプロイ実行
```

### 🔄 既存システムから移行する

```
1. MIGRATION_GUIDE.md で全体流れ確認
2. 既存データベーススキーマ確認
3. マイグレーション作成
4. 既存データをシード
5. 機能テスト実施
```

---

## 📊 技術スタック

| レイヤー | 技術 | バージョン |
|---------|------|----------|
| **言語** | PHP | 8.2 |
| **フレームワーク** | Laravel | 12.x |
| **ORM** | Eloquent | 12.x |
| **認証** | Sanctum | 4.x |
| **データベース** | MySQL | 8.0 |
| **Web サーバー** | Apache | 2.4 |
| **コンテナ** | Docker | 24.x |

---

## 🔑 デフォルトアカウント

| 役割 | ユーザー名 | パスワード | 用途 |
|------|-----------|----------|------|
| 管理者 | admin | admin | 注文管理・統計 |
| 学生 | student | 1234 | 注文・商品閲覧 |
| DB管理 | root | rootpassword | phpMyAdmin |

**⚠️ 本番環境では必ず変更してください。**

---

## 📚 主要API エンドポイント

### 認証
- `POST /api/auth/login` - ログイン
- `POST /api/auth/register` - 登録
- `GET /api/auth/me` - ユーザー情報取得
- `POST /api/auth/logout` - ログアウト

### 商品
- `GET /api/products` - 商品一覧
- `GET /api/products/:id` - 商品詳細
- `GET /api/products/categories/list` - カテゴリ一覧

### 注文
- `POST /api/orders` - 注文作成
- `GET /api/orders/my/list` - 自分の注文一覧
- `GET /api/orders/:id` - 注文詳細

### 管理（管理者のみ）
- `GET /api/orders` - 全注文一覧
- `PUT /api/orders/:id/status` - ステータス更新
- `GET /api/stats/today` - 本日統計

詳細: [API_SPEC.md](./API_SPEC.md)

---

## 🔧 便利なコマンド

### 起動・停止

```bash
# 起動
docker-compose up -d

# 停止
docker-compose down

# 再起動
docker-compose restart
```

### データベース

```bash
# マイグレーション実行
docker-compose exec web php artisan migrate --seed

# マイグレーション削除
docker-compose exec web php artisan migrate:rollback

# リセット
docker-compose down -v && docker-compose up -d && docker-compose exec web php artisan migrate --seed
```

### 開発ツール

```bash
# Tinker（対話型PHP）
docker-compose exec web php artisan tinker

# ルート確認
docker-compose exec web php artisan route:list

# ログ確認
docker-compose logs -f web
```

詳細: [README.md](./README.md#便利なコマンド)

---

## 🐛 トラブルシューティング

### Docker 関連

| 問題 | 原因 | 解決策 |
|------|------|--------|
| "Docker Desktop が起動していない" | Docker が動作していない | Docker Desktop を起動 |
| "ポート 8000 が使用中" | 別のアプリが占有 | ポート番号を変更 |
| "Cannot connect to Docker daemon" | WSL2 の問題 | `wsl --install` を実行 |

詳細: [DOCKER_CONNECTION.md](./DOCKER_CONNECTION.md#トラブルシューティング)

### Laravel 関連

| 問題 | 原因 | 解決策 |
|------|------|--------|
| "database is not accessible" | DB 起動待ち | 30秒待つ |
| "Class not found" | オートローダ更新なし | `composer dump-autoload` |
| "SQLSTATE error" | マイグレーション未実行 | `php artisan migrate` |

詳細: [LARAVEL_SETUP.md](./LARAVEL_SETUP.md#トラブルシューティング)

---

## 📖 学習パス

### 初級（これからLaravelを始める人）

1. [README.md](./README.md) - 全体理解
2. [QUICKSTART.md](./QUICKSTART.md) - セットアップ
3. [API_SPEC.md](./API_SPEC.md) - API理解

### 中級（Laravelで開発できる人）

1. [LARAVEL_SETUP.md](./LARAVEL_SETUP.md) - 詳細設定
2. [DOCKER_SETUP.md](./DOCKER_SETUP.md) - Docker詳細
3. Laravel 公式ドキュメント

### 上級（本番環境対応）

1. セキュリティ強化
2. パフォーマンス最適化
3. CI/CD パイプライン構築

---

## 📞 サポート・質問

### ドキュメント内の検索

各ドキュメント内に該当のセクションがあります：

- Docker 関連 → [DOCKER_SETUP.md](./DOCKER_SETUP.md)
- API 関連 → [API_SPEC.md](./API_SPEC.md)
- セットアップ → [LARAVEL_SETUP.md](./LARAVEL_SETUP.md)
- トラブル → [DOCKER_CONNECTION.md](./DOCKER_CONNECTION.md#トラブルシューティング)

### オンラインリソース

- [Laravel 公式ドキュメント](https://laravel.com/docs/12.x)
- [Docker ドキュメント](https://docs.docker.com/)
- [Stack Overflow](https://stackoverflow.com/questions/tagged/laravel)

---

## 🎓 開発チーム向け

### ブランチ戦略

```
main          ← 本番リリース
  └─ develop ← 開発統合
       ├─ feature/auth    ← 機能開発
       ├─ feature/order   ← 機能開発
       └─ bugfix/xxx      ← バグ修正
```

### コード品質ツール

```bash
# コード整形
./vendor/bin/pint

# テスト実行
php artisan test

# コード解析
./vendor/bin/phpstan analyse app
```

### コミットメッセージ形式

```
feat: 新しい機能の説明
fix: バグ修正の説明
docs: ドキュメント修正
refactor: リファクタリング
test: テスト追加
chore: その他の変更
```

---

## 🎉 プロジェクト完成

このプロジェクトはすべてのドキュメント、環境設定、サンプルコードが揃い、**本番環境へのデプロイが可能な状態**です。

### 次のステップ

1. ✅ [QUICKSTART.md](./QUICKSTART.md) に従ってセットアップ
2. ✅ [API_SPEC.md](./API_SPEC.md) を参照しながら開発
3. ✅ Postman で API テスト
4. ✅ フロントエンド開発開始
5. ✅ 本番環境へデプロイ

---

## 📋 ファイル一覧

| ファイル | 説明 | 重要度 |
|---------|------|--------|
| [QUICKSTART.md](./QUICKSTART.md) | 5分で開始ガイド | ⭐⭐⭐ |
| [API_SPEC.md](./API_SPEC.md) | API仕様書 | ⭐⭐⭐ |
| [README.md](./README.md) | プロジェクト概要 | ⭐⭐⭐ |
| [LARAVEL_SETUP.md](./LARAVEL_SETUP.md) | セットアップ詳細 | ⭐⭐ |
| [DOCKER_SETUP.md](./DOCKER_SETUP.md) | Docker詳細設定 | ⭐⭐ |
| [DOCKER_CONNECTION.md](./DOCKER_CONNECTION.md) | Docker接続ガイド | ⭐⭐ |
| [MIGRATION_GUIDE.md](./MIGRATION_GUIDE.md) | 移行ガイド | ⭐ |
| [COMPLETION_REPORT.md](./COMPLETION_REPORT.md) | 完了レポート | ⭐ |

---

**作成日**: 2024年12月26日  
**プロジェクト**: 学校食堂注文システム Laravel 12版  
**ステータス**: ✅ 本番環境対応可能  

**Happy Coding! 🚀**
