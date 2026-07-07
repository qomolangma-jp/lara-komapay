<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use App\Enums\UserRole;

class User extends Model
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'username',
        'student_id',
        'status',
        'name_2nd',
        'name_1st',
        'shop_name',
        'line_id',
        'line_user_id',
        'password',
        'is_admin',
        'role',
        'email_verified_at',
        'email_verification_token',
    ];

    protected $hidden = [
        'password',
    ];

    protected $appends = [
        'display_name',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'role' => UserRole::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    public function isEmailVerified()
    {
        return ! empty($this->email_verified_at);
    }

    /**
     * 管理者かどうか（後方互換性のため）
     */
    public function isAdmin()
    {
        return $this->isMasterAdmin() || $this->isGeneralAdmin() || $this->is_admin == 1 || $this->status === 'admin';
    }

    /**
     * マスター管理者かどうか
     */
    public function isMasterAdmin(): bool
    {
        return $this->role === UserRole::MASTER_ADMIN;
    }

    /**
     * 一般管理者かどうか
     */
    public function isGeneralAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * 管理者レベルのロールを持つか（マスター管理者と一般管理者）
     */
    public function isAdministrator(): bool
    {
        return $this->role?->isAdministrator() ?? false;
    }

    /**
     * 販売者かどうか
     */
    public function isSeller(): bool
    {
        return $this->role === UserRole::SELLER || $this->status === 'seller';
    }

    /**
     * 販売者以上の権限を持つか
     */
    public function isSellerOrHigher(): bool
    {
        return $this->role?->isSellerOrHigher() ?? false;
    }

    /**
     * 通常ユーザーかどうか
     */
    public function isRegularUser(): bool
    {
        return $this->role === UserRole::USER;
    }

    /**
     * ロール文字列を取得
     */
    public function getRoleString(): string
    {
        return $this->role?->value ?? UserRole::USER->value;
    }

    /**
     * ロールラベルを日本語で取得
     */
    public function getRoleLabel(): string
    {
        return $this->role?->getLabel() ?? UserRole::USER->getLabel();
    }

    /**
     * フルネームを取得
     */
    public function getFullNameAttribute()
    {
        return $this->name_2nd . ' ' . $this->name_1st;
    }

    /**
     * 表示名を取得（店舗名がある場合は店舗名、なければフルネーム）
     */
    public function getDisplayNameAttribute()
    {
        return $this->shop_name ?: $this->getFullNameAttribute();
    }

    /**
     * ユーザーの注文を取得
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
