<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderWindowController;
use App\Http\Controllers\Api\ImageUploadController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\NewsController;

// ===== 全リクエスト ログ=====
if (app()->environment() !== 'production' || env('APP_DEBUG') === true) {
    Route::middleware([])->group(function () {
        // キャッチオール：すべてのリクエストのパスを記録
        Route::fallback(function (Request $request) {
            Log::warning('Fallback Route Hit - Unmatched Request', [
                'method' => $request->getMethod(),
                'path' => $request->path(),
                'uri' => $request->getRequestUri(),
                'timestamp' => now()->toIso8601String(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'エンドポイントが見つかりません',
                'requested_path' => $request->path(),
                'available_endpoints' => [
                    'GET /api/health',
                    'POST /api/diagnose',
                    'GET /api/test',
                    'POST /api/test',
                    'POST /api/auth/check',
                    'GET|POST /api/auth/line-login',
                    'POST /api/auth/login',
                    'POST /api/auth/register',
                    'POST /api/auth/line-callback',
                ],
            ], 404);
        });
    });
}

// ===== 診断用エンドポイント =====
Route::get('/health', function (Request $request) {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'path_detected' => $request->path(),
        'uri' => $request->getRequestUri(),
        'environment' => app()->environment(),
        'debug_mode' => config('app.debug'),
    ]);
});

Route::post('/diagnose', function (Request $request) {
    return response()->json([
        'status' => 'ok',
        'message' => 'Diagnostic endpoint is working',
        'request_method' => $request->getMethod(),
        'request_path' => $request->path(),
        'request_uri' => $request->getRequestUri(),
        'origin' => $request->header('Origin'),
        'user_agent' => $request->header('User-Agent'),
        'timestamp' => now()->toIso8601String(),
    ]);
});

// テスト用エンドポイント（CORS確認用）
Route::get('/test', function (Request $request) {
    return response()->json([
        'status' => 'ok',
        'message' => 'API is working',
        'timestamp' => now()->toIso8601String(),
        'origin' => $request->header('Origin'),
    ]);
});

Route::post('/test', function (Request $request) {
    return response()->json([
        'status' => 'ok',
        'message' => 'POST request received',
        'received_data' => $request->all(),
        'timestamp' => now()->toIso8601String(),
    ]);
});

// ===== 認証不要のエンドポイント =====
Route::match(['GET', 'POST'], '/auth/check', [AuthController::class, 'check']);
Route::match(['GET', 'POST'], '/auth/line-login', [AuthController::class, 'check']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/line-callback', [AuthController::class, 'lineCallback']);

// 商品閲覧（認証不要）
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/categories/list', [ProductController::class, 'categories']);
Route::get('/products/{id}', [ProductController::class, 'show'])->whereNumber('id');
Route::get('/search', [ProductController::class, 'index']); // 検索用エイリアス

// お知らせ（認証関連は仕様によるが、閲覧は公開とするならここ）
Route::get('/news', [NewsController::class, 'index']);
Route::get('/news/{news}', [NewsController::class, 'show'])->whereNumber('news');

// 受け取り可能情報（モニター用、認証不要とするか検討だが一旦公開）
Route::get('/pickup-info', [OrderController::class, 'pickupList']);

// マスター管理画面用（開発環境：認証不要）
Route::get('/master/cart', [CartController::class, 'getAllCarts']);
Route::delete('/master/cart/{id}', [CartController::class, 'adminRemove']);
Route::get('/master/users', [AuthController::class, 'users']);
Route::post('/master/users', [AuthController::class, 'create']);
Route::put('/master/users/{user}', [AuthController::class, 'update']);
Route::delete('/master/users/{user}', [AuthController::class, 'destroy']);
Route::get('/master/products', [ProductController::class, 'index']);
Route::get('/master/products/{id}', [ProductController::class, 'show'])->whereNumber('id');
Route::post('/master/products', [ProductController::class, 'store']);
Route::put('/master/products/{product}', [ProductController::class, 'update']);
Route::delete('/master/products/{product}', [ProductController::class, 'destroy']);
Route::post('/master/upload-image', [ImageUploadController::class, 'upload']);
Route::get('/master/orders', [OrderController::class, 'index']);
Route::get('/master/orders/{order}', [OrderController::class, 'show']);
Route::put('/master/orders/{order}/status', [OrderController::class, 'updateStatus']);
Route::get('/master/order-windows', [OrderWindowController::class, 'index']);
Route::post('/master/order-windows', [OrderWindowController::class, 'upsertMany']);
Route::post('/master/order-windows/clear', [OrderWindowController::class, 'clearMany']);
Route::get('/master/news', [NewsController::class, 'index']);
Route::post('/master/news', [NewsController::class, 'store']);
Route::put('/master/news/{news}', [NewsController::class, 'update']);
Route::delete('/master/news/{news}', [NewsController::class, 'destroy']);
Route::get('/master/stats/sales', [OrderController::class, 'sales']);

// 認証が必要なエンドポイント
Route::middleware('auth:sanctum')->group(function () {
    // 認証情報
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // 認証済みユーザー向けニュース管理
    Route::get('/seller/news', [NewsController::class, 'index']);
    Route::post('/seller/news', [NewsController::class, 'store']);
    Route::put('/seller/news/{news}', [NewsController::class, 'update']);
    Route::delete('/seller/news/{news}', [NewsController::class, 'destroy']);

    // 旧エンドポイント互換（接続復旧用）
    Route::post('/news', [NewsController::class, 'store']);
    Route::put('/news/{news}', [NewsController::class, 'update']);
    Route::delete('/news/{news}', [NewsController::class, 'destroy']);
    
    // 販売者・管理者共通（商品管理）
    Route::middleware('seller')->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
        Route::post('/products/{product}/stock', [ProductController::class, 'updateStock']); // 在庫更新
        Route::post('/upload-image', [ImageUploadController::class, 'upload']);
    });
    
    // 管理者のみ
    Route::middleware('admin')->group(function () {
        // ユーザー一覧
        Route::get('/auth/users', [AuthController::class, 'users']);
        Route::post('/auth/users', [AuthController::class, 'create']); // 新規作成
        Route::put('/auth/users/{user}', [AuthController::class, 'update']);
        Route::delete('/auth/users/{user}', [AuthController::class, 'destroy']);
        
        // 受け取り情報削除
        Route::delete('/pickup-info/{order}', [OrderController::class, 'completePickup']);
    });

    // 注文エンドポイント
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/my/list', [OrderController::class, 'myOrders']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);

    // 管理者のみ
    Route::middleware('admin')->group(function () {
        Route::get('/orders', [OrderController::class, 'index']);
        Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);
        Route::get('/stats/today', [OrderController::class, 'todayStats']);
        Route::get('/stats/sales', [OrderController::class, 'sales']);
    });
});

// カートエンドポイントは Sanctum とセッションの両方を許容する
Route::match(['GET', 'POST'], '/cart', [CartController::class, 'index'])
    ->withoutMiddleware('auth:sanctum');
Route::post('/cart/add', [CartController::class, 'add'])
    ->withoutMiddleware('auth:sanctum');
Route::put('/cart/{id}', [CartController::class, 'update'])
    ->withoutMiddleware('auth:sanctum');
Route::delete('/cart/{id}', [CartController::class, 'remove'])
    ->withoutMiddleware('auth:sanctum');
Route::delete('/cart', [CartController::class, 'clear'])
    ->withoutMiddleware('auth:sanctum');
