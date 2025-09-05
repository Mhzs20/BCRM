<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountCode extends Model
{
    use HasFactory;
    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'starts_at',
        'expires_at',
        'is_active',
        'target_users',
        'user_filter_type',
        'description',
        'usage_limit',
        'usage_count',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'target_users' => 'array',
        'usage_count' => 'integer',
        'usage_limit' => 'integer',
    ];

    /**
     * Check if the discount code is currently valid
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check if discount has started
        if ($this->starts_at && now()->lessThan($this->starts_at)) {
            return false;
        }

        // Check if discount has expired
        if ($this->expires_at && now()->greaterThan($this->expires_at)) {
            return false;
        }

        // Check usage limit - now based on unique salons count
        if ($this->usage_limit && $this->salonUsages()->count() >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Check if a user can use this discount code based on filter criteria
     */
    public function canUserUse($user): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        // SECURITY: Check if salon has already used this discount code
        if ($user->active_salon_id && $this->hasBeenUsedBySalon($user->active_salon_id)) {
            return false;
        }

        // If no filter is set, all users can use it
        if ($this->user_filter_type === 'all' || !$this->target_users) {
            return true;
        }

        // Check filtered criteria
        if ($this->user_filter_type === 'filtered' && $this->target_users) {
            return $this->userMatchesFilters($user, $this->target_users);
        }

        return true;
    }

    /**
     * Check if a salon can use this discount code based on filter criteria
     */
    public function canSalonUse($salon): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        // SECURITY: Check if salon has already used this discount code
        if ($this->hasBeenUsedBySalon($salon->id)) {
            return false;
        }

        // If no filter is set, all salons can use it
        if ($this->user_filter_type === 'all' || !$this->target_users) {
            return true;
        }

        // Check filtered criteria
        if ($this->user_filter_type === 'filtered' && $this->target_users) {
            return $this->salonMatchesFilters($salon, $this->target_users);
        }

        return true;
    }

    /**
     * Check if user matches the filter criteria
     */
    private function userMatchesFilters($user, array $filters): bool
    {
        // Get user's salon
        $salon = $user->salon ?? null;
        
        if (!$salon) {
            return false;
        }

        return $this->salonMatchesFilters($salon, $filters);
    }

    /**
     * Check if salon matches the filter criteria
     */
    private function salonMatchesFilters($salon, array $filters): bool
    {
        // Check province filter
        if (isset($filters['province_id']) && $filters['province_id']) {
            $salonProvinceId = $salon->city ? $salon->city->province_id : null;
            
            if (!$salon->city || $salon->city->province_id != (int)$filters['province_id']) {
                return false;
            }
        }

        // Check city filter
        if (isset($filters['city_id']) && $filters['city_id']) {
            if ($salon->city_id != (int)$filters['city_id']) {
                return false;
            }
        }

        // Check business category filter
        if (isset($filters['business_category_id']) && $filters['business_category_id']) {
            if ($salon->business_category_id != (int)$filters['business_category_id']) {
                return false;
            }
        }

        // Check status filter (active)
        if (isset($filters['status']) && $filters['status'] === 'active') {
            if (!$salon->is_active) {
                return false;
            }
        }

        // Check SMS balance filter
        if (isset($filters['sms_balance']) && is_numeric($filters['sms_balance'])) {
            $requiredBalance = (int)$filters['sms_balance'];
            $currentBalance = $salon->sms_balance ?? 0;
            
            if ($currentBalance < $requiredBalance) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get total usage count for this discount code
     */
    public function getTotalUsageCount(): int
    {
        return $this->salonUsages()->count();
    }

    /**
     * Calculate discount amount for a given price
     */
    public function calculateDiscount(float $amount): float
    {
        if ($this->type === 'percentage') {
            $discount = ($amount * $this->value) / 100;
            
            // Apply max discount limit if set
            if ($this->max_discount_amount && $discount > $this->max_discount_amount) {
                $discount = $this->max_discount_amount;
            }
            
            return $discount;
        } else {
            // Fixed amount discount
            return min($this->value, $amount);
        }
    }

    /**
     * Get orders that used this discount code
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'discount_code', 'code');
    }

    /**
     * Get salon usage records for this discount code
     */
    public function salonUsages()
    {
        return $this->hasMany(DiscountCodeSalonUsage::class);
    }

    /**
     * Check if this discount code has been used by a specific salon
     */
    public function hasBeenUsedBySalon(int $salonId): bool
    {
        return $this->salonUsages()->where('salon_id', $salonId)->exists();
    }

    /**
     * Record usage by a salon
     */
    public function recordSalonUsage(int $salonId, int $orderId = null): void
    {
        // Only record if not already used by this salon
        if (!$this->hasBeenUsedBySalon($salonId)) {
            $this->salonUsages()->create([
                'salon_id' => $salonId,
                'order_id' => $orderId,
                'used_at' => now()
            ]);
        }
    }
}
