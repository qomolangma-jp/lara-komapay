<?php

/**
 * 権限チェック用ヘルパー関数
 */

use App\Enums\UserRole;

if (!function_exists('auth_user')) {
    /**
     * 現在認証されているユーザーを取得
     */
    function auth_user()
    {
        return auth('sanctum')->user();
    }
}

if (!function_exists('is_master_admin')) {
    /**
     * 現在のユーザーがマスター管理者かを判定
     */
    function is_master_admin(): bool
    {
        $user = auth_user();
        return $user && $user->isMasterAdmin();
    }
}

if (!function_exists('is_admin')) {
    /**
     * 現在のユーザーが管理者かを判定
     */
    function is_admin(): bool
    {
        $user = auth_user();
        return $user && ($user->isMasterAdmin() || $user->isGeneralAdmin());
    }
}

if (!function_exists('is_seller')) {
    /**
     * 現在のユーザーが販売者かを判定
     */
    function is_seller(): bool
    {
        $user = auth_user();
        return $user && $user->isSeller();
    }
}

if (!function_exists('is_seller_or_higher')) {
    /**
     * 現在のユーザーが販売者以上の権限かを判定
     */
    function is_seller_or_higher(): bool
    {
        $user = auth_user();
        return $user && $user->isSellerOrHigher();
    }
}

if (!function_exists('user_role')) {
    /**
     * 現在のユーザーのロール情報を取得
     */
    function user_role(): ?UserRole
    {
        return auth_user()?->role;
    }
}

if (!function_exists('user_role_label')) {
    /**
     * 現在のユーザーのロール名を日本語で取得
     */
    function user_role_label(): string
    {
        return auth_user()?->getRoleLabel() ?? UserRole::USER->getLabel();
    }
}
