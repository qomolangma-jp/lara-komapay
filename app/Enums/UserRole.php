<?php

namespace App\Enums;

enum UserRole: string
{
    /**
     * マスター管理者 - システム全体の管理者
     * - ユーザー管理（作成、編集、削除）
     * - システム設定
     * - すべての管理機能
     */
    case MASTER_ADMIN = 'master_admin';

    /**
     * 一般管理者 - 運営用管理者
     * - ニュース管理
     * - 注文管理
     * - 商品管理
     */
    case ADMIN = 'admin';

    /**
     * 販売者 - 店舗管理者
     * - 自分の商品管理
     * - 自分の注文確認
     */
    case SELLER = 'seller';

    /**
     * 通常ユーザー - 購買者
     * - 商品閲覧
     * - 注文・購入
     */
    case USER = 'user';

    /**
     * ロール名を日本語で取得
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::MASTER_ADMIN => 'マスター管理者',
            self::ADMIN => '一般管理者',
            self::SELLER => '販売者',
            self::USER => '通常ユーザー',
        };
    }

    /**
     * ロールの説明を取得
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::MASTER_ADMIN => 'システム全体の管理者。ユーザー管理と全システム設定が可能。',
            self::ADMIN => '運営用管理者。ニュース、注文、商品管理が可能。',
            self::SELLER => '店舗管理者。自分の商品と注文を管理可能。',
            self::USER => '通常ユーザー。商品閲覧と購入が可能。',
        };
    }

    /**
     * すべてのロール値を配列で取得
     */
    public static function getAllValues(): array
    {
        return array_map(fn (self $role) => $role->value, self::cases());
    }

    /**
     * 管理者レベルのロールかどうか（マスター管理者と一般管理者）
     */
    public function isAdministrator(): bool
    {
        return $this === self::MASTER_ADMIN || $this === self::ADMIN;
    }

    /**
     * マスター管理者かどうか
     */
    public function isMasterAdmin(): bool
    {
        return $this === self::MASTER_ADMIN;
    }

    /**
     * 販売者以上の権限を持つか
     */
    public function isSellerOrHigher(): bool
    {
        return $this !== self::USER;
    }
}
