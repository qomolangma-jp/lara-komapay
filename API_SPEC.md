# API仕様書 - 学校食堂注文システム Laravel版

## 概要

RESTful APIとして設計された学校食堂注文システムのAPI仕様です。すべてのエンドポイントはJSON形式でリクエスト・レスポンスを行います。

## 基本情報

- **ベースURL**: `http://localhost:8000/api`
- **認証**: Laravel Sanctum（トークン認証）
- **レスポンス形式**: JSON
- **文字コード**: UTF-8

## レスポンス形式

### 成功時

```json
{
  "success": true,
  "message": "メッセージ（オプション）",
  "data": {
    // レスポンスデータ
  }
}
```

### エラー時

```json
{
  "success": false,
  "message": "エラーメッセージ"
}
```

## HTTPステータスコード

| コード | 意味 |
|--------|------|
| 200 | 成功 |
| 201 | リソース作成成功 |
| 400 | リクエストエラー |
| 401 | 未認証 |
| 403 | 権限なし |
| 404 | リソース見つからず |
| 422 | バリデーションエラー |
| 500 | サーバーエラー |

## 認証エンドポイント

### POST /auth/login

ユーザーをログインさせ、認証トークンを取得します。

**リクエスト**:
```json
{
  "username": "admin",
  "password": "admin"
}
```

**レスポンス** (200):
```json
{
  "success": true,
  "message": "ログインしました",
  "data": {
    "user": {
      "id": 1,
      "username": "admin",
      "is_admin": true,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
  }
}
```

**エラーレスポンス** (401):
```json
{
  "success": false,
  "message": "ユーザー名またはパスワードが正しくありません"
}
```

### POST /auth/register

新規ユーザーアカウントを作成します。

**リクエスト**:
```json
{
  "username": "newuser",
  "password": "password123"
}
```

**レスポンス** (201):
```json
{
  "success": true,
  "message": "アカウントを作成しました",
  "data": {
    "user": {
      "id": 2,
      "username": "newuser",
      "is_admin": false
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
  }
}
```

**バリデーションエラー** (422):
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "username": ["The username has already been taken."]
  }
}
```

### GET /auth/me

現在ログインしているユーザーの情報を取得します。

**リクエストヘッダー**:
```
Authorization: Bearer {token}
```

**レスポンス** (200):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "username": "admin",
    "is_admin": true,
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-01T00:00:00Z"
  }
}
```

### POST /auth/logout

ユーザーをログアウトします。

**リクエストヘッダー**:
```
Authorization: Bearer {token}
```

**レスポンス** (200):
```json
{
  "success": true,
  "message": "ログアウトしました"
}
```

## 商品エンドポイント

### GET /products

全商品を取得します。

**リクエストヘッダー**:
```
Authorization: Bearer {token}
```

**クエリパラメータ**:
| パラメータ | 型 | 説明 |
|-----------|-----|------|
| category | string | カテゴリでフィルタリング |
| available | boolean | 在庫がある商品のみ（"true"） |

**レスポンス** (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "日替わり定食（ハンバーグ）",
      "price": 500,
      "stock": 20,
      "category": "定食",
      "description": "国産牛を使用したジューシーなハンバーグ。サラダ・スープ付き。",
      "image_url": "https://...",
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z"
    }
  ],
  "count": 1
}
```

### GET /products/:id

特定の商品詳細を取得します。

**リクエスト**:
```
GET /products/1
Authorization: Bearer {token}
```

**レスポンス** (200):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "日替わり定食（ハンバーグ）",
    "price": 500,
    "stock": 20,
    "category": "定食",
    "description": "国産牛を使用したジューシーなハンバーグ。サラダ・スープ付き。",
    "image_url": "https://...",
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-01T00:00:00Z"
  }
}
```

### GET /products/categories/list

カテゴリ一覧を取得します。

**リクエスト**:
```
GET /products/categories/list
Authorization: Bearer {token}
```

**レスポンス** (200):
```json
{
  "success": true,
  "data": ["定食", "カレー", "麺類", "サイド"]
}
```

### POST /products

新しい商品を作成します（**管理者のみ**）。

**リクエスト**:
```
POST /products
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "name": "新商品",
  "price": 600,
  "stock": 25,
  "category": "定食",
  "description": "説明テキスト",
  "image_url": "https://..."
}
```

**レスポンス** (201):
```json
{
  "success": true,
  "message": "商品を作成しました",
  "data": {
    "id": 6,
    "name": "新商品",
    "price": 600,
    "stock": 25,
    "category": "定食",
    "description": "説明テキスト",
    "image_url": "https://...",
    "created_at": "2024-01-02T00:00:00Z",
    "updated_at": "2024-01-02T00:00:00Z"
  }
}
```

### PUT /products/:id

商品を更新します（**管理者のみ**）。

**リクエスト**:
```
PUT /products/1
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "price": 550,
  "stock": 30
}
```

**レスポンス** (200):
```json
{
  "success": true,
  "message": "商品を更新しました",
  "data": {
    "id": 1,
    "name": "日替わり定食（ハンバーグ）",
    "price": 550,
    "stock": 30,
    "category": "定食",
    "description": "国産牛を使用したジューシーなハンバーグ。サラダ・スープ付き。",
    "image_url": "https://...",
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-02T00:00:00Z"
  }
}
```

### DELETE /products/:id

商品を削除します（**管理者のみ**）。

**リクエスト**:
```
DELETE /products/1
Authorization: Bearer {admin_token}
```

**レスポンス** (200):
```json
{
  "success": true,
  "message": "商品を削除しました"
}
```

## 注文エンドポイント

### POST /orders

新しい注文を作成します。

**リクエスト**:
```
POST /orders
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

**レスポンス** (201):
```json
{
  "success": true,
  "message": "注文を作成しました",
  "data": {
    "id": 1,
    "user_id": 2,
    "total_price": 1400,
    "status": "調理中",
    "created_at": "2024-01-02T10:30:00Z",
    "updated_at": "2024-01-02T10:30:00Z",
    "details": [
      {
        "id": 1,
        "order_id": 1,
        "product_id": 1,
        "quantity": 2,
        "product": {
          "id": 1,
          "name": "日替わり定食（ハンバーグ）",
          "price": 500,
          "stock": 18
        }
      }
    ]
  }
}
```

### GET /orders/my/list

自分の注文一覧を取得します。

**リクエスト**:
```
GET /orders/my/list?page=1
Authorization: Bearer {token}
```

**レスポンス** (200):
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "user_id": 2,
        "total_price": 1400,
        "status": "調理中",
        "created_at": "2024-01-02T10:30:00Z",
        "updated_at": "2024-01-02T10:30:00Z"
      }
    ],
    "links": {
      "first": "http://localhost:8000/api/orders/my/list?page=1",
      "last": "http://localhost:8000/api/orders/my/list?page=1",
      "prev": null,
      "next": null
    },
    "meta": {
      "current_page": 1,
      "from": 1,
      "per_page": 10,
      "to": 1,
      "total": 1
    }
  }
}
```

### GET /orders/:id

特定の注文詳細を取得します。

**リクエスト**:
```
GET /orders/1
Authorization: Bearer {token}
```

**レスポンス** (200):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 2,
    "total_price": 1400,
    "status": "調理中",
    "created_at": "2024-01-02T10:30:00Z",
    "updated_at": "2024-01-02T10:30:00Z",
    "user": {
      "id": 2,
      "username": "student",
      "is_admin": false
    },
    "details": [
      {
        "id": 1,
        "order_id": 1,
        "product_id": 1,
        "quantity": 2,
        "product": {
          "id": 1,
          "name": "日替わり定食（ハンバーグ）",
          "price": 500,
          "stock": 18
        }
      }
    ]
  }
}
```

### GET /orders

全注文を取得します（**管理者のみ**）。

**リクエスト**:
```
GET /orders?status=調理中&date=2024-01-02&sort_by=created_at&sort_dir=desc&page=1
Authorization: Bearer {admin_token}
```

**クエリパラメータ**:
| パラメータ | 型 | 説明 |
|-----------|-----|------|
| status | string | ステータスでフィルタリング（"調理中" or "完了"） |
| date | string | 日付でフィルタリング（YYYY-MM-DD） |
| sort_by | string | ソートキー（created_at, user_id など） |
| sort_dir | string | ソート順序（asc or desc） |
| page | integer | ページ番号 |

**レスポンス** (200):
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "user_id": 2,
        "total_price": 1400,
        "status": "調理中",
        "created_at": "2024-01-02T10:30:00Z",
        "updated_at": "2024-01-02T10:30:00Z",
        "user": {
          "id": 2,
          "username": "student",
          "is_admin": false
        },
        "details": [...]
      }
    ],
    "links": {...},
    "meta": {
      "current_page": 1,
      "per_page": 15,
      "total": 1
    }
  }
}
```

### PUT /orders/:id/status

注文のステータスを更新します（**管理者のみ**）。

**リクエスト**:
```
PUT /orders/1/status
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "status": "完了"
}
```

**レスポンス** (200):
```json
{
  "success": true,
  "message": "ステータスを更新しました",
  "data": {
    "id": 1,
    "user_id": 2,
    "total_price": 1400,
    "status": "完了",
    "created_at": "2024-01-02T10:30:00Z",
    "updated_at": "2024-01-02T11:00:00Z"
  }
}
```

### GET /stats/today

本日の統計情報を取得します（**管理者のみ**）。

**リクエスト**:
```
GET /stats/today
Authorization: Bearer {admin_token}
```

**レスポンス** (200):
```json
{
  "success": true,
  "data": {
    "total_orders": 15,
    "pending_orders": 8,
    "completed_orders": 7,
    "today_revenue": 6500
  }
}
```

## エラーハンドリング

### 認証エラー (401)

```json
{
  "message": "Unauthenticated."
}
```

### 権限エラー (403)

```json
{
  "success": false,
  "message": "管理者権限が必要です"
}
```

### バリデーションエラー (422)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "quantity": ["The quantity must be at least 1."],
    "product_id": ["The selected product_id is invalid."]
  }
}
```

### リソース見つからず (404)

```json
{
  "message": "Not found"
}
```

## 使用例（JavaScript）

### ログイン

```javascript
const response = await fetch('http://localhost:8000/api/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    username: 'student',
    password: '1234'
  })
});

const data = await response.json();
const token = data.data.token;
```

### 注文作成

```javascript
const response = await fetch('http://localhost:8000/api/orders', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    items: [
      { product_id: 1, quantity: 2 },
      { product_id: 3, quantity: 1 }
    ]
  })
});

const data = await response.json();
console.log(data.data);
```

## レート制限

現在のバージョンではレート制限は設定されていません。本番環境では、Laravel Throttleミドルウェアの導入を推奨します。

## ページング

複数件のデータを返すエンドポイントはページングに対応しています：

```javascript
// ページ2を取得
GET /api/orders/my/list?page=2
```

レスポンスには以下の情報が含まれます：
- `data`: 該当ページのデータ配列
- `links`: 前後のページへのリンク
- `meta`: 総件数、現在のページ等のメタデータ
