<?php

namespace App\Listeners;

use App\Events\PaymentSuccessful;
use App\Models\SalonSmsBalance;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateOrderAndBalance implements ShouldQueue
{
    use InteractsWithQueue;

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
            } else {
                Log::warning("Order {$order->id} was already '{$order->status}', not updating balance again.");
            }


            // 3. Mark all *other* Transactions belonging to the same Order as 'expired'
            $order->transactions()
                ->where('id', '!=', $successfulTransaction->id)
                ->where('status', 'pending') // Only expire pending transactions
                ->update(['status' => 'expired', 'description' => 'تراکنش منقضی شده به دلیل پرداخت موفقیت آمیز تراکنش دیگر برای همین سفارش.']);
            Log::info("Other pending transactions for Order {$order->id} marked as 'expired'.");
        });
    }
}
