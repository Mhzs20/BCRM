<?php

namespace App\Http\Controllers;

use App\Models\SmsPackage;
use App\Models\Order; // New import
use App\Models\Transaction; // New import
use App\Events\PaymentSuccessful; // New import
use Illuminate\Http\Request;
use App\Models\SalonSmsBalance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment;
use Illuminate\Support\Facades\Log;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;

class ZarinpalController extends Controller
{
    /**
     * Create a new payment request and get the payment URL.
     * This endpoint should be protected by auth:sanctum.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function purchase(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:sms_packages,id',
            'callback_url' => ['required', 'regex:/^[a-zA-Z][a-zA-Z0-9+.-]*:\/\//'], // Allow custom schemes
        ]);

        $user = Auth::user();
        $package = SmsPackage::findOrFail($request->package_id);

        $amount = $package->discount_price ?? $package->price;

        if (!$user->active_salon_id) {
            return response()->json([
                'status' => 'NOK',
                'message' => 'شما هیچ سالن فعالی برای انجام تراکنش ندارید.',
            ], 400);
        }

        // Create a new Order record
        $order = Order::create([
            'user_id' => $user->id,
            'salon_id' => $user->active_salon_id,
            'sms_package_id' => $package->id,
            'amount' => $amount,
            'sms_count' => $package->sms_count,
            'status' => 'pending',
        ]);

        // Create a new Transaction record associated with the Order
        $transaction = Transaction::create([
            'order_id' => $order->id,
            'gateway' => 'zarinpal', // Assuming Zarinpal for this controller
            'amount' => $amount,
            'status' => 'pending',
            'description' => 'در انتظار پرداخت',
        ]);

        try {
            $invoice = new Invoice();
            $invoice->amount($amount);
            $invoice->detail('description', "خرید بسته پیامک: {$package->name} - سفارش {$order->id}");
            $invoice->detail('mobile', $user->mobile);

            // 1. Create the payment object and set the callback URL.
            $payment = Payment::via('zarinpal')->callbackUrl($request->callback_url);

            // 2. Prepare the invoice and the transaction callback.
            $payment->purchase(
                $invoice,
                function ($driver, $transactionId) use ($transaction) {
                    // Store the gateway transaction ID (Authority)
                    $transaction->update(['transaction_id' => $transactionId]);
                }
            );

            // 3. Generate the redirection form.
            $redirectionForm = $payment->pay();

            // 4. Get the payment URL from the redirection form by casting it to a string.
            $paymentUrl = (string) $redirectionForm;

            return response()->json([
                'status' => 'OK',
                'payment_url' => $paymentUrl,
                'authority' => $transaction->transaction_id, // Return for client-side reference
                'order_id' => $order->id, // Return order ID for client-side reference
            ]);

        } catch (\Exception $e) {
            $transaction->update(['status' => 'failed', 'description' => 'Error during purchase setup: ' . $e->getMessage()]);
            $order->update(['status' => 'failed']);
            Log::error('Zarinpal Purchase Error: ' . $e->getMessage(), ['order_id' => $order->id, 'transaction_id' => $transaction->id]);

            return response()->json([
                'status' => 'NOK',
                'message' => 'خطا در ایجاد لینک پرداخت.',
            ], 500);
        }
    }

    /**
     * Verify the payment using the authority from the client app.
     * This endpoint must be protected by auth:sanctum.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request)
    {
        $request->validate([
            'authority' => 'required|string',
        ]);

        $authority = $request->authority;
        $user = Auth::user();

        // Find the transaction by authority and ensure it belongs to the current user
        $transaction = Transaction::where('transaction_id', $authority)
                                  ->whereHas('order', function ($query) use ($user) {
                                      $query->where('user_id', $user->id);
                                  })
                                  ->first();

        if (!$transaction) {
            return response()->json([
                'status' => 'NOK',
                'message' => 'تراکنش یافت نشد یا متعلق به این کاربر نیست.',
            ], 404);
        }

        $order = $transaction->order;

        // If the order is already paid, this transaction is a duplicate attempt for an already completed order.
        if ($order->status === 'paid') {
            $transaction->update([
                'status' => 'expired',
                'description' => 'این سفارش قبلا با موفقیت پرداخت شده است.',
            ]);
            return response()->json([
                'status' => 'NOK',
                'message' => 'این خرید قبلا با موفقیت انجام شده است. (سفارش تکراری)',
                'reference_id' => $transaction->reference_id,
            ], 409); // 409 Conflict
        }

        // If this specific transaction is already completed, return early.
        if ($transaction->status === 'completed') {
            return response()->json([
                'status' => 'NOK',
                'message' => 'این تراکنش قبلا با موفقیت تایید شده است.',
                'reference_id' => $transaction->reference_id,
            ], 409); // 409 Conflict
        }

        try {
            DB::transaction(function () use ($transaction, $order, $authority) {
                // Verify the payment with Zarinpal
                $receipt = Payment::via('zarinpal')
                    ->amount($transaction->amount)
                    ->transactionId($authority)
                    ->verify();

                $referenceId = $receipt->getReferenceId();

                // Update the successful transaction
                $transaction->update([
                    'status' => 'completed',
                    'reference_id' => $referenceId,
                    'description' => 'پرداخت با موفقیت تایید شد',
                ]);

                // Dispatch the event to update order status, salon balance, and expire other transactions
                event(new PaymentSuccessful($order, $transaction));

                Log::info('PaymentSuccessful event dispatched.', [
                    'order_id' => $order->id,
                    'transaction_id' => $transaction->id,
                ]);
            });

            // Reload the order and transaction to get the latest status after the listener
            $order->refresh();
            $transaction->refresh();

            return response()->json([
                'status' => 'OK',
                'message' => 'پرداخت با موفقیت تایید شد.',
                'purchased_sms' => $order->sms_count,
                'salon_total_balance' => $order->salon->smsBalance->balance ?? 0, // Access balance via order's salon
                'reference_id' => $transaction->reference_id,
                'order_status' => $order->status,
            ]);
        } catch (InvalidPaymentException $e) {
            // This exception is specifically for failed verifications (e.g., user cancelled)
            $transaction->update(['status' => 'failed', 'description' => $e->getMessage()]);
            $order->update(['status' => 'failed']); // Mark order as failed if transaction fails
            Log::warning('Zarinpal Verification Failed: ' . $e->getMessage(), ['authority' => $authority, 'order_id' => $order->id]);

            return response()->json([
                'status' => 'NOK',
                'message' => 'پرداخت ناموفق بود یا توسط کاربر لغو شده است.',
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            // Other exceptions (e.g., network issues, config problems)
            // Update transaction status to failed for any unexpected errors
            $transaction->update(['status' => 'failed', 'description' => 'خطای ناشناخته در تایید پرداخت: ' . $e->getMessage()]);
            $order->update(['status' => 'failed']); // Mark order as failed for any unexpected errors
            Log::error('Zarinpal Verification Error: ' . $e->getMessage(), ['authority' => $authority, 'order_id' => $order->id]);

            return response()->json([
                'status' => 'NOK',
                'message' => 'خطا در فرآیند تایید پرداخت. لطفا با پشتیبانی تماس بگیرید.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
