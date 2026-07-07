<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SellerMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * 販売者以上の権限を持つユーザー（マスター管理者、一般管理者、販売者）のみアクセス可能
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

        // 管理者（マスター管理者と一般管理者）、または販売者のみアクセス可能
        if (!($user->isMasterAdmin() || $user->isGeneralAdmin() || $user->isSeller())) {
            return response()->json([
                'success' => false,
                'message' => '販売者以上の権限が必要です',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}

