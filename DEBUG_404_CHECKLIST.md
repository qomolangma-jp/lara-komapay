# 404 エラー診断・チェックリスト

## 🔍 実施した検証

### 1️⃣ **ルート定義の確認**

```
✅ routes/api.php に Route::prefix('api') がないことを確認
✅ /auth/check ルート: Route::match(['GET', 'POST'], '/auth/check', ...) で定義
✅ /auth/line-login ルート: Route::match(['GET', 'POST'], '/auth/line-login', ...) で定義
✅ bootstrap/app.php での api パラメータ設定が正しい（自動プレフィックス）
```

**期待される URL:**
- `/api/auth/check` (GET/POST)
- `/api/auth/line-login` (GET/POST)

---

### 2️⃣ **ミドルウェア干渉チェック**

**追加したミドルウェア:**
- `DebugRequestMiddleware` - すべてのリクエストをキャプチャ
- `CorsMiddleware` - CORS ヘッダー処理（デバッグログ追加）

**確認済み:**
✅ ミドルウェアが `abort(404)` を返していない
✅ 未認証時に 401 を正しく返す設定あり

---

### 3️⃣ **エントリポイント検証**

**public/index.php:**
```php
✅ require __DIR__.'/../vendor/autoload.php' で Composer 自動ロード
✅ $app = require_once __DIR__.'/../bootstrap/app.php' で框架初期化
✅ $kernel->handle($request)->send() でリクエスト処理
```

**vercel.json リライト設定:**
```json
"rewrites": [
  {
    "source": "/(.*)",
    "destination": "/public/index.php"
  }
]
```

---

### 4️⃣ **デバッグログ追加**

**3 つのレベルでログ記録:**

1. **ミドルウェアレベル** (`DebugRequestMiddleware`)
   - リクエスト受信時: メソッド、パス、ルート名
   - レスポンス送信時: ステータスコード

2. **ルートレベル** (`routes/api.php`)
   - プリフライト（OPTIONS）
   - キャッチオール (Fallback)

3. **CORS ミドルウェアレベル** (`CorsMiddleware`)
   - Origin ヘッダー確認
   - 許可されたオリジン判定

---

## 🧪 **テスト手順**

### ステップ 1: ローカルでログを有効化

```bash
# キャッシュクリア
php artisan config:clear
php artisan cache:clear
php artisan route:cache --force

# 開発モード確認
php artisan tinker
>>> env('APP_DEBUG')
true  # ← true であることを確認
```

### ステップ 2: ローカル サーバー起動（デバッグモード）

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

### ステップ 3: リアルタイムログ監視

**別のターミナルで:**
```bash
tail -f storage/logs/laravel.log
# または
php artisan logs
```

### ステップ 4: テストリクエスト実行

```bash
# 診断エンドポイント
curl -X GET http://localhost:8000/api/health | jq

# プリフライトリクエスト (OPTIONS)
curl -X OPTIONS http://localhost:8000/api/auth/check \
  -H "Origin: http://localhost:5173" \
  -H "Access-Control-Request-Method: POST" | jq

# 実際のリクエスト
curl -X POST http://localhost:8000/api/auth/check \
  -H "Content-Type: application/json" \
  -H "Origin: http://localhost:5173" \
  -d '{"line_id":"U190494a7dc6a8363b27a53bb52d8166d"}' | jq
```

**ログ出力例（正常時）:**
```
[2026-03-30 12:00:00] debug.DEBUG: === API Request Received ===
array (
  'method' => 'POST',
  'path' => 'auth/check',
  'full_path' => '/api/auth/check',
  'matching_routes' => 'App\Http\Controllers\Api\AuthController@check',
)
[2026-03-30 12:00:00] debug.DEBUG: CORS Middleware Processing
array (
  'method' => 'POST',
  'path' => 'auth/check',
  'origin_header' => 'http://localhost:5173',
  'allowed_origin' => 'http://localhost:5173',
)
```

---

## ⚠️ **期待される結果**

| シナリオ | 動作 | ログ |
|--------|------|------|
| `/api/health` アクセス | ✅ 200 JSON | `matching_routes: Closure` |
| `/api/auth/check` (存在) | ✅ 200/422 JSON | `matching_routes: AuthController@check` |
| `/api/auth/invalid` (存在しない) | ❌ 404 JSON | `Fallback Route Hit` |
| CORS プリフライト | ✅ 200 + CORS ヘッダ | `is_preflight: true` |

---

## 🚨 **デバッグ結果から判断**

### ログが **表示されない** 場合
→ リクエストが `public/index.php` に到達していない
- **確認項目:**
  - vercel.json の rewrite が正しいか
  - Vercel の設定で `public` ディレクトリが指定されていないか

### ログが **表示されるが 404** の場合
→ ルートマッチングに失敗している
- **確認項目:**
  - `php artisan route:list | grep auth` で実際に登録されているか
  - typo がないか

### ログが **表示されるが CORS ヘッダがない** 場合
→ Origin が許可されていない
- **確認項目:**
  - `config/cors.php` で `https://pken-purchase-system.vercel.app` が許可されているか

---

## 📝 **本番デプロイ前チェック**

```bash
# 1. ログをロテーション（容量対策）
php artisan logs:prune

# 2. ミドルウェアを確認（本番では debug ログを削除）
# → 指示後に DebugRequestMiddleware を削除

# 3. ルートキャッシュを再構築
php artisan route:cache

# 4. デプロイ
git add .
git commit -m "Debug: Add logging for 404 investigation"
git push origin main
```
