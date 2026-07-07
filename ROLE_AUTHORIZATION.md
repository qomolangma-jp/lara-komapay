# 権限管理システム

## システム概要

ユーザーを4つのロール（役割）に分類して、明確な権限分割を実現しています。

## ロール定義

### 1. **マスター管理者** (`master_admin`)
- **説明**: システム全体の管理者。最高権限を持つ。
- **権限範囲**:
  - ✅ ユーザー管理（作成、編集、削除）
  - ✅ ユーザーロール変更
  - ✅ システム設定
  - ✅ すべての一般管理者機能
  - ✅ データベース初期化・リセット
  - ✅ システムログ閲覧
  - ✅ 監査ログ全件閲覧

### 2. **一般管理者** (`admin`)
- **説明**: 運営用管理者。業務運営に必要な機能にアクセス可能。
- **権限範囲**:
  - ✅ ニュース管理（作成、編集、削除）
  - ✅ 商品管理（申請内容の承認・却下）
  - ✅ 注文管理（確認、キャンセル処理）
  - ✅ 販売者からの問い合わせ対応
  - ✅ 監査ログ（自分以外の一般管理者操作除く）
  - ❌ ユーザー管理（マスター管理者のみ）
  - ❌ ユーザーロール変更（マスター管理者のみ）

### 3. **販売者** (`seller`)
- **説明**: 店舗管理者。自分の商品と注文に関する操作が可能。
- **権限範囲**:
  - ✅ 自分の商品登録・管理
  - ✅ 自分の商品の売上確認
  - ✅ 自分の注文確認・管理
  - ✅ マイページ（プロフィール、パスワード変更）
  - ❌ 他の店舗の商品閲覧（管理画面）
  - ❌ 他の販売者の注文確認
  - ❌ システム設定

### 4. **通常ユーザー** (`user`)
- **説明**: 購入者。商品閲覧と購入に限定。
- **権限範囲**:
  - ✅ 商品閲覧
  - ✅ かごに入れる・購入
  - ✅ 自分の注文履歴確認
  - ✅ マイページ（プロフィール、パスワード変更）
  - ❌ 商品登録
  - ❌ 他のユーザーの情報閲覧
  - ❌ 管理機能へのアクセス

## 権限チェック方法

### 1. ミドルウェアを使用（推奨）

ルート定義でミドルウェアを指定:

```php
// マスター管理者のみ
Route::post('/api/admin/users', [UserController::class, 'store'])
    ->middleware('auth:sanctum', 'master_admin');

// 一般管理者以上
Route::post('/api/admin/news', [NewsController::class, 'store'])
    ->middleware('auth:sanctum', 'admin');

// 販売者以上
Route::post('/api/seller/products', [ProductController::class, 'store'])
    ->middleware('auth:sanctum', 'seller');
```

### 2. Userモデルのメソッドを使用

コントローラーで権限チェック:

```php
$user = auth('sanctum')->user();

// ロール確認
if ($user->isMasterAdmin()) { }        // マスター管理者か
if ($user->isGeneralAdmin()) { }       // 一般管理者か
if ($user->isSeller()) { }             // 販売者か
if ($user->isRegularUser()) { }        // 通常ユーザーか

// 複合条件
if ($user->isAdministrator()) { }      // 管理者（マスター or 一般）か
if ($user->isSellerOrHigher()) { }     // 販売者以上か

// ロール情報取得
$role = $user->role;                   // UserRole enum
$roleString = $user->getRoleString();  // 文字列: "master_admin"
$roleLabel = $user->getRoleLabel();    // 日本語: "マスター管理者"
```

### 3. AuthorizationTraitを使用（コントローラー内）

コントローラーでトレイトを使用: ```php
use App\Traits\AuthorizationTrait;

class UserController extends Controller
{
    use AuthorizationTrait;

    public function delete($id)
    {
        // マスター管理者のみ実行可能
        $this->requireMasterAdmin();
        
        //処理...
    }

    public function store(Request $request)
    {
        // マスター管理者と一般管理者
        $this->requireAdmin();
        
        // 処理...
    }

    public function updateProfile(Request $request)
    {
        // 認証ユーザーなら OK
        $this->requireAuth();
        
        // 処理...
    }
}
```

## データベース

### usersテーブル

新たに追加されたカラム:

```
- role: string (enum値を格納)
  - master_admin: マスター管理者
  - admin: 一般管理者
  - seller: 販売者
  - user: 通常ユーザー
```

既存カラム（後方互換性を保つ）:

```
- is_admin: boolean（廃止予定）
- status: string（廃止予定。seller との統合）
```

## ロール一覧ページ 

ユーザー管理画面でロール変更時の使用例:

```php
use App\Enums\UserRole;

// すべてのロールを取得
$allRoles = UserRole::cases();
// または
$roleValues = UserRole::getAllValues();

// 各ロールの情報を取得
foreach ($allRoles as $role) {
    echo $role->value;            // "master_admin"
    echo $role->getLabel();        // "マスター管理者"
    echo $role->getDescription();  // "システム全体の管理者...
}
```

## セキュリティのポイント

1. **ミドルウェア検証**: ルートレベルで権限チェックを必ず実施
2. **多重チェック**: 重要な操作はコントローラーでも再度チェック
3. **ログ記録**: 管理者操作のすべてを監査ログに記録
4. **最小権限の原則**: ユーザーに必要な最小限の権限のみを付与
5. **ロール変更の監査**: ロール変更はマスター管理者のみが可能

## マイグレーション

マイグレーション実行:

```bash
php artisan migrate
```

ロールカラムが新たに作成されます。既存ユーザーはデフォルトで `user` ロールが割り当てられます。

## ロール初期化スクリプト

既存ユーザーのロール設定例:

```php
use App\Models\User;
use App\Enums\UserRole;

// is_admin = true のユーザーをマスター管理者に
User::where('is_admin', true)->update(['role' => UserRole::MASTER_ADMIN->value]);

// status = 'admin' のユーザーを一般管理者に
User::where('status', 'admin')->update(['role' => UserRole::ADMIN->value]);

// status = 'seller' のユーザーを販売者に
User::where('status', 'seller')->update(['role' => UserRole::SELLER->value]);

// その他は通常ユーザーに
User::whereNull('role')->update(['role' => UserRole::USER->value]);
```

## FAQ

### Q: 元の `is_admin` フィールドはどうなるの?
A: 後方互換性のため保持されています。新規開発では `role` フィールドを使用してください。

### Q: ロール変更はどうやるの?
A: マスター管理者のみが実行可能です。管理者用APIエンドポイント経由で変更します。

### Q: 販売者が複数の店舗を管理できるの?
A: 現在の実装では1ユーザー=1店舗です。複数店舗管理が必要な場合は別途実装が必要です。

## 関連ファイル

- 📁 **ロール定義**: `app/Enums/UserRole.php`
- 📁 **ユーザーモデル**: `app/Models/User.php`
- 📁 **マスター管理者ミドルウェア**: `app/Http/Middleware/MasterAdminMiddleware.php`
- 📁 **管理者ミドルウェア**: `app/Http/Middleware/AdminMiddleware.php`
- 📁 **販売者ミドルウェア**: `app/Http/Middleware/SellerMiddleware.php`
- 📁 **権限チェックトレイト**: `app/Traits/AuthorizationTrait.php`
- 📁 **マイグレーション**: `database/migrations/2026_07_07_000000_add_role_to_users_table.php`
