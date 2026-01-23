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
        // プリフライトリクエストの処理
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', $this->getAllowedOrigin($request))
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Max-Age', '86400');
        }

        $response = $next($request);

        // レスポンスにCORSヘッダーを追加
        return $response
            ->header('Access-Control-Allow-Origin', $this->getAllowedOrigin($request))
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin')
            ->header('Access-Control-Allow-Credentials', 'true');
    }

    /**
     * 許可されたオリジンを取得
     */
    private function getAllowedOrigin(Request $request): string
    {
        $origin = $request->header('Origin');
        
        $allowedOrigins = [
            'http://localhost:5173',
            'http://localhost:3000',
            'https://komapay.p-kmt.com',
            'https://pken-purchase-system.vercel.app',
        ];

        // 完全一致
        if (in_array($origin, $allowedOrigins)) {
            return $origin;
        }

        // Vercelドメインのパターンマッチ
        if (preg_match('/^https:\/\/.*\.vercel\.app$/', $origin)) {
            return $origin;
        }

        // デフォルト
        return $allowedOrigins[0];
    }
}
