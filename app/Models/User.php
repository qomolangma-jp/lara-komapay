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
        'line_id',
    ];

    protected $hidden = [
        // 非表示項目なし
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
        return $this->status === 'admin';
    }

    /**
     * フルネームを取得
     */
    public function getFullNameAttribute()
    {
        return $this->name_2nd . ' ' . $this->name_1st;
    }

    /**
     * ユーザーの注文を取得
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * ユーザーが管理者かどうか
     */
    public function isAdmin()
    {
        return $this->is_admin == 1;
    }
}
