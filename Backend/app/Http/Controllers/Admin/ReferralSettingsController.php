<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferralSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReferralSettingsController extends Controller
{
    /**
     * Display referral settings form
     */
    public function index()
    {
        $settings = ReferralSetting::getActiveSettings();
        return view('admin.referral.settings', compact('settings'));
    }

    /**
     * Update referral settings
     */
    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'is_active' => 'boolean',
                'enable_signup_reward' => 'boolean',
                'enable_purchase_reward' => 'boolean',
                'referral_reward_amount' => 'nullable|numeric|min:0',
                'order_reward_percentage' => 'nullable|numeric|min:0|max:100',
                'max_order_reward' => 'nullable|numeric|min:0',
                'max_referrals_per_month' => 'nullable|integer|min:0',
                'min_purchase_amount' => 'nullable|numeric|min:0',
                'send_sms_notifications' => 'boolean',
                'welcome_bonus' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $data = $request->only([
                'referral_reward_amount', 'order_reward_percentage',
                'max_order_reward', 'max_referrals_per_month', 'min_purchase_amount',
                'welcome_bonus'
            ]);

            // Convert checkboxes
            $data['is_active'] = $request->has('is_active');
            $data['enable_signup_reward'] = $request->has('enable_signup_reward');
            $data['enable_purchase_reward'] = $request->has('enable_purchase_reward');
            $data['send_sms_notifications'] = $request->has('send_sms_notifications');

            // Validation based on enabled reward types
            if ($data['enable_signup_reward'] && empty($data['referral_reward_amount'])) {
                return redirect()->back()
                    ->withErrors(['referral_reward_amount' => 'مبلغ پاداش ثبت نام الزامی است.'])
                    ->withInput();
            }

            if ($data['enable_purchase_reward'] && empty($data['order_reward_percentage'])) {
                return redirect()->back()
                    ->withErrors(['order_reward_percentage' => 'درصد پاداش خرید الزامی است.'])
                    ->withInput();
            }

            // At least one reward type should be enabled if system is active
            if ($data['is_active'] && !$data['enable_signup_reward'] && !$data['enable_purchase_reward']) {
                return redirect()->back()
                    ->withErrors(['enable_signup_reward' => 'حداقل یک نوع پاداش باید فعال باشد.'])
                    ->withInput();
            }

            $settings = ReferralSetting::updateOrCreate(
                ['id' => 1],
                $data
            );

            return redirect()->back()->with('success', 'تنظیمات با موفقیت به‌روزرسانی شد.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'خطا در به‌روزرسانی تنظیمات: ' . $e->getMessage())
                ->withInput();
        }
    }
    /**
     * Get current referral settings
     */
    public function getSettings()
    {
        try {
            $settings = ReferralSetting::getActiveSettings();
            
            return response()->json([
                'status' => 'success',
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در دریافت تنظیمات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update referral settings
     */
    public function updateSettings(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'signup_reward' => 'required|numeric|min:0|max:10000000',
                'purchase_percentage' => 'required|numeric|min:0|max:100',
                'monthly_referral_limit' => 'required|integer|min:1|max:1000',
                'minimum_withdrawal_amount' => 'required|numeric|min:1000|max:10000000',
                'is_active' => 'required|boolean',
                'reward_type' => 'required|in:cash,points,discount',
                'max_purchase_reward_amount' => 'nullable|numeric|min:0|max:10000000',
            ], [
                'signup_reward.required' => 'پاداش ثبت‌نام الزامی است.',
                'signup_reward.numeric' => 'پاداش ثبت‌نام باید عددی باشد.',
                'signup_reward.min' => 'پاداش ثبت‌نام نمی‌تواند منفی باشد.',
                'signup_reward.max' => 'پاداش ثبت‌نام نمی‌تواند بیش از ۱۰ میلیون تومان باشد.',
                'purchase_percentage.required' => 'درصد پاداش خرید الزامی است.',
                'purchase_percentage.numeric' => 'درصد پاداش خرید باید عددی باشد.',
                'purchase_percentage.min' => 'درصد پاداش خرید نمی‌تواند منفی باشد.',
                'purchase_percentage.max' => 'درصد پاداش خرید نمی‌تواند بیش از ۱۰۰ درصد باشد.',
                'monthly_referral_limit.required' => 'حد مجاز دعوت ماهانه الزامی است.',
                'monthly_referral_limit.integer' => 'حد مجاز دعوت ماهانه باید عدد صحیح باشد.',
                'monthly_referral_limit.min' => 'حد مجاز دعوت ماهانه باید حداقل ۱ باشد.',
                'monthly_referral_limit.max' => 'حد مجاز دعوت ماهانه نمی‌تواند بیش از ۱۰۰۰ باشد.',
                'minimum_withdrawal_amount.required' => 'حداقل مبلغ برداشت الزامی است.',
                'minimum_withdrawal_amount.numeric' => 'حداقل مبلغ برداشت باید عددی باشد.',
                'minimum_withdrawal_amount.min' => 'حداقل مبلغ برداشت نمی‌تواند کمتر از ۱۰۰۰ تومان باشد.',
                'minimum_withdrawal_amount.max' => 'حداقل مبلغ برداشت نمی‌تواند بیش از ۱۰ میلیون تومان باشد.',
                'is_active.required' => 'وضعیت فعال بودن سیستم الزامی است.',
                'is_active.boolean' => 'وضعیت فعال بودن سیستم باید true یا false باشد.',
                'reward_type.required' => 'نوع پاداش الزامی است.',
                'reward_type.in' => 'نوع پاداش باید یکی از cash، points یا discount باشد.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get current settings or create new one
            $settings = ReferralSetting::where('is_active', true)->first();
            
            if (!$settings) {
                $settings = new ReferralSetting();
            }

            // Deactivate all existing settings first
            ReferralSetting::where('is_active', true)->update(['is_active' => false]);

            // Update settings
            $settings->fill($request->all());
            $settings->is_active = true;
            $settings->save();

            return response()->json([
                'status' => 'success',
                'message' => 'تنظیمات رفرال با موفقیت به‌روزرسانی شد.',
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در به‌روزرسانی تنظیمات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get referral settings history
     */
    public function getSettingsHistory(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 10);

            $settings = ReferralSetting::orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'status' => 'success',
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در دریافت تاریخچه تنظیمات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle referral system status
     */
    public function toggleSystem(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'is_active' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $settings = ReferralSetting::getActiveSettings();
            $settings->is_active = $request->is_active;
            $settings->save();

            $status = $request->is_active ? 'فعال' : 'غیرفعال';

            return response()->json([
                'status' => 'success',
                'message' => "سیستم رفرال با موفقیت {$status} شد.",
                'data' => [
                    'is_active' => $settings->is_active
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در تغییر وضعیت سیستم: ' . $e->getMessage()
            ], 500);
        }
    }
}
