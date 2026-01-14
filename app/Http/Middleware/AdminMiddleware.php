<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth('sanctum')->check() || !auth('sanctum')->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => '管理者権限が必要です',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
