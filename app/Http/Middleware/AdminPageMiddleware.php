<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminPageMiddleware
{
    /**
     * Handle an incoming request for admin pages.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userId = session('user_id');

        if (! $userId) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ログインが必要です',
                ], Response::HTTP_UNAUTHORIZED);
            }

            return redirect('/login')->with('error', 'ログインが必要です');
        }

        $user = User::find($userId);

        if (! $user) {
            session()->forget('user_id');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ユーザーが見つかりません',
                ], Response::HTTP_UNAUTHORIZED);
            }

            return redirect('/login')->with('error', 'ユーザーが見つかりません');
        }

        if (! $user->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '管理者権限が必要です',
                ], Response::HTTP_FORBIDDEN);
            }

            return redirect('/student')->with('error', '管理者権限が必要です');
        }

        view()->share('user', $user);

        return $next($request);
    }
}
