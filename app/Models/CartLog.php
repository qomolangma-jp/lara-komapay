<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_item_id',
        'user_id',
        'product_id',
        'quantity',
        'logged_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'logged_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function cartItem()
    {
        return $this->belongsTo(CartItem::class);
    }
}
