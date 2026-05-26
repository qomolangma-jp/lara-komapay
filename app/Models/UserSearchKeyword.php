<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSearchKeyword extends Model
{
    protected $table = 'user_search_keywords';

    protected $fillable = [
        'user_id',
        'keyword',
        'search_type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
