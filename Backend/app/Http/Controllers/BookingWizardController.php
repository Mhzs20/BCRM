<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Salon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BookingWizardController extends Controller
{
    /**
     * Check if a customer exists by mobile number.
     */
    public function checkCustomer(Request $request, $salonId)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|string|regex:/^09[0-9]{9}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'شماره موبایل نامعتبر است',
                'errors' => $validator->errors()
            ], 422);
        }

        $customer = Customer::where('salon_id', $salonId)
            ->where('phone_number', $request->mobile)
            ->first();

        if ($customer) {
            return response()->json([
                'success' => true,
                'exists' => true,
                'customer' => [
                    'name' => $customer->name,
                    'mobile' => $customer->phone_number
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'exists' => false
        ]);
    }

    /**
     * Send OTP to the customer.
     */
    public function sendOtp(Request $request, $salonId)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|string|regex:/^09[0-9]{9}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'شماره موبایل نامعتبر است'
            ], 422);
        }

        // Generate a random 6-digit OTP
        $otp = rand(100000, 999999);
        
        // Store OTP in cache for 2 minutes (120 seconds)
        // Key format: otp_salonId_mobile
        $cacheKey = "otp_{$salonId}_{$request->mobile}";
        Cache::put($cacheKey, $otp, 120);

        // Send OTP via SMS using the existing SmsService
        $smsService = app(\App\Services\SmsService::class);
        $smsSent = $smsService->sendOtp($request->mobile, (string)$otp);

        if (!$smsSent) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال پیامک. لطفا مجددا تلاش کنید.'
            ], 500);
        }

        Log::info("OTP sent successfully to {$request->mobile} for salon {$salonId}");

        return response()->json([
            'success' => true,
            'message' => 'کد تایید ارسال شد',
            'expires_in' => 120
        ]);
    }

    /**
     * Verify OTP.
     */
    public function verifyOtp(Request $request, $salonId)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|string|regex:/^09[0-9]{9}$/',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات ورودی نامعتبر است'
            ], 422);
        }

        $cacheKey = "otp_{$salonId}_{$request->mobile}";
        $cachedOtp = Cache::get($cacheKey);

        if (!$cachedOtp || $cachedOtp != $request->otp) {
            return response()->json([
                'success' => false,
                'message' => 'کد تایید اشتباه یا منقضی شده است'
            ], 400);
        }

        // OTP verified, clear it
        Cache::forget($cacheKey);

        return response()->json([
            'success' => true,
            'message' => 'تایید شد'
        ]);
    }
}
