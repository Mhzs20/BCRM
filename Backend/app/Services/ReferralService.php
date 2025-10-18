<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserReferral;
use App\Models\ReferralSetting;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReferralService
{
    /**
     * Process referral during user registration
     */
    public function processRegistrationReferral(User $user, $referralCode = null)
    {
        if (!$referralCode) {
            return null;
        }

        $settings = ReferralSetting::getActiveSettings();
        if (!$settings->is_active) {
            return null;
        }

        // Find referrer by referral code
        $referrer = User::where('referral_code', $referralCode)->first();
        if (!$referrer) {
            throw new \Exception('کد دعوت معتبر نیست.');
        }

        // Check if user is trying to refer themselves
        if ($referrer->mobile === $user->mobile) {
            throw new \Exception('امکان استفاده از کد دعوت خود وجود ندارد.');
        }

        // Check if referrer has reached monthly limit
        if (!$referrer->canReferMore()) {
            throw new \Exception('دعوت‌کننده به حد مجاز دعوت در ماه رسیده است.');
        }

        // Check if this user was already referred
        $existingReferral = UserReferral::where('referred_id', $user->id)->first();
        if ($existingReferral) {
            return $existingReferral;
        }

        try {
            DB::beginTransaction();

            // Set referrer for the new user
            $user->referrer_id = $referrer->id;
            $user->save();

            // Create referral record
            $referral = UserReferral::create([
                'referrer_id' => $referrer->id,
                'referred_id' => $user->id,
                'status' => UserReferral::STATUS_PENDING,
            ]);

            DB::commit();
            Log::info("ReferralService: Created referral record - Referrer: {$referrer->id}, Referred: {$user->id}");

            return $referral;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("ReferralService: Failed to process referral - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process referral when user completes profile (becomes registered)
     */
    public function processRegistrationCompletion(User $user)
    {
        // Find referral record where this user was referred
        $referral = UserReferral::where('referred_id', $user->id)
            ->where('status', UserReferral::STATUS_PENDING)
            ->first();

        if (!$referral) {
            return null;
        }

        try {
            DB::beginTransaction();

            // Update referral status and process rewards
            $referral->updateStatus(UserReferral::STATUS_REGISTERED);

            DB::commit();
            Log::info("ReferralService: Processed registration completion for referral: {$referral->id}");

            return $referral;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("ReferralService: Failed to process registration completion - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate referral code
     */
    public function validateReferralCode($code, $userMobile = null)
    {
        if (!$code) {
            return ['valid' => true]; // No code is valid
        }

        $settings = ReferralSetting::getActiveSettings();
        if (!$settings->is_active) {
            return [
                'valid' => false,
                'message' => 'سیستم رفرال غیرفعال است.'
            ];
        }

        $referrer = User::where('referral_code', $code)->first();
        if (!$referrer) {
            return [
                'valid' => false,
                'message' => 'کد دعوت معتبر نیست.'
            ];
        }

        // Check if user is trying to refer themselves
        if ($userMobile && $referrer->mobile === $userMobile) {
            return [
                'valid' => false,
                'message' => 'امکان استفاده از کد دعوت خود وجود ندارد.'
            ];
        }

        // Check if referrer has reached monthly limit
        if (!$referrer->canReferMore()) {
            return [
                'valid' => false,
                'message' => 'دعوت‌کننده به حد مجاز دعوت در ماه رسیده است.'
            ];
        }

        return [
            'valid' => true,
            'referrer' => $referrer,
            'message' => 'کد دعوت معتبر است.'
        ];
    }

    /**
     * Generate unique referral code for user
     */
    public function generateUniqueReferralCode(User $user)
    {
        return $user->generateReferralCode();
    }

    /**
     * Get referral statistics for admin
     */
    public function getAdminStats($dateFrom = null, $dateTo = null)
    {
        $query = UserReferral::query();

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }

        $totalReferrals = $query->count();
        $successfulReferrals = $query->whereIn('status', [
            UserReferral::STATUS_REGISTERED,
            UserReferral::STATUS_PURCHASED,
            UserReferral::STATUS_REWARDED
        ])->count();

        $totalRewards = WalletTransaction::whereIn('type', [
            WalletTransaction::TYPE_REFERRAL_SIGNUP,
            WalletTransaction::TYPE_REFERRAL_PURCHASE
        ])
        ->when($dateFrom, function($q) use ($dateFrom) {
            return $q->where('created_at', '>=', $dateFrom);
        })
        ->when($dateTo, function($q) use ($dateTo) {
            return $q->where('created_at', '<=', $dateTo);
        })
        ->sum('amount');

        $topReferrers = User::withCount(['referrals as successful_referrals_count' => function($q) use ($dateFrom, $dateTo) {
            $q->whereIn('status', [
                UserReferral::STATUS_REGISTERED,
                UserReferral::STATUS_PURCHASED,
                UserReferral::STATUS_REWARDED
            ]);
            if ($dateFrom) $q->where('created_at', '>=', $dateFrom);
            if ($dateTo) $q->where('created_at', '<=', $dateTo);
        }])
        ->having('successful_referrals_count', '>', 0)
        ->orderBy('successful_referrals_count', 'desc')
        ->limit(10)
        ->get();

        return [
            'total_referrals' => $totalReferrals,
            'successful_referrals' => $successfulReferrals,
            'success_rate' => $totalReferrals > 0 ? round(($successfulReferrals / $totalReferrals) * 100, 2) : 0,
            'total_rewards_paid' => $totalRewards,
            'top_referrers' => $topReferrers,
        ];
    }
}