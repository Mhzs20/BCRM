<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'salon_id',
        'sms_package_id',
        'package_id',
        'type',
        'order_type',
        'item_id',
        'item_title',
        'amount',
        'original_amount',
        'discount_amount',
        'sms_count',
        'status',
        'payment_status',
        'payment_method',
        'discount_code',
        'discount_percentage',
        'payment_authority',
        'payment_ref_id',
        'transaction_id',
        'reference_id',
        'paid_at',
        'metadata',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:0',
        'original_amount' => 'decimal:0',
        'discount_amount' => 'decimal:0',
        'sms_count' => 'integer',
        'discount_percentage' => 'integer',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the salon that the order belongs to.
     */
    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    /**
     * Get the SMS package associated with the order.
     */
    public function smsPackage()
    {
        return $this->belongsTo(SmsPackage::class);
    }

    /**
     * Get the feature package associated with the order.
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Get the transactions for the order.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the discount code associated with the order.
     */
    public function discountCodeModel()
    {
        return $this->belongsTo(DiscountCode::class, 'discount_code', 'code');
    }
}
