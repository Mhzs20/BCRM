<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_active',
        'enable_signup_reward',
        'enable_purchase_reward',
        'referral_reward_amount',
        'order_reward_percentage', 
        'max_order_reward',
        'max_referrals_per_month',
        'min_purchase_amount',
        'send_sms_notifications',
        'welcome_bonus_message',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'enable_signup_reward' => 'boolean',
        'enable_purchase_reward' => 'boolean',
        'referral_reward_amount' => 'decimal:0',
        'order_reward_percentage' => 'decimal:2',
        'max_order_reward' => 'decimal:0',
        'max_referrals_per_month' => 'integer',
        'min_purchase_amount' => 'decimal:0',
        'send_sms_notifications' => 'boolean',
    ];

    /**
     * Get signup reward (alias for referral_reward_amount)
     */
    public function getSignupRewardAttribute()
    {
        return $this->referral_reward_amount;
    }

    /**
     * Get purchase percentage (alias for order_reward_percentage)
     */
    public function getPurchasePercentageAttribute()
    {
        return $this->order_reward_percentage;
    }

    /**
     * Get purchase max reward (alias for max_order_reward)
     */
    public function getPurchaseMaxRewardAttribute()
    {
        return $this->max_order_reward;
    }

    /**
     * Get the current active referral settings
     */
    public static function getActiveSettings()
    {
        return self::first() ?? new self([
            'is_active' => false,
            'enable_signup_reward' => true,
            'enable_purchase_reward' => false,
            'referral_reward_amount' => 10000,
            'order_reward_percentage' => 5.0,
            'max_order_reward' => 50000,
            'max_referrals_per_month' => 0,
            'min_purchase_amount' => 0,
            'send_sms_notifications' => false,
        ]);
    }

    /**
     * Check if signup reward is enabled
     */
    public function isSignupRewardEnabled()
    {
        return $this->is_active && $this->enable_signup_reward;
    }

    /**
     * Check if purchase reward is enabled
     */
    public function isPurchaseRewardEnabled()
    {
        return $this->is_active && $this->enable_purchase_reward;
    }

    /**
     * Check if any reward type is enabled
     */
    public function hasAnyRewardEnabled()
    {
        return $this->is_active && ($this->enable_signup_reward || $this->enable_purchase_reward);
    }
}