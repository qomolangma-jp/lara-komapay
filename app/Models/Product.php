<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'stock',
        'category',
        'seller_id',
        'description',
        'image_url',
    ];

    /**
     * 商品の販売者（ユーザー）を取得
     */
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * 商品の注文詳細を取得
     */
    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    /**
     * 在庫があるかどうか
     */
    public function hasStock($quantity = 1)
    {
        return $this->stock >= $quantity;
    }

    /**
     * 在庫を減らす
     */
    public function decrementStock($quantity)
    {
        return $this->decrement('stock', $quantity);
    }

    /**
     * 在庫を増やす
     */
    public function incrementStock($quantity)
    {
        return $this->increment('stock', $quantity);
    }
}
