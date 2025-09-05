<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\DiscountCode;
use App\Models\Salon;
use App\Models\Order;

class DiscountCodeSalonUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'discount_code_id',
        'salon_id', 
        'order_id',
        'used_at'
    ];

    protected $casts = [
        'used_at' => 'datetime'
    ];

    /**
     * Get the discount code that was used.
     */
    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    /**
     * Get the salon that used the discount code.
     */
    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    /**
     * Get the order associated with this usage.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}