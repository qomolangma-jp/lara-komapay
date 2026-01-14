<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_price',
        'status',
    ];

    const STATUS_COOKING = '調理中';
    const STATUS_COMPLETED = '完了';
    const STATUS_PICKED_UP = '受渡済';

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 注文のユーザーを取得
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 注文の詳細を取得
     */
    public function details()
    {
        return $this->hasMany(OrderDetail::class);
    }

    /**
     * 注文のアイテム一覧（テキスト形式）
     */
    public function getItemsAttribute()
    {
        return $this->details()
            ->with('product')
            ->get()
            ->map(fn($detail) => "{$detail->product->name} × {$detail->quantity}")
            ->implode(', ');
    }

    /**
     * 調理中かどうか
     */
    public function isCooking()
    {
        return $this->status === self::STATUS_COOKING;
    }

    /**
     * 完了済みかどうか
     */
    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
