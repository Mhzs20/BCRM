<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsPackage extends Model
{
    protected $fillable = [
        'name',
        'sms_count',
        'price',
        'discount_price',
        'is_active',
    ];

    /**
     * Get the SMS transactions for the SMS package.
     */
    public function smsTransactions(): HasMany
    {
        return $this->hasMany(SmsTransaction::class);
    }

    protected $casts = [
        'price' => 'integer',
        'discount_price' => 'integer',
        'is_active' => 'boolean',
    ];
}
