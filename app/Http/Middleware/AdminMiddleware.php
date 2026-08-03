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
        $user = auth('sanctum')->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => '認証が必要です',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (! $user->canAccessMaster()) {
            return response()->json([
                'success' => false,
                'message' => 'マスター管理画面へのアクセス権限が必要です',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
