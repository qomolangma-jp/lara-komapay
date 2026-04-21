<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class OrderWindow extends Model
{
    use HasFactory;

    protected $fillable = [
        'target_date',
        'start_time',
        'end_time',
        'is_closed',
        'note',
    ];

    protected $casts = [
        'target_date' => 'date',
        'is_closed' => 'boolean',
    ];

    public function allowsAt(Carbon $at): bool
    {
        if ($this->is_closed) {
            return false;
        }

        if (!$this->start_time || !$this->end_time) {
            return true;
        }

        $current = $at->format('H:i:s');

        return $current >= $this->start_time && $current <= $this->end_time;
    }
}
