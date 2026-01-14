# 学校食堂注文システム - Laravel 12移行ガイド

## 📋 プロジェクト概要

このドキュメントは、PHPベースの学校食堂注文システムをLaravel 12にマイグレーションするプロセスを説明します。

### 移行前後の比較

#### 移行前（PHP + PDO）

```
index.php
admin.php
student.php
├─ includes/
│  ├─ config.php （PDO接続）
│  └─ ...
├─ css/
├─ uploads/
└─ templates/ （HTMLテンプレート）
```

**特徴**:
- PDOで直接SQLを実行
- セッション管理は手作業
- バリデーション無し
- エラーハンドリングが不統一

#### 移行後（Laravel 12）

```
laravel-app/
├─ app/
│  ├─ Http/Controllers/Api/  （APIロジック）
│  ├─ Models/                 （Eloquent Models）
│  └─ Http/Middleware/        （認証・権限）
├─ routes/
│  └─ api.php                （ルート定義）
├─ database/
│  ├─ migrations/             （スキーマバージョン管理）
│  └─ seeders/                （初期データ）
├─ resources/views/           （フロント用テンプレート）
└─ docker-compose.yml         （Docker構成）
```

**メリット**:
- ✅ セキュリティが強化（CSRF、SQL Injection対策）
- ✅ コードが整理される（MVC分離）
- ✅ テストが容易（PHPUnit統合）
- ✅ 開発速度向上（Artisan CLIツール）
- ✅ チーム開発が楽（規約が統一）

## 🔧 主要な技術スタック

### バックエンド
- **PHP 8.2**: 最新の言語機能
- **Laravel 12**: フルスタックフレームワーク
- **Eloquent ORM**: オブジェクト指向DB操作
- **Laravel Sanctum**: トークン認証
- **MySQL 8.0**: リレーショナルDB

### フロント
- **Vue.js / React** (推奨): API連携
- **Bootstrap 5**: UIフレームワーク
- **Vite**: モジュールバンドラー

### 開発環境
- **Docker**: コンテナ化
- **Docker Compose**: マルチコンテナ管理
- **phpMyAdmin**: DB管理ツール

## 📁 ディレクトリ構成の説明

### `laravel-app/app/Models`

Eloquentモデルが配置されます。各モデルは1つのデータベーステーブルに対応します。

```php
<?php
// User.php
namespace App\Models;

class User extends Model
{
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
```

**メリット**:
- SQLを書かずにデータベース操作が可能
- リレーション（1対多、多対多）が簡単に定義できる
- 遅延ロード・先読みで効率的にデータ取得

### `laravel-app/app/Http/Controllers`

ビジネスロジックを実装するコントローラーが配置されます。

```php
<?php
// OrderController.php
namespace App\Http\Controllers\Api;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        // 注文作成ロジック
    }
}
```

**メリット**:
- リクエスト処理を一元化
- バリデーションが統合
- 例外処理が統一される

### `laravel-app/database/migrations`

スキーマバージョン管理ファイル。既存のSQLファイルの代わりです。

```php
<?php
// 2024_01_01_000000_create_users_table.php
class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->timestamps();
        });
    }
}
```

**メリット**:
- スキーマの変更履歴が保存される
- ロールバック・リード・フォワードが可能
- マイグレーション実行順序が自動管理される

### `laravel-app/database/seeders`

初期データやテストデータを投入するシーダーです。

```php
<?php
// DatabaseSeeder.php
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'username' => 'admin',
            'password' => Hash::make('admin'),
            'is_admin' => true
        ]);
    }
}
```

## 🔄 既存機能の移行マッピング

### ユーザー認証

#### 移行前（config.php）
```php
<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
```

#### 移行後（Laravel）
```php
<?php
// ミドルウェア: auth:sanctum

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
});

// コントローラー内
$user = auth('sanctum')->user();
```

### 注文作成（トランザクション付き）

#### 移行前
```php
<?php
try {
    $pdo->beginTransaction();
    
    // 注文作成
    $stmt = $pdo->prepare("INSERT INTO orders ...");
    $stmt->execute([...]);
    
    // 在庫更新
    $stmt = $pdo->prepare("UPDATE products ...");
    $stmt->execute([...]);
    
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
}
```

#### 移行後
```php
<?php
use Illuminate\Support\Facades\DB;

DB::transaction(function () {
    $order = Order::create([...]);
    Product::find($product_id)->decrementStock($quantity);
});
```

### バリデーション

#### 移行前
```php
<?php
if (empty($_POST['username'])) {
    echo "ユーザー名は必須です";
}
```

#### 移行後
```php
<?php
$validated = $request->validate([
    'username' => 'required|string|max:150|unique:users',
    'password' => 'required|string|min:4',
]);
```

## 📚 主要Eloquentクエリの例

### シンプルなクエリ

```php
<?php
// すべての商品を取得
$products = Product::all();

// IDで検索
$product = Product::find(1);

// 条件付きで検索
$available = Product::where('stock', '>', 0)->get();
```

### リレーション

```php
<?php
// ユーザーの注文を取得（N+1問題を回避）
$orders = Order::with('user', 'details.product')->get();

// 注文してくださいレコードを削除
Product::whereDoesntHave('orderDetails')->delete();
```

### ページング

```php
<?php
// 1ページあたり15件
$orders = Order::paginate(15);

// 結果: {data: [...], links: {...}, meta: {...}}
```

## 🚀 フロントエンド統合パターン

### REST APIの活用

#### JavaScriptでAPI呼び出し

```javascript
// login
const response = await fetch('http://localhost:8000/api/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ username: 'student', password: '1234' })
});
const { data } = await response.json();
localStorage.setItem('token', data.token);
```

#### 注文作成

```javascript
const response = await fetch('http://localhost:8000/api/orders', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    items: [
      { product_id: 1, quantity: 2 }
    ]
  })
});
```

## 🔐 セキュリティ機能

### 自動で含まれる対策

1. **CSRF Protection**: トークンベースの自動保護
2. **パスワードハッシュ化**: BCrypt推奨
3. **SQL Injection防止**: Eloquent + バインディング
4. **XSS対策**: {{ }} で自動エスケープ
5. **認可（Authorization）**: ポリシークラス

### 例: ポリシー（権限チェック）

```php
<?php
// OrderPolicy.php
class OrderPolicy
{
    public function view(User $user, Order $order)
    {
        return $user->id === $order->user_id || $user->is_admin;
    }
}
```

## 📦 Docker環境の利点

1. **環境の統一**: 全メンバーが同じバージョン
2. **本番環境との同期**: 開発環境 = 本番環境
3. **簡単なセットアップ**: `docker-compose up -d`
4. **カプセル化**: ホストマシンへの影響なし

## 🎯 マイグレーション計画

### Phase 1: 基盤構築（完了）
- [x] Laravelプロジェクト初期化
- [x] Eloquentモデル作成
- [x] マイグレーションファイル作成
- [x] REST APIコントローラー実装
- [x] Docker環境構築

### Phase 2: 機能実装
- [ ] フロントエンドUI制作
- [ ] API統合テスト
- [ ] ユーザー認証テスト

### Phase 3: 本番対応
- [ ] パフォーマンス最適化
- [ ] セキュリティ監査
- [ ] ロードテスト

## 📖 参考資料

- [Laravel公式ドキュメント](https://laravel.com/docs/12.x)
- [Eloquent ORM](https://laravel.com/docs/12.x/eloquent)
- [Laravel API Resources](https://laravel.com/docs/12.x/eloquent-resources)
- [Docker Documentation](https://docs.docker.com/)

## 🤝 開発チームへのアドバイス

### 学習リソース

1. **PHP 8.2の新機能**
   - Match式
   - Named Arguments
   - Union Types

2. **Laravel 12の重要概念**
   - Service Container（依存性注入）
   - Middleware（ミドルウェア）
   - Query Builder（クエリビルダ）

3. **テスト駆動開発**
   ```bash
   php artisan make:test OrderTest
   ```

### コーディング規約

Laravel Pint（PHP Code Style Fixer）で自動整形：

```bash
# コーディング規約をチェック
./vendor/bin/pint --test

# 自動修正
./vendor/bin/pint
```

## 💡 トラブルシューティング

### よくあるエラー

| エラー | 原因 | 解決策 |
|--------|------|--------|
| `SQLSTATE[HY000]: General error` | DB接続エラー | `docker-compose logs db` で確認 |
| `Class not found` | オートローダ更新なし | `composer dump-autoload` |
| `Port 8000 already in use` | ポート競合 | docker-compose.yml でポート変更 |

## 📞 サポート

質問や問題があれば、以下をチェック：

1. `docker-compose logs` でログ確認
2. `.env` ファイルの環境変数確認
3. Laravel Tinker で動作確認
   ```bash
   docker-compose exec web php artisan tinker
   > User::all()
   ```

---

**バージョン**: 1.0  
**最終更新**: 2024年12月26日
