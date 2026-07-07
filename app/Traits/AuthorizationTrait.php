<?php

namespace App\Traits;

use App\Enums\UserRole;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * 権限チェック用トレイト
 * 
 * コントローラー内で権限チェックを行う時に使用
 */
trait AuthorizationTrait
{
    /**
     * ユーザーがマスター管理者かをチェック
     */
    protected function requireMasterAdmin(): void
    {
        $user = auth('sanctum')->user();
        
        if (!$user || !$user->isMasterAdmin()) {
            abort(response()->json([
                'success' => false,
                'message' => 'マスター管理者権限が必要です',
            ], Response::HTTP_FORBIDDEN));
        }
    }

    /**
     * ユーザーが管理者かをチェック
     */
    protected function requireAdmin(): void
    {
        $user = auth('sanctum')->user();
        
        if (!$user || !($user->isMasterAdmin() || $user->isGeneralAdmin())) {
            abort(response()->json([
                'success' => false,
                'message' => '管理者権限が必要です',
            ], Response::HTTP_FORBIDDEN));
        }
    }

    /**
     * ユーザーが販売者以上かをチェック
     */
    protected function requireSeller(): void
    {
        $user = auth('sanctum')->user();
        
        if (!$user || !($user->isMasterAdmin() || $user->isGeneralAdmin() || $user->isSeller())) {
            abort(response()->json([
                'success' => false,
                'message' => '販売者以上の権限が必要です',
            ], Response::HTTP_FORBIDDEN));
        }
    }

    /**
     * ユーザーが認証されているかをチェック
     */
    protected function requireAuth(): void
    {
        $user = auth('sanctum')->user();
        
        if (!$user) {
            abort(response()->json([
                'success' => false,
                'message' => '認証が必要です',
            ], Response::HTTP_UNAUTHORIZED));
        }
    }

    /**
     * 指定されたロールを持っているかをチェック
     */
    protected function requireRole(UserRole $role): void
    {
        $user = auth('sanctum')->user();
        
        if (!$user || $user->role !== $role) {
            abort(response()->json([
                'success' => false,
                'message' => $role->getLabel() . 'のみがアクセス可能です',
            ], Response::HTTP_FORBIDDEN));
        }
    }

    /**
     * 複数のロールのいずれかを持っているかをチェック
     */
    protected function requireRoles(UserRole ...$roles): void
    {
        $user = auth('sanctum')->user();
        
        if (!$user || !in_array($user->role, $roles, true)) {
            $roleLabels = array_map(fn ($role) => $role->getLabel(), $roles);
            abort(response()->json([
                'success' => false,
                'message' => implode('または', $roleLabels) . 'のみがアクセス可能です',
            ], Response::HTTP_FORBIDDEN));
        }
    }

    /**
     * 権限チェック結果を返す
     */
    protected function checkAuthorization(bool $isAuthorized, string $message = '権限がありません'): bool
    {
        if (!$isAuthorized) {
            abort(response()->json([
                'success' => false,
                'message' => $message,
            ], Response::HTTP_FORBIDDEN));
        }
        return true;
    }
}
