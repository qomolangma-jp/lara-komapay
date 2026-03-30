
<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\MigrationController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

// API経路の解決が崩れた場合の受け口（フロント改修なし運用のため）
Route::match(['GET', 'POST', 'OPTIONS'], '/api/auth/check', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(AuthController::class)
        ->check($request)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->withoutMiddleware([ValidateCsrfToken::class]);

Route::match(['GET', 'POST', 'OPTIONS'], '/api/auth/line-login', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(AuthController::class)
        ->check($request)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->withoutMiddleware([ValidateCsrfToken::class]);

Route::match(['GET', 'POST', 'OPTIONS'], '/auth/check', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(AuthController::class)
        ->check($request)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->withoutMiddleware([ValidateCsrfToken::class]);

Route::match(['GET', 'POST', 'OPTIONS'], '/auth/line-login', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(AuthController::class)
        ->check($request)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->withoutMiddleware([ValidateCsrfToken::class]);

// API経路の解決が崩れた場合の主要エンドポイント救済
Route::match(['GET', 'OPTIONS'], '/api/products', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(ProductController::class)
        ->index($request)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->withoutMiddleware([ValidateCsrfToken::class]);

Route::match(['GET', 'OPTIONS'], '/api/products/{id}', function (Request $request, int $id) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(ProductController::class)
        ->show($id)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->whereNumber('id')->withoutMiddleware([ValidateCsrfToken::class]);

// 互換ルート: //api/products がサーバー側で /products に潰れた場合を吸収
Route::match(['GET', 'OPTIONS'], '/products', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(ProductController::class)
        ->index($request)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->withoutMiddleware([ValidateCsrfToken::class]);

// 互換ルート: //api/products/{id} が /products/{id} に潰れた場合を吸収
Route::match(['GET', 'OPTIONS'], '/products/{id}', function (Request $request, int $id) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(ProductController::class)
        ->show($id)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->whereNumber('id')->withoutMiddleware([ValidateCsrfToken::class]);

Route::match(['GET', 'OPTIONS'], '/api/news', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(NewsController::class)
        ->index($request)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->withoutMiddleware([ValidateCsrfToken::class]);

// 互換ルート: //api/news が /news に潰れた場合を吸収
Route::match(['GET', 'OPTIONS'], '/news', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(NewsController::class)
        ->index($request)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->withoutMiddleware([ValidateCsrfToken::class]);

Route::match(['GET', 'OPTIONS'], '/api/cart', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    if (! auth('sanctum')->user()) {
        return response()->json([
            'success' => false,
            'message' => '認証が必要です',
        ], 401)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(CartController::class)
        ->index($request)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->withoutMiddleware([ValidateCsrfToken::class]);

// 互換ルート: //api/cart が /cart に潰れた場合を吸収
Route::match(['GET', 'OPTIONS'], '/cart', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    if (! auth('sanctum')->user()) {
        return response()->json([
            'success' => false,
            'message' => '認証が必要です',
        ], 401)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(CartController::class)
        ->index($request)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->withoutMiddleware([ValidateCsrfToken::class]);

Route::match(['POST', 'OPTIONS'], '/api/cart/add', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    if (! auth('sanctum')->user()) {
        return response()->json([
            'success' => false,
            'message' => '認証が必要です',
        ], 401)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(CartController::class)
        ->add($request)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->withoutMiddleware([ValidateCsrfToken::class]);

// 互換ルート: //api/cart/add が /cart/add に潰れた場合を吸収
Route::match(['POST', 'OPTIONS'], '/cart/add', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    if (! auth('sanctum')->user()) {
        return response()->json([
            'success' => false,
            'message' => '認証が必要です',
        ], 401)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(CartController::class)
        ->add($request)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->withoutMiddleware([ValidateCsrfToken::class]);

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [PageController::class, 'login'])->name('login');
Route::get('/student', [PageController::class, 'student'])->name('student');
Route::get('/master', [App\Http\Controllers\MasterController::class, 'index'])->name('master.index');
Route::get('/master/users', [App\Http\Controllers\MasterController::class, 'users'])->name('master.users');
Route::get('/master/products', [App\Http\Controllers\MasterController::class, 'products'])->name('master.products');
Route::get('/master/orders', [App\Http\Controllers\MasterController::class, 'orders'])->name('master.orders');
Route::get('/master/news', [App\Http\Controllers\MasterController::class, 'news'])->name('master.news');
Route::get('/master/stats', [App\Http\Controllers\MasterController::class, 'stats'])->name('master.stats');
Route::get('/master/cart', [App\Http\Controllers\MasterController::class, 'cart'])->name('master.cart');
Route::get('/master/logs', [App\Http\Controllers\MasterController::class, 'logs'])->name('master.logs');

// 販売者管理画面
Route::get('/seller', [App\Http\Controllers\SellerController::class, 'index'])->name('seller.index');
Route::get('/seller/products', [App\Http\Controllers\SellerController::class, 'products'])->name('seller.products');
Route::get('/seller/orders', [App\Http\Controllers\SellerController::class, 'orders'])->name('seller.orders');
Route::get('/seller/news', [App\Http\Controllers\SellerController::class, 'news'])->name('seller.news');

// マイグレーション管理（管理者のみ）
Route::get('/master/migration', [MigrationController::class, 'index'])->name('master.migration');
Route::get('/migration/status', [MigrationController::class, 'status']);
Route::post('/migration/migrate', [MigrationController::class, 'migrate']);
Route::post('/migration/rollback', [MigrationController::class, 'rollback']);
Route::post('/migration/clear-cache', [MigrationController::class, 'clearCache']);
Route::post('/migration/check-table', [MigrationController::class, 'checkTable']);

// 旧形式のマイグレーション（後方互換性のため保持）
Route::get('/migrate', [MigrationController::class, 'migrate']);
Route::get('/migrate-fresh', [MigrationController::class, 'fresh']);

// 最終救済: API風パスがweb側404に落ちた場合でも、バックエンドだけで吸収してJSON応答する
Route::fallback(function (Request $request) {
    $requestUri = (string) $request->getRequestUri();
    $normalizedUri = preg_replace('#/+#', '/', $requestUri) ?: $requestUri;
    $normalizedUri = '/' . ltrim($normalizedUri, '/');

    $isApiLike = str_contains($normalizedUri, '/api/') || str_starts_with($normalizedUri, '/api');
    if (! $isApiLike) {
        abort(404);
    }

    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    $method = strtoupper($request->getMethod());

    if ($method === 'GET' && preg_match('#^/api/products/?$#', $normalizedUri)) {
        return app(ProductController::class)->index($request)
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }

    if ($method === 'GET' && preg_match('#^/api/products/(\d+)/?$#', $normalizedUri, $matches)) {
        return app(ProductController::class)->show((int) $matches[1])
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }

    if ($method === 'GET' && preg_match('#^/api/news/?$#', $normalizedUri)) {
        return app(NewsController::class)->index($request)
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }

    if ($method === 'GET' && preg_match('#^/api/cart/?$#', $normalizedUri)) {
        return app(CartController::class)->index($request)
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }

    if ($method === 'POST' && preg_match('#^/api/cart/add/?$#', $normalizedUri)) {
        return app(CartController::class)->add($request)
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }

    if (in_array($method, ['GET', 'POST'], true)
        && preg_match('#^/api/auth/(check|line-login)/?$#', $normalizedUri)) {
        return app(AuthController::class)->check($request)
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
        'path' => $normalizedUri,
    ], 404)->header('Content-Type', 'application/json; charset=UTF-8');
});
