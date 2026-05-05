<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordResetCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'user_id',
        'line_user_id',
        'code_hash',
        'expires_at',
        'sent_at',
        'used_at',
        'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
        'used_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}