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

        // 管理者または販売者フラグがtrueの場合は許可
        // （is_adminがtrueなら管理者、将来的にis_sellerフラグを追加することも可能）
        if (!$user->isAdmin()) {
            // 現在は管理者のみ許可（後でis_sellerフラグを追加可能）
            return response()->json([
                'success' => false,
                'message' => '販売者権限が必要です',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
