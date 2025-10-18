<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'amount',
        'price',
        'discount_percentage',
        'is_active',
        'is_featured',
        'sort_order',
        'icon',
        'color',
    ];

    protected $casts = [
        'amount' => 'decimal:0',
        'price' => 'decimal:0',
        'discount_percentage' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Scope for active packages
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for inactive packages
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope for featured packages
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Get final price after discount
     */
    public function getFinalPriceAttribute()
    {
        if ($this->discount_percentage > 0) {
            return $this->price - ($this->price * $this->discount_percentage / 100);
        }
        
        return $this->price;
    }

    /**
     * Get discount amount
     */
    public function getDiscountAmountAttribute()
    {
        if ($this->discount_percentage > 0) {
            return $this->price * $this->discount_percentage / 100;
        }
        
        return 0;
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount / 10) . ' تومان';
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price / 10) . ' تومان';
    }

    /**
     * Get formatted final price
     */
    public function getFormattedFinalPriceAttribute()
    {
        return number_format($this->final_price / 10) . ' تومان';
    }
}