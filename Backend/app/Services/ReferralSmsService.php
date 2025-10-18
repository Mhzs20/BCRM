<?php

namespace App\Services;

use App\Models\SmsTransaction;
use App\Models\ReferralSetting;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ReferralSmsService
{
    /**
     * Send SMS notification for referral reward
     */
    public static function sendRewardNotification(User $referrer, string $rewardType, float $amount, User $referred = null)
    {
        try {
            $settings = ReferralSetting::getActiveSettings();
            
            if (!$settings->send_sms_notifications) {
                return false;
            }

            $message = self::generateMessage($rewardType, $amount, $referred);
            
            if (!$message) {
                return false;
            }

            // Create SMS transaction record
            $smsTransaction = SmsTransaction::create([
                'user_id' => $referrer->id,
                'receptor' => $referrer->phone ?? $referrer->mobile,
                'content' => $message,
                'type' => 'referral_reward',
                'sms_type' => 'referral_notification',
                'status' => 'pending',
                'description' => 'اعلان پاداش ریفرال',
                'reference_id' => $referrer->id,
                'sms_count' => 1,
                'amount' => 0, // Free notification
            ]);

            // TODO: Integrate with actual SMS provider
            // For now, just mark as sent for testing
            $smsTransaction->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            Log::info('Referral SMS notification sent', [
                'referrer_id' => $referrer->id,
                'reward_type' => $rewardType,
                'amount' => $amount,
                'message' => $message
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send referral SMS notification', [
                'referrer_id' => $referrer->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Generate SMS message based on reward type
     */
    private static function generateMessage(string $rewardType, float $amount, User $referred = null): ?string
    {
        $formattedAmount = number_format($amount);
        
        switch ($rewardType) {
            case 'signup_reward':
                $referredInfo = $referred ? " ({$referred->name})" : '';
                return "🎉 تبریک! مبلغ {$formattedAmount} ریال بابت دعوت کاربر جدید{$referredInfo} به کیف پول شما اضافه شد.\nسالن زیبایی BCRM";
                
            case 'purchase_reward':
                $referredInfo = $referred ? " ({$referred->name})" : '';
                return "💰 مبلغ {$formattedAmount} ریال بابت خرید کاربر معرفی‌شده{$referredInfo} به کیف پول شما اضافه شد.\nسالن زیبایی BCRM";
                
            case 'manual_credit':
                return "💳 مبلغ {$formattedAmount} ریال به صورت دستی به کیف پول شما اضافه شد.\nسالن زیبایی BCRM";
                
            default:
                return null;
        }
    }

    /**
     * Send SMS for manual wallet transactions
     */
    public static function sendManualTransactionNotification(User $user, string $type, float $amount, string $description = '')
    {
        try {
            $settings = ReferralSetting::getActiveSettings();
            
            if (!$settings->send_sms_notifications) {
                return false;
            }

            $formattedAmount = number_format($amount);
            
            if ($type === 'credit') {
                $message = "💳 مبلغ {$formattedAmount} ریال به کیف پول شما اضافه شد.";
                if ($description) {
                    $message .= "\nتوضیحات: {$description}";
                }
                $message .= "\nسالن زیبایی BCRM";
            } else {
                $message = "📤 مبلغ {$formattedAmount} ریال از کیف پول شما کسر شد.";
                if ($description) {
                    $message .= "\nتوضیحات: {$description}";
                }
                $message .= "\nسالن زیبایی BCRM";
            }

            // Create SMS transaction record
            SmsTransaction::create([
                'user_id' => $user->id,
                'receptor' => $user->phone ?? $user->mobile,
                'content' => $message,
                'type' => 'wallet_transaction',
                'sms_type' => 'wallet_notification',
                'status' => 'sent',
                'sent_at' => now(),
                'description' => 'اعلان تراکنش کیف پول',
                'reference_id' => $user->id,
                'sms_count' => 1,
                'amount' => 0,
            ]);

            Log::info('Manual transaction SMS notification sent', [
                'user_id' => $user->id,
                'type' => $type,
                'amount' => $amount
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send manual transaction SMS notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
}