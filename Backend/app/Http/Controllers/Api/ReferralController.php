<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReferralSetting;
use App\Models\UserReferral;
use App\Models\WalletTransaction;
use App\Models\WithdrawRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReferralController extends Controller
{
    /**
     * Get user's referral information
     */
    public function getReferralInfo(Request $request)
    {
        try {
            $user = $request->user();
            
            // Generate referral code if not exists
            if (!$user->referral_code) {
                $user->generateReferralCode();
            }
            
            $stats = $user->getReferralStats();
            $settings = ReferralSetting::getActiveSettings();
            
            // لیست افراد دعوت شده
            $invitedUsers = $user->referrals()
                ->with(['referred:id,name,mobile,created_at'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($referral) {
                    return [
                        'name' => $referral->referred->name ?? 'کاربر',
                        'mobile' => $referral->referred->mobile ?? '-',
                        'status' => $referral->status,
                        'status_display' => $this->getReferralStatusDisplay($referral->status),
                        'registered_at' => $referral->registered_at ? $referral->registered_at->format('Y-m-d H:i') : null,
                        'reward_amount' => $referral->total_reward_amount,
                    ];
                });
            
            // محاسبه تعداد باقیمانده از ظرفیت
            $dailyLimit = $settings->daily_referral_limit ?? 0;
            $monthlyLimit = $settings->monthly_referral_limit ?? 0;
            $totalLimit = $settings->total_referral_limit ?? 0;
            
            $todayCount = $user->referrals()
                ->whereDate('created_at', today())
                ->count();
            
            $thisMonthCount = $user->referrals()
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count();
            
            $totalCount = $user->referrals()->count();
            
            $remainingDaily = $dailyLimit > 0 ? max(0, $dailyLimit - $todayCount) : null;
            $remainingMonthly = $monthlyLimit > 0 ? max(0, $monthlyLimit - $thisMonthCount) : null;
            $remainingTotal = $totalLimit > 0 ? max(0, $totalLimit - $totalCount) : null;
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'referral_code' => $user->getReferralCode(),
                    'statistics' => $stats,
                    'can_refer_more' => $user->canReferMore(),
                    'monthly_limit' => $settings->monthly_referral_limit,
                    'system_active' => $settings->is_active,
                    
                    // لیست افراد دعوت شده
                    'invited_users' => $invitedUsers,
                    'total_invited' => $invitedUsers->count(),
                    
                    // ظرفیت باقیمانده
                    'capacity' => [
                        'daily_limit' => $dailyLimit,
                        'monthly_limit' => $monthlyLimit,
                        'total_limit' => $totalLimit,
                        'remaining_daily' => $remainingDaily,
                        'remaining_monthly' => $remainingMonthly,
                        'remaining_total' => $remainingTotal,
                        'today_count' => $todayCount,
                        'this_month_count' => $thisMonthCount,
                        'total_count' => $totalCount,
                    ],
                    
                    // درصد هدیه برای خرید
                    'reward_settings' => [
                        'signup_reward_amount' => $settings->referral_reward_amount ?? 0,
                        'purchase_reward_percentage' => $settings->order_reward_percentage ?? 0,
                        'max_purchase_reward' => $settings->max_order_reward ?? 0,
                        'min_purchase_amount' => $settings->min_purchase_amount ?? 0,
                    ],
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در دریافت اطلاعات رفرال: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get referral status display text
     */
    private function getReferralStatusDisplay($status)
    {
        return match($status) {
            UserReferral::STATUS_PENDING => 'در انتظار',
            UserReferral::STATUS_REGISTERED => 'ثبت‌نام شده',
            UserReferral::STATUS_PURCHASED => 'خرید انجام شده',
            UserReferral::STATUS_REWARDED => 'پاداش دریافت شده',
            UserReferral::STATUS_CANCELLED => 'لغو شده',
            default => $status
        };
    }

    /**
     * Get user's referral list
     */
    public function getReferrals(Request $request)
    {
        try {
            $user = $request->user();
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 15);
            
            $referrals = $user->referrals()
                ->with(['referred:id,name,mobile,created_at'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);
            
            return response()->json([
                'status' => 'success',
                'data' => $referrals
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در دریافت لیست معرفی‌ها: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get referral settings for display
     */
    public function getSettings()
    {
        try {
            $settings = ReferralSetting::getActiveSettings();
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'is_active' => $settings->is_active,
                    'enable_signup_reward' => $settings->enable_signup_reward,
                    'enable_purchase_reward' => $settings->enable_purchase_reward,
                    'referral_reward_amount' => $settings->referral_reward_amount,
                    'order_reward_percentage' => $settings->order_reward_percentage,
                    'max_order_reward' => $settings->max_order_reward,
                    'max_referrals_per_month' => $settings->max_referrals_per_month,
                    'min_purchase_amount' => $settings->min_purchase_amount,
                    'send_sms_notifications' => $settings->send_sms_notifications,
                    'welcome_bonus' => $settings->welcome_bonus,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در دریافت تنظیمات: ' . $e->getMessage()
            ], 500);
        }
    }
}
