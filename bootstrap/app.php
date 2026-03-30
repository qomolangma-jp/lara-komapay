<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use App\Http\Controllers\Api\AuthController;

date_default_timezone_set('Asia/Tokyo');

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend([
            \App\Http\Middleware\NormalizeApiPathMiddleware::class,
        ]);

        $middleware->api(prepend: [
            \App\Http\Middleware\DebugRequestMiddleware::class,
            \App\Http\Middleware\CorsMiddleware::class,
        ]);
        
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'seller' => \App\Http\Middleware\SellerMiddleware::class,
            'seller.auth' => \App\Http\Middleware\SellerAuthMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 二重スラッシュ等で route 解決に失敗した auth エンドポイントを救済
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            $message = (string) $e->getMessage();

            if (stripos($message, 'route api/auth/check could not be found') !== false
                || stripos($message, 'route api/auth/line-login could not be found') !== false) {
                return app(AuthController::class)->check($request);
            }
        });

        // 404 エラーを JSON で返す
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'エンドポイントが見つかりません',
                    'error' => $e->getMessage() ?: 'Not Found',
                ], 404);
            }
        });

        // 認証エラーを JSON で返す
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => '認証が必要です',
                ], 401);
            }
        });
    })->create();
