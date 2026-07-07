<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MasterAdminMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * マスター管理者のみアクセス可能なエンドポイントを保護
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

        if (! $user->isMasterAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'マスター管理者権限が必要です',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
