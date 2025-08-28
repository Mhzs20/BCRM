<?php

namespace App\Http\Controllers;

use App\Models\SmsPackage;
use App\Models\SmsTransaction;
use Illuminate\Http\Request;
use App\Models\SalonSmsBalance; // Ensure SalonSmsBalance is imported
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

        // According to best practices, allow multiple payment attempts (transactions) for a single purchase intent.
        // The uniqueness will be handled at the verification stage.
        // No check for pending transactions here, always create a new one.

        $amount = $package->discount_price ?? $package->price;

        if (!$user->active_salon_id) {
            return response()->json([
                'status' => 'NOK',
                'message' => 'شما هیچ سالن فعالی برای انجام تراکنش ندارید.',
            ], 400);
        }

        // Create a new transaction record
        $transaction = SmsTransaction::create([
            'user_id' => $user->id,
            'salon_id' => $user->active_salon_id, // Add the active salon ID
            'sms_package_id' => $package->id,
            'amount' => $amount,
            'status' => 'pending',
            'sms_count' => $package->sms_count,
            'sms_type' => 'purchase', // Set sms_type to 'purchase'
        ]);

        try {
            $invoice = new Invoice();
            $invoice->amount($amount);
            $invoice->detail('description', "خرید بسته پیامک برای کاربر {$user->id}");
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
            ]);

        } catch (\Exception $e) {
            $transaction->update(['status' => 'failed', 'description' => 'Error during purchase setup']);
            Log::error('Zarinpal Purchase Error: ' . $e->getMessage());

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

        $transaction = SmsTransaction::where('transaction_id', $authority)
                                     ->where('user_id', $user->id)
                                     ->first();

        if (!$transaction) {
            return response()->json([
                'status' => 'NOK',
                'message' => 'تراکنش یافت نشد یا متعلق به این کاربر نیست.',
            ], 404);
        }

        // If the current transaction is already completed, return early.
        if ($transaction->status === 'completed') {
            return response()->json([
                'status' => 'NOK',
                'message' => 'این تراکنش قبلا با موفقیت تایید شده است.',
                'reference_id' => $transaction->reference_id,
            ], 409); // 409 Conflict
        }

        try {
            $salonSmsBalance = null; // Initialize variable

            DB::transaction(function () use ($transaction, $authority, &$salonSmsBalance) {
                // Verify the payment with Zarinpal
                $receipt = Payment::via('zarinpal')
                    ->amount($transaction->amount)
                    ->transactionId($authority)
                    ->verify();

                $referenceId = $receipt->getReferenceId();

                // Check if the "order" (user buying this specific package) has already been completed by another transaction.
                $existingCompletedPurchase = SmsTransaction::where('user_id', $transaction->user_id)
                    ->where('sms_package_id', $transaction->sms_package_id)
                    ->where('status', 'completed')
                    ->exists();

                if ($existingCompletedPurchase) {
                    // If the purchase is already completed, mark this transaction as a failed duplicate.
                    $transaction->update([
                        'status' => 'failed_duplicate',
                        'reference_id' => $referenceId,
                        'description' => 'این خرید قبلا با موفقیت انجام شده است (تراکنش تکراری).',
                    ]);
                    // No need to throw an exception here, just return a specific response later.
                    return; // Exit the transaction closure
                }

                // If this is the first successful transaction for this purchase intent, complete it.
                $transaction->update([
                    'status' => 'completed',
                    'reference_id' => $referenceId,
                    'description' => 'پرداخت با موفقیت تایید شد',
                ]);

                Log::info('Attempting to update salon SMS balance after Zarinpal purchase.', [
                    'salon_id' => $transaction->salon_id,
                    'sms_count_to_add' => $transaction->sms_count,
                    'transaction_id' => $transaction->id,
                ]);

                // Update the SalonSmsBalance directly using the transaction's salon_id
                $salonSmsBalance = SalonSmsBalance::firstOrCreate(
                    ['salon_id' => $transaction->salon_id],
                    ['balance' => 0]
                );
                $salonSmsBalance->increment('balance', $transaction->sms_count);

                Log::info('Salon SMS balance updated successfully after Zarinpal purchase.', [
                    'salon_id' => $transaction->salon_id,
                    'new_salon_balance' => $salonSmsBalance->balance,
                    'transaction_id' => $transaction->id,
                ]);
            });

            // Check if the transaction was marked as failed_duplicate within the transaction block
            if ($transaction->status === 'failed_duplicate') {
                return response()->json([
                    'status' => 'NOK',
                    'message' => 'این خرید قبلا با موفقیت انجام شده است. (تراکنش تکراری)',
                    'reference_id' => $transaction->reference_id,
                ], 409); // 409 Conflict
            }

            return response()->json([
                'status' => 'OK',
                'message' => 'پرداخت با موفقیت تایید شد.',
                'purchased_sms' => $transaction->sms_count,
                'salon_total_balance' => $salonSmsBalance ? $salonSmsBalance->balance : null, // Include salon balance
                'reference_id' => $transaction->reference_id,
            ]);
        } catch (InvalidPaymentException $e) {
            // This exception is specifically for failed verifications (e.g., user cancelled)
            $transaction->update(['status' => 'failed', 'description' => $e->getMessage()]);
            Log::warning('Zarinpal Verification Failed: ' . $e->getMessage(), ['authority' => $authority]);

            return response()->json([
                'status' => 'NOK',
                'message' => 'پرداخت ناموفق بود یا توسط کاربر لغو شده است.',
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            // Other exceptions (e.g., network issues, config problems)
            // Update transaction status to failed for any unexpected errors
            $transaction->update(['status' => 'failed', 'description' => 'خطای ناشناخته در تایید پرداخت: ' . $e->getMessage()]);
            Log::error('Zarinpal Verification Error: ' . $e->getMessage(), ['authority' => $authority]);

            return response()->json([
                'status' => 'NOK',
                'message' => 'خطا در فرآیند تایید پرداخت. لطفا با پشتیبانی تماس بگیرید.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
