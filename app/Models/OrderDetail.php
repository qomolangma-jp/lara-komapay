<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
    ];

    /**
     * 注文を取得
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * 商品を取得
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 小計を計算
     */
    public function getSubtotalAttribute()
    {
        return $this->product->price * $this->quantity;
    }
}
