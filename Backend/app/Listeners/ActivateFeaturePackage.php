<?php

namespace App\Listeners;

use App\Events\FeaturePackagePurchased;
use App\Models\UserPackage;
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

            // 4. Mark all *other* Transactions belonging to the same Order as 'expired'
            $order->transactions()
                ->where('id', '!=', $successfulTransaction->id)
                ->where('status', 'pending')
                ->update(['status' => 'expired', 'description' => 'تراکنش منقضی شده به دلیل پرداخت موفقیت آمیز تراکنش دیگر برای همین سفارش.']);
            Log::info("Other pending transactions for Order {$order->id} marked as 'expired'.");
        });
    }
}
