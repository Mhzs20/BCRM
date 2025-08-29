<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'salon_id',
        'sms_package_id',
        'amount',
        'sms_count',
        'status',
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
     * Get the transactions for the order.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
