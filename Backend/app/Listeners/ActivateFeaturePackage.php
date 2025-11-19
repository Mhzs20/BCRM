<?php

namespace App\Listeners;

use App\Events\FeaturePackagePurchased;
use App\Models\UserPackage;
use App\Models\DiscountCode;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActivateFeaturePackage implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(FeaturePackagePurchased $event): void
    {
        $order = $event->order;
        $successfulTransaction = $event->transaction;

        try {
            DB::transaction(function () use ($order, $successfulTransaction) {
                // 1. Update the Order status to 'completed' (if not already)
                if ($order->status === 'pending') {
                    $order->update(['status' => 'completed']);
                    Log::info("Order {$order->id} status updated to 'completed'.");
                } else {
                    Log::warning("Order {$order->id} was already '{$order->status}', not updating again.");
                }

                // 2. Deactivate all previous active packages for this user and salon
                UserPackage::where('user_id', $order->user_id)
                    ->where('salon_id', $order->salon_id)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'expired',
                        'updated_at' => now()
                    ]);
                Log::info("Previous active packages for user {$order->user_id} and salon {$order->salon_id} marked as expired.");

                // 3. Create or update the new user package for this salon
                // Get package to access duration_days
                $package = $order->package;
                $durationDays = $package->duration_days ?? 365; // پیش‌فرض 365 روز
                
                $userPackage = UserPackage::updateOrCreate(
                    [
                        'user_id' => $order->user_id,
                        'salon_id' => $order->salon_id,
                        'package_id' => $order->package_id,
                        'order_id' => $order->id
                    ],
                    [
                        'amount_paid' => $order->amount,
                        'status' => 'active',
                        'purchased_at' => now(),
                        'expires_at' => Carbon::now()->addDays($durationDays) // بر اساس تنظیمات پکیج
                    ]
                );
                Log::info('Feature package activated successfully for salon', [
                    'user_id' => $order->user_id,
                    'salon_id' => $order->salon_id,
                    'package_id' => $order->package_id,
                    'order_id' => $order->id,
                    'user_package_id' => $userPackage->id,
                    'duration_days' => $durationDays,
                    'expires_at' => $userPackage->expires_at
                ]);

                // 5. If package has gift SMS, increment salon SMS balance
                if ($package && $package->gift_sms_count > 0) {
                    $salonSmsBalance = \App\Models\SalonSmsBalance::firstOrCreate(
                        ['salon_id' => $order->salon_id],
                        ['balance' => 0]
                    );
                    $salonSmsBalance->increment('balance', $package->gift_sms_count);

                    // Create SMS transaction record for gift
                    \App\Models\SmsTransaction::create([
                        'salon_id' => $order->salon_id,
                        'type' => 'gift',
                        'amount' => $package->gift_sms_count,
                        'description' => "هدیه بسته امکانات - سفارش {$order->id}",
                        'status' => 'completed',
                        'approved_by' => $order->user_id, // The user who purchased the package
                    ]);

                    Log::info("Added {$package->gift_sms_count} gift SMS to salon {$order->salon_id} for package {$package->name}");
                }

                // 6. SECURITY: Record discount code usage if used
                if ($order->discount_code) {
                    $discountCode = DiscountCode::where('code', $order->discount_code)->first();
                    if ($discountCode) {
                        // SECURITY: Record salon-specific usage to prevent reuse
                        if ($order->salon_id) {
                            $discountCode->recordSalonUsage($order->salon_id, $order->id);
                            Log::info("Discount code {$order->discount_code} usage recorded for feature package. Salon {$order->salon_id} usage recorded for order {$order->id}.");
                        }
                    }
                }

                // 7. Mark all *other* Transactions belonging to the same Order as 'expired'
                $order->transactions()
                    ->where('id', '!=', $successfulTransaction->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'expired', 'description' => 'تراکنش منقضی شده به دلیل پرداخت موفقیت آمیز تراکنش دیگر برای همین سفارش.']);
                Log::info("Other pending transactions for Order {$order->id} marked as 'expired'.");
            });
        } catch (\Exception $e) {
            Log::error('Failed to activate feature package', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw to mark job as failed
        }
    }
}
