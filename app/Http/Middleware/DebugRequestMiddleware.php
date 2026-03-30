<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DebugRequestMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // リクエスト到達ログ
        Log::debug('=== API Request Received ===', [
            'method' => $request->getMethod(),
            'path' => $request->path(),
            'full_path' => $request->fullPath(),
            'uri' => $request->getRequestUri(),
            'url' => $request->url(),
            'matching_routes' => $request->route()?->getActionName() ?? 'NO_ROUTE_MATCHED',
            'origin' => $request->header('Origin'),
            'user_agent' => $request->header('User-Agent'),
            'timestamp' => now()->toIso8601String(),
        ]);

        $response = $next($request);

        // レスポンス状態ログ
        Log::debug('=== API Response Sent ===', [
            'path' => $request->path(),
            'status_code' => $response->getStatusCode(),
            'content_type' => $response->headers->get('Content-Type'),
        ]);

        return $response;
    }
}
