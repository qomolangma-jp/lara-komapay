<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SellerMiddleware
{
    /**
     * Handle an incoming request.
     * 管理者または販売者のみアクセス可能
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('sanctum')->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => '認証が必要です',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // 管理者、またはstatusが'seller'のユーザーのみアクセス可能
        if (!$user->isAdmin() && $user->status !== 'seller') {
            return response()->json([
                'success' => false,
                'message' => '販売者権限が必要です',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
