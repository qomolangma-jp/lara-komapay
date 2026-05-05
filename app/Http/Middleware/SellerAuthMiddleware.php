<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SellerAuthMiddleware
{
    /**
     * Handle an incoming request.
     * 販売者管理画面用の認証ミドルウェア（セッションベース）
     */
    public function handle(Request $request, Closure $next): Response
    {
        // セッションからユーザー情報を取得
        $userId = session('user_id');
        
        if (!$userId) {
            // ログインしていない場合はログインページにリダイレクト
            return redirect('/login')->with('error', 'ログインが必要です');
        }

        // ユーザー情報を取得
        $user = \App\Models\User::find($userId);
        
        if (!$user) {
            // ユーザーが見つからない場合はセッションをクリアしてログインページへ
            session()->forget('user_id');
            return redirect('/login')->with('error', 'ユーザーが見つかりません');
        }

        // 管理者、またはstatusが'seller'のユーザーのみアクセス可能
        if (! $user->isAdmin() && $user->status !== 'seller') {
            // 権限がない場合は学生画面にリダイレクト
            return redirect('/student')->with('error', '販売者権限が必要です');
        }

        // リクエストにユーザー情報を追加（ビューで使用可能に）
        $request->merge(['user' => $user]);
        view()->share('user', $user);

        return $next($request);
    }
}
