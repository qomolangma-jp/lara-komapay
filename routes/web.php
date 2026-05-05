
<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\OrderController;
use App\Models\News;
use App\Models\Order;
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

Route::match(['POST', 'OPTIONS'], '/api/auth/register', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(AuthController::class)
        ->register($request)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->withoutMiddleware([ValidateCsrfToken::class]);

// 互換ルート: //api/auth/register が /auth/register に潰れた場合を吸収
Route::match(['POST', 'OPTIONS'], '/auth/register', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(AuthController::class)
        ->register($request)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->withoutMiddleware([ValidateCsrfToken::class]);

Route::match(['POST', 'OPTIONS'], '/api/auth/login', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(AuthController::class)
        ->login($request)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->withoutMiddleware([ValidateCsrfToken::class]);

// 互換ルート: //api/auth/login が /auth/login に潰れた場合を吸収
Route::match(['POST', 'OPTIONS'], '/auth/login', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(AuthController::class)
        ->login($request)
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

Route::match(['GET', 'OPTIONS'], '/api/news/{id}', function (Request $request, int $id) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    $news = News::find($id);
    if (! $news) {
        return response()->json([
            'success' => false,
            'message' => 'ニュースが見つかりません',
        ], 404)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(NewsController::class)
        ->show($news)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->whereNumber('id')->withoutMiddleware([ValidateCsrfToken::class]);

// 互換ルート: //api/news が /news に潰れた場合を吸収
Route::match(['GET', 'OPTIONS'], '/news', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(NewsController::class)
        ->index($request)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->withoutMiddleware([ValidateCsrfToken::class]);

// 互換ルート: //api/news/{id} が /news/{id} に潰れた場合を吸収
Route::match(['GET', 'OPTIONS'], '/news/{id}', function (Request $request, int $id) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    $news = News::find($id);
    if (! $news) {
        return response()->json([
            'success' => false,
            'message' => 'ニュースが見つかりません',
        ], 404)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(NewsController::class)
        ->show($news)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->whereNumber('id')->withoutMiddleware([ValidateCsrfToken::class]);

Route::match(['GET', 'POST', 'OPTIONS'], '/api/cart', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(CartController::class)
        ->index($request)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->withoutMiddleware([ValidateCsrfToken::class]);

// 互換ルート: //api/cart が /cart に潰れた場合を吸収
Route::match(['GET', 'POST', 'OPTIONS'], '/cart', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(CartController::class)
        ->index($request)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->withoutMiddleware([ValidateCsrfToken::class]);

Route::match(['PUT', 'DELETE', 'OPTIONS'], '/api/cart/{id}', function (Request $request, int $id) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    if ($request->isMethod('PUT')) {
        return app(CartController::class)
            ->update($request, $id)
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(CartController::class)
        ->remove($request, $id)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->whereNumber('id')->withoutMiddleware([ValidateCsrfToken::class]);

// 互換ルート: //api/cart/{id} が /cart/{id} に潰れた場合を吸収
Route::match(['PUT', 'DELETE', 'OPTIONS'], '/cart/{id}', function (Request $request, int $id) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    if ($request->isMethod('PUT')) {
        return app(CartController::class)
            ->update($request, $id)
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(CartController::class)
        ->remove($request, $id)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->whereNumber('id')->withoutMiddleware([ValidateCsrfToken::class]);

Route::match(['POST', 'OPTIONS'], '/api/cart/add', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
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

    return app(CartController::class)
        ->add($request)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->withoutMiddleware([ValidateCsrfToken::class]);

Route::match(['DELETE', 'OPTIONS'], '/api/cart', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(CartController::class)
        ->clear($request)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->withoutMiddleware([ValidateCsrfToken::class]);

// 互換ルート: //api/cart が /cart に潰れた場合を吸収（DELETE）
Route::match(['DELETE', 'OPTIONS'], '/cart', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(CartController::class)
        ->clear($request)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->withoutMiddleware([ValidateCsrfToken::class]);

Route::match(['POST', 'OPTIONS'], '/api/orders', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    if (! auth('sanctum')->user()) {
        return response()->json([
            'success' => false,
            'message' => '認証が必要です',
        ], 401)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(OrderController::class)
        ->store($request)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->withoutMiddleware([ValidateCsrfToken::class]);

// 互換ルート: //api/orders が /orders に潰れた場合を吸収
Route::match(['POST', 'OPTIONS'], '/orders', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    if (! auth('sanctum')->user()) {
        return response()->json([
            'success' => false,
            'message' => '認証が必要です',
        ], 401)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(OrderController::class)
        ->store($request)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->withoutMiddleware([ValidateCsrfToken::class]);

Route::match(['GET', 'OPTIONS'], '/api/orders/my/list', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    if (! auth('sanctum')->user()) {
        return response()->json([
            'success' => false,
            'message' => '認証が必要です',
        ], 401)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(OrderController::class)
        ->myOrders($request)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->withoutMiddleware([ValidateCsrfToken::class]);

// 互換ルート: //api/orders/my/list が /orders/my/list に潰れた場合を吸収
Route::match(['GET', 'OPTIONS'], '/orders/my/list', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    if (! auth('sanctum')->user()) {
        return response()->json([
            'success' => false,
            'message' => '認証が必要です',
        ], 401)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(OrderController::class)
        ->myOrders($request)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->withoutMiddleware([ValidateCsrfToken::class]);

Route::match(['GET', 'OPTIONS'], '/api/orders/{id}', function (Request $request, int $id) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    if (! auth('sanctum')->user()) {
        return response()->json([
            'success' => false,
            'message' => '認証が必要です',
        ], 401)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    $order = Order::find($id);
    if (! $order) {
        return response()->json([
            'success' => false,
            'message' => '注文が見つかりません',
        ], 404)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(OrderController::class)
        ->show($order)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->whereNumber('id')->withoutMiddleware([ValidateCsrfToken::class]);

// 互換ルート: //api/orders/{id} が /orders/{id} に潰れた場合を吸収
Route::match(['GET', 'OPTIONS'], '/orders/{id}', function (Request $request, int $id) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    if (! auth('sanctum')->user()) {
        return response()->json([
            'success' => false,
            'message' => '認証が必要です',
        ], 401)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    $order = Order::find($id);
    if (! $order) {
        return response()->json([
            'success' => false,
            'message' => '注文が見つかりません',
        ], 404)->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return app(OrderController::class)
        ->show($order)
        ->header('Content-Type', 'application/json; charset=UTF-8');
})->whereNumber('id')->withoutMiddleware([ValidateCsrfToken::class]);

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
Route::get('/master/help', [App\Http\Controllers\MasterController::class, 'help'])->name('master.help');
Route::get('/master/cart', [App\Http\Controllers\MasterController::class, 'cart'])->name('master.cart');
Route::get('/master/cart/user/{username}', function ($username) {
    return view('master_admin.cart_user_detail');
})->name('master.cart_user_detail');
Route::get('/master/order-windows', [App\Http\Controllers\MasterController::class, 'orderWindows'])->name('master.order_windows');

// 販売者管理画面
Route::get('/seller', [App\Http\Controllers\SellerController::class, 'index'])->name('seller.index');
Route::get('/seller/help', [App\Http\Controllers\SellerController::class, 'help'])->name('seller.help');
Route::get('/seller/products', [App\Http\Controllers\SellerController::class, 'products'])->name('seller.products');
Route::get('/seller/orders', [App\Http\Controllers\SellerController::class, 'orders'])->name('seller.orders');
Route::get('/seller/news', [App\Http\Controllers\SellerController::class, 'news'])->name('seller.news');
Route::get('/seller/reports', [App\Http\Controllers\SellerController::class, 'reports'])->name('seller.reports');

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

    if ($method === 'GET' && preg_match('#^/api/news/(\d+)/?$#', $normalizedUri, $matches)) {
        $news = News::find((int) $matches[1]);
        if (! $news) {
            return response()->json([
                'success' => false,
                'message' => 'ニュースが見つかりません',
            ], 404)->header('Content-Type', 'application/json; charset=UTF-8');
        }

        return app(NewsController::class)->show($news)
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }

    if (in_array($method, ['GET', 'POST'], true) && preg_match('#^/api/cart/?$#', $normalizedUri)) {
        return app(CartController::class)->index($request)
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }

    if ($method === 'POST' && preg_match('#^/api/cart/add/?$#', $normalizedUri)) {
        return app(CartController::class)->add($request)
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }

    if ($method === 'POST' && preg_match('#^/api/orders/?$#', $normalizedUri)) {
        if (! auth('sanctum')->user()) {
            return response()->json([
                'success' => false,
                'message' => '認証が必要です',
            ], 401)->header('Content-Type', 'application/json; charset=UTF-8');
        }

        return app(OrderController::class)->store($request)
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }

    if ($method === 'DELETE' && preg_match('#^/api/cart/?$#', $normalizedUri)) {
        if (! auth('sanctum')->user()) {
            return response()->json([
                'success' => false,
                'message' => '認証が必要です',
            ], 401)->header('Content-Type', 'application/json; charset=UTF-8');
        }

        return app(CartController::class)->clear($request)
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }

    if ($method === 'GET' && preg_match('#^/api/orders/my/list/?$#', $normalizedUri)) {
        if (! auth('sanctum')->user()) {
            return response()->json([
                'success' => false,
                'message' => '認証が必要です',
            ], 401)->header('Content-Type', 'application/json; charset=UTF-8');
        }

        return app(OrderController::class)->myOrders($request)
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }

    if ($method === 'GET' && preg_match('#^/api/orders/(\d+)/?$#', $normalizedUri, $matches)) {
        if (! auth('sanctum')->user()) {
            return response()->json([
                'success' => false,
                'message' => '認証が必要です',
            ], 401)->header('Content-Type', 'application/json; charset=UTF-8');
        }

        $order = Order::find((int) $matches[1]);
        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => '注文が見つかりません',
            ], 404)->header('Content-Type', 'application/json; charset=UTF-8');
        }

        return app(OrderController::class)->show($order)
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }

    if (in_array($method, ['GET', 'POST'], true)
        && preg_match('#^/api/auth/(check|line-login|login)/?$#', $normalizedUri)) {
        if (preg_match('#^/api/auth/login/?$#', $normalizedUri)) {
            return app(AuthController::class)->login($request)
                ->header('Content-Type', 'application/json; charset=UTF-8');
        }

        return app(AuthController::class)->check($request)
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }

    if ($method === 'POST' && preg_match('#^/api/auth/register/?$#', $normalizedUri)) {
        return app(AuthController::class)->register($request)
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }

    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
        'path' => $normalizedUri,
    ], 404)->header('Content-Type', 'application/json; charset=UTF-8');
});
