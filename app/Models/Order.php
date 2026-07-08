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
        'payment_method',
        'payment_status',
        'paypay_payment_id',
        'paypay_redirect_url',
        'paid_at',
        'scheduled_at',
    ];

    const PAYMENT_METHOD_CASH = 'cash';
    const PAYMENT_METHOD_PAYPAY = 'paypay';

    const PAYMENT_STATUS_PENDING = 'pending';
    const PAYMENT_STATUS_PAID = 'paid';

    const STATUS_UNCONFIRMED = '未確認';
    const STATUS_CONFIRMED = '確認済';
    const STATUS_COOKING = '調理中';
    const STATUS_PREPARED = '調理済';
    const STATUS_PICKED_UP = '受取済';
    const STATUS_STOPPED = '停止';
    const STATUS_PAYMENT_PENDING = '決済待ち';
    const STATUS_RESERVED = '予約時間';
    const STATUS_POSTPAY = '後払い購入';

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'paid_at' => 'datetime',
        'scheduled_at' => 'datetime',
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
     * 決済待ちかどうか
     */
    public function isPaymentPending()
    {
        return $this->status === self::STATUS_PAYMENT_PENDING;
    }

    /**
     * 完了済みかどうか
     */
    public function isCompleted()
    {
        // 後方互換のため isCompleted は "調理済" を指すようにする
        return $this->status === self::STATUS_PREPARED;
    }

    /**
     * 受取済みかどうか
     */
    public function isPickedUp()
    {
        return $this->status === self::STATUS_PICKED_UP;
    }

    public function isPayPay()
    {
        return $this->payment_method === self::PAYMENT_METHOD_PAYPAY;
    }

    public function isPaymentCompleted()
    {
        return $this->payment_status === self::PAYMENT_STATUS_PAID;
    }

    public function isPostPay()
    {
        return $this->status === self::STATUS_POSTPAY;
    }
}
