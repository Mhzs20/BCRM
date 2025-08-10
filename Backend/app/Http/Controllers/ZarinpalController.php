<?php

namespace App\Http\Controllers;

use App\Models\SmsPackage;
use App\Models\SmsTransaction;
use App\Models\UserSmsBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'callback_url' => 'required|url', // The deep link for the app
        ]);

        $user = Auth::user();
        $package = SmsPackage::findOrFail($request->package_id);

        $amount = $package->discount_price ?? $package->price;

        // Create a new transaction record
        $transaction = SmsTransaction::create([
            'user_id' => $user->id,
            'sms_package_id' => $package->id,
            'amount' => $amount,
            'status' => 'pending',
            'sms_count' => $package->sms_count,
        ]);

        try {
            $invoice = new Invoice();
            $invoice->amount($amount);
            $invoice->detail('description', "خرید بسته پیامک برای کاربر {$user->id}");
            $invoice->detail('mobile', $user->mobile);

            // Zarinpal will redirect the user to this URL after payment attempt.
            // The mobile app must catch this URL to get the 'Authority' query parameter.
            $payment = Payment::via('zarinpal')
                ->callbackUrl($request->callback_url)
                ->purchase($invoice, function ($driver, $transactionId) use ($transaction) {
                    // Store the gateway transaction ID (Authority)
                    $transaction->update(['transaction_id' => $transactionId]);
                });

            $paymentUrl = $payment->getAction();

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

        if ($transaction->status === 'completed') {
            return response()->json([
                'status' => 'NOK',
                'message' => 'این تراکنش قبلا با موفقیت تایید شده است.',
            ], 409); // 409 Conflict
        }

        try {
            // Verify the payment with Zarinpal
            $receipt = Payment::via('zarinpal')
                ->amount($transaction->amount)
                ->transactionId($authority)
                ->verify();

            // Payment is successful, update transaction and user balance
            $transaction->update([
                'status' => 'completed',
                'reference_id' => $receipt->getReferenceId(),
                'description' => 'پرداخت با موفقیت تایید شد',
            ]);

            $userBalance = UserSmsBalance::firstOrCreate(['user_id' => $transaction->user_id]);
            $userBalance->increment('balance', $transaction->sms_count);

            return response()->json([
                'status' => 'OK',
                'message' => 'پرداخت با موفقیت تایید شد.',
                'purchased_sms' => $transaction->sms_count,
                'total_balance' => $userBalance->balance,
                'reference_id' => $receipt->getReferenceId(),
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
            // We don't change the transaction status here, it remains 'pending' for a potential retry.
            Log::error('Zarinpal Verification Error: ' . $e->getMessage(), ['authority' => $authority]);

            return response()->json([
                'status' => 'NOK',
                'message' => 'خطا در فرآیند تایید پرداخت. لطفا با پشتیبانی تماس بگیرید.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
