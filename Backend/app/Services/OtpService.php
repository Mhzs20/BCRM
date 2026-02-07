<?php

namespace App\Services;

use App\Models\AdminOtpVerification;
use Carbon\Carbon;

class OtpService
{
    /**
     * OTP expiration time in minutes.
     */
    protected int $otpExpirationMinutes = 5;

    /**
     * Maximum attempts per mobile per hour.
     */
    protected int $maxAttemptsPerHour = 5;

    /**
     * Send OTP to mobile number.
     */
    public function sendOtp(string $mobile, string $ipAddress = null): array
    {
        // Check rate limiting
        if ($this->hasExceededRateLimit($mobile)) {
            return [
                'success' => false,
                'message' => 'تعداد درخواست‌های شما بیش از حد مجاز است. لطفا بعدا تلاش کنید.',
            ];
        }

        // Generate OTP code
        $otpCode = AdminOtpVerification::generateOtpCode();

        // Save OTP to database
        $otp = AdminOtpVerification::create([
            'mobile' => $mobile,
            'otp_code' => $otpCode,
            'expires_at' => Carbon::now()->addMinutes($this->otpExpirationMinutes),
            'ip_address' => $ipAddress,
        ]);

        // Send SMS (integrate with your SMS service)
        $this->sendSms($mobile, $otpCode);

        return [
            'success' => true,
            'message' => 'کد تایید با موفقیت ارسال شد.',
            'expires_in' => $this->otpExpirationMinutes * 60, // in seconds
        ];
    }

    /**
     * Verify OTP code.
     */
    public function verifyOtp(string $mobile, string $otpCode): array
    {
        // Find the latest valid OTP for this mobile
        $otp = AdminOtpVerification::byMobile($mobile)
            ->valid()
            ->where('otp_code', $otpCode)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$otp) {
            return [
                'success' => false,
                'message' => 'کد تایید نامعتبر یا منقضی شده است.',
            ];
        }

        // Mark as verified and generate temp token
        $tempToken = $otp->markAsVerified();

        return [
            'success' => true,
            'message' => 'شماره موبایل با موفقیت تایید شد.',
            'data' => [
                'temp_token' => $tempToken,
            ],
        ];
    }

    /**
     * Validate temp token.
     */
    public function validateTempToken(string $tempToken): ?AdminOtpVerification
    {
        return AdminOtpVerification::where('temp_token', $tempToken)
            ->where('is_verified', true)
            ->where('created_at', '>', Carbon::now()->subHours(24)) // Token valid for 24 hours
            ->first();
    }

    /**
     * Check if mobile has exceeded rate limit.
     */
    protected function hasExceededRateLimit(string $mobile): bool
    {
        $count = AdminOtpVerification::byMobile($mobile)
            ->where('created_at', '>', Carbon::now()->subHour())
            ->count();

        return $count >= $this->maxAttemptsPerHour;
    }

    /**
     * Send SMS with OTP code.
     */
    protected function sendSms(string $mobile, string $otpCode): void
    {
        // Integrate with your SMS service (Kavenegar, etc.)
        // Example:
        // $api = new \Kavenegar\KavenegarApi(config('kavenegar.apiKey'));
        // $template = 'admin-otp';
        // $api->VerifyLookup($mobile, $otpCode, null, null, $template);

        // For now, just log it
        \Log::info("OTP sent to {$mobile}: {$otpCode}");
    }

    /**
     * Clean up old OTP records.
     */
    public function cleanupOldOtps(): int
    {
        return AdminOtpVerification::where('created_at', '<', Carbon::now()->subDays(7))
            ->delete();
    }
}
