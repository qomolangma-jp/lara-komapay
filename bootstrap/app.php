<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Support\Facades\Log;
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
            \App\Http\Middleware\ForceApiJsonMiddleware::class,
            \App\Http\Middleware\CorsMiddleware::class,
        ]);

        $middleware->api(prepend: [
            \App\Http\Middleware\DebugRequestMiddleware::class,
        ]);
        
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'seller' => \App\Http\Middleware\SellerMiddleware::class,
            'seller.auth' => \App\Http\Middleware\SellerAuthMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $resolveAllowedOrigin = static function ($request): string {
            $origin = (string) $request->header('Origin', '');
            if ($origin === '') {
                return '';
            }

            $allowedOrigins = (array) config('cors.allowed_origins', []);
            $allowedPatterns = (array) config('cors.allowed_origins_patterns', []);

            if (in_array($origin, $allowedOrigins, true)) {
                return $origin;
            }

            foreach ($allowedPatterns as $pattern) {
                if (@preg_match($pattern, $origin)) {
                    return $origin;
                }
            }

            return '';
        };

        $attachCorsHeaders = static function ($response, $request) use ($resolveAllowedOrigin) {
            $allowedOrigin = $resolveAllowedOrigin($request);
            $allowedMethods = implode(', ', (array) config('cors.allowed_methods', ['*']));
            $allowedHeaders = implode(', ', (array) config('cors.allowed_headers', ['*']));
            $supportsCredentials = (bool) config('cors.supports_credentials', false);

            $response->headers->set('Vary', 'Origin');
            $response->headers->set('Access-Control-Allow-Methods', $allowedMethods);
            $response->headers->set('Access-Control-Allow-Headers', $allowedHeaders);

            if ($allowedOrigin !== '') {
                $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
            }

            if ($supportsCredentials) {
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
            }

            return $response;
        };

        $isApiLikeRequest = static function ($request): bool {
            $uri = (string) $request->getRequestUri();
            $path = '/' . ltrim((string) $request->path(), '/');
            $normalizedUri = preg_replace('#/+#', '/', $uri) ?: $uri;
            $normalizedPath = preg_replace('#/+#', '/', $path) ?: $path;

            return $request->is('api/*')
                || str_contains($normalizedUri, '/api/')
                || str_starts_with($normalizedUri, '/api')
                || str_contains($normalizedPath, '/api/')
                || str_starts_with($normalizedPath, '/api');
        };

        $isAuthCheckLikeRequest = static function ($request): bool {
            $uri = (string) $request->getRequestUri();
            $path = '/' . ltrim((string) $request->path(), '/');
            $normalizedUri = preg_replace('#/+#', '/', $uri) ?: $uri;
            $normalizedPath = preg_replace('#/+#', '/', $path) ?: $path;

            return str_contains($normalizedUri, '/api/auth/check')
                || str_contains($normalizedUri, '/api/auth/line-login')
                || str_contains($normalizedUri, '/auth/check')
                || str_contains($normalizedUri, '/auth/line-login')
                || str_contains($normalizedPath, '/api/auth/check')
                || str_contains($normalizedPath, '/api/auth/line-login')
                || str_contains($normalizedPath, '/auth/check')
                || str_contains($normalizedPath, '/auth/line-login');
        };

        // 汎用救済: //api/... など崩れたAPI URLを正規化して内部再ディスパッチ
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) use ($isApiLikeRequest, $attachCorsHeaders) {
            $requestUri = (string) $request->getRequestUri();
            $normalizedUri = preg_replace('#/+#', '/', $requestUri) ?: $requestUri;
            $normalizedUri = '/' . ltrim($normalizedUri, '/');

            if ($normalizedUri === $requestUri) {
                return null;
            }

            if (! $isApiLikeRequest($request) && ! str_contains($normalizedUri, '/api/')) {
                return null;
            }

            if ((string) $request->headers->get('X-Normalized-Retry', '') === '1') {
                return null;
            }

            $server = $request->server->all();
            $server['REQUEST_URI'] = $normalizedUri;
            $server['PATH_INFO'] = (string) (parse_url($normalizedUri, PHP_URL_PATH) ?: '/');
            $server['HTTP_X_NORMALIZED_RETRY'] = '1';

            $replayed = IlluminateRequest::create(
                $normalizedUri,
                $request->getMethod(),
                $request->request->all(),
                $request->cookies->all(),
                $request->files->all(),
                $server,
                $request->getContent()
            );
            $replayed->headers->replace($request->headers->all());
            $replayed->headers->set('X-Normalized-Retry', '1');

            try {
                $response = app()->handle($replayed);
            } catch (\Throwable $ignored) {
                return null;
            }

            if ((int) $response->getStatusCode() === 404) {
                return null;
            }

            return $attachCorsHeaders($response, $request);
        });

        // auth/check系の崩れたURLを救済
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) use ($isAuthCheckLikeRequest, $attachCorsHeaders) {
            if (! $isAuthCheckLikeRequest($request)) {
                return null;
            }

            if ($request->isMethod('OPTIONS')) {
                $response = response('', 200);
                return $attachCorsHeaders($response, $request);
            }

            $response = app(AuthController::class)->check($request);
            $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

            return $attachCorsHeaders($response, $request);
        });

        // API 404 は常にJSON
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) use ($isApiLikeRequest, $attachCorsHeaders) {
            Log::warning('NotFound debug', [
                'uri' => (string) $request->getRequestUri(),
                'path' => (string) $request->path(),
                'is_api_like' => $isApiLikeRequest($request),
                'message' => (string) $e->getMessage(),
            ]);

            if (! $isApiLikeRequest($request)) {
                return null;
            }

            $response = response()->json([
                'success' => false,
                'message' => 'エンドポイントが見つかりません',
                'error' => $e->getMessage() ?: 'Not Found',
            ], 404);

            return $attachCorsHeaders($response, $request);
        });

        // API 認証エラーもJSON
        $exceptions->render(function (AuthenticationException $e, $request) use ($isApiLikeRequest, $attachCorsHeaders) {
            if (! $isApiLikeRequest($request)) {
                return null;
            }

            $response = response()->json([
                'success' => false,
                'message' => '認証が必要です',
            ], 401);

            return $attachCorsHeaders($response, $request);
        });
    })->create();
