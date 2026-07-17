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
        'start_day_offset',
        'start_time',
        'end_day_offset',
        'end_time',
        'is_closed',
        'note',
    ];

    protected $casts = [
        'target_date' => 'string',  // 日付型キャストを削除してタイムゾーン問題を回避
        'start_day_offset' => 'integer',
        'end_day_offset' => 'integer',
        'is_closed' => 'boolean',
    ];

    public function startAt(): ?Carbon
    {
        if (!$this->start_time) {
            return null;
        }

        $baseDate = Carbon::parse((string) $this->target_date)->startOfDay();
        return $baseDate
            ->copy()
            ->addDays((int) ($this->start_day_offset ?? 0))
            ->setTimeFromTimeString((string) $this->start_time);
    }

    public function endAt(): ?Carbon
    {
        if (!$this->end_time) {
            return null;
        }

        $baseDate = Carbon::parse((string) $this->target_date)->startOfDay();
        return $baseDate
            ->copy()
            ->addDays((int) ($this->end_day_offset ?? 0))
            ->setTimeFromTimeString((string) $this->end_time);
    }

    public function allowsAt(Carbon $at): bool
    {
        if ($this->is_closed) {
            return false;
        }

        if (!$this->start_time || !$this->end_time) {
            return true;
        }

        $startAt = $this->startAt();
        $endAt = $this->endAt();

        if (!$startAt || !$endAt) {
            return true;
        }

        return $at->betweenIncluded($startAt, $endAt);
    }
}
