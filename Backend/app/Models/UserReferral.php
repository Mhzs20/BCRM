<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\ReferralSmsService;

class UserReferral extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'referrer_id',
        'referred_id',
        'status',
        'signup_reward_amount',
        'purchase_reward_amount',
        'total_reward_amount',
        'registered_at',
        'first_purchase_at',
        'last_purchase_at',
    ];

    protected $casts = [
        'signup_reward_amount' => 'decimal:0',
        'purchase_reward_amount' => 'decimal:0',
        'total_reward_amount' => 'decimal:0',
        'registered_at' => 'datetime',
        'first_purchase_at' => 'datetime',
        'last_purchase_at' => 'datetime',
    ];

    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_REGISTERED = 'registered';
    const STATUS_PURCHASED = 'purchased';
    const STATUS_REWARDED = 'rewarded';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the user who made the referral (referrer)
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * Get the user who was referred
     */
    public function referred()
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    /**
     * Get the wallet transactions related to this referral
     */
    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class, 'referral_id');
    }

    /**
     * Check if referral is eligible for signup reward
     */
    public function canReceiveSignupReward()
    {
        return $this->status === self::STATUS_REGISTERED && 
               $this->signup_reward_amount == 0;
    }

    /**
     * Check if referral is eligible for purchase reward
     */
    public function canReceivePurchaseReward()
    {
        return in_array($this->status, [self::STATUS_REGISTERED, self::STATUS_PURCHASED, self::STATUS_REWARDED]);
    }

    /**
     * Get display name for status
     */
    public function getStatusDisplayAttribute()
    {
        return match($this->status) {
            'pending' => 'در انتظار',
            'completed' => 'تکمیل شده',
            'cancelled' => 'لغو شده',
            default => $this->status
        };
    }
    public function updateStatus($newStatus, $purchaseAmount = 0)
    {
        $settings = ReferralSetting::getActiveSettings();
        
        if (!$settings->is_active) {
            return false;
        }

        $this->status = $newStatus;
        
        // Handle signup reward
        if ($newStatus === self::STATUS_REGISTERED && $this->canReceiveSignupReward()) {
            $this->signup_reward_amount = $settings->signup_reward;
            $this->registered_at = now();
            
            // Create wallet transaction for signup reward
            if ($settings->signup_reward > 0) {
                WalletTransaction::createAndUpdateBalance([
                    'user_id' => $this->referrer_id,
                    'type' => WalletTransaction::TYPE_REFERRAL_SIGNUP,
                    'amount' => $settings->signup_reward,
                    'status' => WalletTransaction::STATUS_COMPLETED,
                    'description' => "پاداش دعوت کاربر جدید: {$this->referred->mobile}",
                    'referral_id' => $this->id,
                ]);
                
                // Send SMS notification
                ReferralSmsService::sendRewardNotification(
                    $this->referrer, 
                    'signup_reward', 
                    $settings->signup_reward,
                    $this->referred
                );
            }
        }
        
        // Handle purchase reward
        if ($newStatus === self::STATUS_PURCHASED && $purchaseAmount > 0) {
            $purchaseReward = ($purchaseAmount * $settings->purchase_percentage) / 100;
            
            // Apply max reward limit if set
            if ($settings->max_purchase_reward_amount > 0) {
                $purchaseReward = min($purchaseReward, $settings->max_purchase_reward_amount);
            }
            
            $this->purchase_reward_amount += $purchaseReward;
            
            if (!$this->first_purchase_at) {
                $this->first_purchase_at = now();
            }
            $this->last_purchase_at = now();
            
            // Create wallet transaction for purchase reward
            if ($purchaseReward > 0) {
                WalletTransaction::create([
                    'user_id' => $this->referrer_id,
                    'type' => WalletTransaction::TYPE_REFERRAL_PURCHASE,
                    'amount' => $purchaseReward,
                    'status' => WalletTransaction::STATUS_COMPLETED,
                    'description' => "پاداش خرید معرفی‌شده: {$this->referred->mobile} - مبلغ خرید: " . number_format($purchaseAmount) . " تومان",
                    'referral_id' => $this->id,
                ]);
                
                // Send SMS notification
                ReferralSmsService::sendRewardNotification(
                    $this->referrer, 
                    'purchase_reward', 
                    $purchaseReward,
                    $this->referred
                );
            }
        }
        
        // Update total reward amount
        $this->total_reward_amount = $this->signup_reward_amount + $this->purchase_reward_amount;
        
        return $this->save();
    }

    /**
     * Scope to get successful referrals for a user in current month
     */
    public function scopeThisMonthSuccessful($query, $userId)
    {
        return $query->where('referrer_id', $userId)
                    ->whereIn('status', [self::STATUS_REGISTERED, self::STATUS_PURCHASED, self::STATUS_REWARDED])
                    ->whereBetween('created_at', [
                        now()->startOfMonth(),
                        now()->endOfMonth()
                    ]);
    }
}