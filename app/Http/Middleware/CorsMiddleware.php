<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $this->getAllowedOrigin($request);
        $allowedMethods = implode(', ', config('cors.allowed_methods', ['*']));
        $allowedHeaders = implode(', ', config('cors.allowed_headers', ['*']));
        $supportsCredentials = (bool) config('cors.supports_credentials', false);
        
        // プリフライトリクエストの処理
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 200)
                ->header('Access-Control-Allow-Methods', $allowedMethods)
                ->header('Access-Control-Allow-Headers', $allowedHeaders)
                ->header('Access-Control-Max-Age', '86400')
                ->header('Vary', 'Origin');

            if ($origin !== '') {
                $response->header('Access-Control-Allow-Origin', $origin);
            }
            if ($supportsCredentials) {
                $response->header('Access-Control-Allow-Credentials', 'true');
            }

            return $response;
        }

        $response = $next($request);

        // レスポンスにCORSヘッダーを追加
        if ($origin !== '') {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        }
        $response->headers->set('Access-Control-Allow-Methods', $allowedMethods);
        $response->headers->set('Access-Control-Allow-Headers', $allowedHeaders);
        $response->headers->set('Vary', 'Origin');
        if ($supportsCredentials) {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }

    /**
     * 許可されたオリジンを取得
     */
    private function getAllowedOrigin(Request $request): string
    {
        $origin = (string) $request->header('Origin', '');
        if ($origin === '') {
            return '';
        }

        $allowedOrigins = (array) config('cors.allowed_origins', []);
        $allowedPatterns = (array) config('cors.allowed_origins_patterns', []);

        // 完全一致
        if (in_array($origin, $allowedOrigins)) {
            return $origin;
        }

        foreach ($allowedPatterns as $pattern) {
            if (@preg_match($pattern, $origin)) {
                return $origin;
            }
        }

        // 許可されていない場合はヘッダーを付与しない
        return '';
    }
}
