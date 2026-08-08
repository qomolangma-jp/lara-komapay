<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PointApproverMiddleware
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

        if (! in_array((string) $user->status, ['master', 'teacher'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'ポイント承認権限が必要です',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
