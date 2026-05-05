<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'action',
        'target_type',
        'target_id',
        'actor_user_id',
        'actor_name',
        'http_method',
        'endpoint',
        'ip_address',
        'user_agent',
        'before_data',
        'after_data',
        'meta',
    ];

    protected $casts = [
        'target_id' => 'integer',
        'actor_user_id' => 'integer',
        'before_data' => 'array',
        'after_data' => 'array',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function actorUser()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
