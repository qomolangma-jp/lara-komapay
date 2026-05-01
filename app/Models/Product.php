<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        'additional_image_urls',
        'label',
        'allergens',
    ];

    protected $casts = [
        'additional_image_urls' => 'array',
    ];

    /**
     * 商品の販売者（ユーザー）を取得
     */
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * 販売者（vendor）エイリアス
     */
    public function vendor()
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
        // 原子的に在庫を減らす: 在庫が十分にある場合のみ減算を行い、成功したら true を返す
        $affected = DB::table($this->getTable())
            ->where('id', $this->id)
            ->where('stock', '>=', $quantity)
            ->decrement('stock', $quantity);

        return $affected > 0;
    }

    /**
     * 在庫を増やす
     */
    public function incrementStock($quantity)
    {
        return $this->increment('stock', $quantity);
    }
}
