<?php

namespace App\Listeners;

use App\Events\PaymentSuccessful;
use App\Models\SalonSmsBalance;
use App\Models\DiscountCode;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateOrderAndBalance implements ShouldQueue
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
    public function handle(PaymentSuccessful $event): void
    {
        $order = $event->order;
        $successfulTransaction = $event->transaction;

        DB::transaction(function () use ($order, $successfulTransaction) {
            // 1. Update the Order status to 'paid'
            if ($order->status === 'pending') {
                $order->update(['status' => 'paid']);
                Log::info("Order {$order->id} status updated to 'paid'.");

                // 2. Increment the SalonSmsBalance for the associated salon
                $salonSmsBalance = SalonSmsBalance::firstOrCreate(
                    ['salon_id' => $order->salon_id],
                    ['balance' => 0]
                );
                $salonSmsBalance->increment('balance', $order->sms_count);
                Log::info("Salon {$order->salon_id} SMS balance incremented by {$order->sms_count}. New balance: {$salonSmsBalance->balance}");

                // 2.5. Create SMS transaction record for purchase history
                \App\Models\SmsTransaction::create([
                    'salon_id' => $order->salon_id,
                    'sms_package_id' => $order->sms_package_id,
                    'type' => 'purchase',
                    'amount' => $order->sms_count,
                    'description' => "خرید بسته پیامک - سفارش {$order->id}",
                    'status' => 'completed',
                    'reference_id' => $successfulTransaction->reference_id ?? null,
                ]);
                Log::info("SMS transaction record created for order {$order->id}");

                // 3. SECURITY: Record discount code usage if used
                if ($order->discount_code) {
                    $discountCode = DiscountCode::where('code', $order->discount_code)->first();
                    if ($discountCode) {
                        $discountCode->incrementUsage();
                        
                        // SECURITY: Record salon-specific usage to prevent reuse
                        $discountCode->recordSalonUsage($order->salon_id, $order->id);
                        
                        Log::info("Discount code {$order->discount_code} usage incremented. Total usage: {$discountCode->usage_count}. Salon {$order->salon_id} usage recorded.");
                    }
                }
            } else {
                Log::warning("Order {$order->id} was already '{$order->status}', not updating balance again.");
            }


            // 4. Mark all *other* Transactions belonging to the same Order as 'expired'
            $order->transactions()
                ->where('id', '!=', $successfulTransaction->id)
                ->where('status', 'pending') // Only expire pending transactions
                ->update(['status' => 'expired', 'description' => 'تراکنش منقضی شده به دلیل پرداخت موفقیت آمیز تراکنش دیگر برای همین سفارش.']);
            Log::info("Other pending transactions for Order {$order->id} marked as 'expired'.");
        });
    }
}
