<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

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
        'password',
        'is_admin',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 管理者かどうか
     */
    public function isAdmin()
    {
        return $this->is_admin == 1 || $this->status === 'admin';
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
