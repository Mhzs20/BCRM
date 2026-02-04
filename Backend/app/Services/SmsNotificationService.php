<?php

namespace App\Services;

use App\Models\SalonAdmin;

class SmsNotificationService
{
    /**
     * Send welcome SMS with login credentials to new admin.
     */
    public function sendWelcomeSms(SalonAdmin $admin, string $password): void
    {
        $message = "سلام {$admin->first_name} عزیز،\n";
        $message .= "حساب کاربری شما در سیستم ایجاد شد.\n";
        $message .= "شماره موبایل: {$admin->mobile}\n";
        $message .= "رمز عبور: {$password}\n";
        $message .= "لطفا پس از ورود، رمز عبور خود را تغییر دهید.";

        $this->sendSms($admin->mobile, $message);
    }

    /**
     * Send password reset notification.
     */
    public function sendPasswordResetSms(SalonAdmin $admin, string $newPassword): void
    {
        $message = "سلام {$admin->first_name} عزیز،\n";
        $message .= "رمز عبور شما توسط مدیر سالن تغییر کرد.\n";
        $message .= "رمز عبور جدید: {$newPassword}\n";
        $message .= "لطفا پس از ورود، رمز عبور خود را تغییر دهید.";

        $this->sendSms($admin->mobile, $message);
    }

    /**
     * Send account activation notification.
     */
    public function sendActivationSms(SalonAdmin $admin): void
    {
        $message = "سلام {$admin->first_name} عزیز،\n";
        $message .= "حساب کاربری شما فعال شد.\n";
        $message .= "اکنون می‌توانید وارد سیستم شوید.";

        $this->sendSms($admin->mobile, $message);
    }

    /**
     * Send account deactivation notification.
     */
    public function sendDeactivationSms(SalonAdmin $admin): void
    {
        $message = "سلام {$admin->first_name} عزیز،\n";
        $message .= "حساب کاربری شما غیرفعال شد.\n";
        $message .= "برای اطلاعات بیشتر با مدیر سالن تماس بگیرید.";

        $this->sendSms($admin->mobile, $message);
    }

    /**
     * Send SMS using configured service.
     */
    protected function sendSms(string $mobile, string $message): void
    {
        // Integrate with your SMS service (Kavenegar, etc.)
        // Example with Kavenegar:
        // try {
        //     $api = new \Kavenegar\KavenegarApi(config('kavenegar.apiKey'));
        //     $api->Send(config('kavenegar.sender'), $mobile, $message);
        // } catch (\Exception $e) {
        //     \Log::error("Failed to send SMS to {$mobile}: " . $e->getMessage());
        // }

        // For now, just log it
        \Log::info("SMS sent to {$mobile}: {$message}");
    }
}
