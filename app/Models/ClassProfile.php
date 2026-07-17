<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'class_code',
        'student_number',
        'student_name',
    ];

    protected $casts = [
        'student_number' => 'integer',
    ];
}
