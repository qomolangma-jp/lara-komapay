<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ImageUploadController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\NewsController;

// 認証不要のエンドポイント
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// 商品閲覧（認証不要）
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/products/categories/list', [ProductController::class, 'categories']);
Route::get('/search', [ProductController::class, 'index']); // 検索用エイリアス

// お知らせ（認証関連は仕様によるが、閲覧は公開とするならここ）
Route::get('/news', [NewsController::class, 'index']);

// 受け取り可能情報（モニター用、認証不要とするか検討だが一旦公開）
Route::get('/pickup-info', [OrderController::class, 'pickupList']);

// 認証が必要なエンドポイント
Route::middleware('auth:sanctum')->group(function () {
    // 認証情報
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    
    // カートエンドポイント
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::put('/cart/{id}', [CartController::class, 'update']);
    Route::delete('/cart/{id}', [CartController::class, 'remove']);
    Route::delete('/cart', [CartController::class, 'clear']);
    
    // 管理者のみ
    Route::middleware('admin')->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
        Route::post('/products/{product}/stock', [ProductController::class, 'updateStock']); // 在庫更新
        
        Route::post('/upload-image', [ImageUploadController::class, 'upload']);
        
        // お知らせ管理
        Route::post('/news', [NewsController::class, 'store']);
        Route::delete('/news/{news}', [NewsController::class, 'destroy']);
        
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
