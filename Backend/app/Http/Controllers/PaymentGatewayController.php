<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\SmsPackage;
use App\Models\WalletPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment;
use Illuminate\Support\Facades\Log;

class PaymentGatewayController extends Controller
{
    /**
     * Redirect user to payment gateway
     */
    public function redirect(Order $order)
    {
        try {
            $user = Auth::user();

            // Check if user owns this order
            if ($order->user_id !== $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'دسترسی غیرمجاز'
                ], 403);
            }

            // Check if order is still pending
            if ($order->status !== 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'وضعیت سفارش قابل پرداخت نیست.'
                ], 400);
            }

            $description = '';
            
            // Determine order type and set description
            if ($order->type === 'wallet_package') {
                $package = WalletPackage::find($order->item_id);
                $description = "شارژ کیف پول - {$order->item_title} - سفارش {$order->id}";
            } else if ($order->sms_package_id) {
                $package = SmsPackage::find($order->sms_package_id);
                $description = "خرید بسته پیامک - {$package->name} - سفارش {$order->id}";
            } else {
                $description = "پرداخت سفارش {$order->id}";
            }

            // Create invoice
            $invoice = new Invoice();
            $invoice->amount($order->amount);
            $invoice->detail('description', $description);
            $invoice->detail('mobile', $user->mobile);

            // Set callback URL based on order type
            $callbackUrl = $order->type === 'wallet_package' 
                ? route('payment.wallet.callback')
                : route('payment.verify');

            // Create payment
            $payment = Payment::via('zarinpal')->callbackUrl($callbackUrl);

            // Purchase and get payment URL
            $payment->purchase(
                $invoice,
                function ($driver, $transactionId) use ($order) {
                    // Store transaction ID in order
                    $order->update(['transaction_id' => $transactionId]);
                }
            );

            $redirectionForm = $payment->pay();
            $paymentUrl = (string) $redirectionForm;

            return response()->json([
                'status' => 'success',
                'payment_url' => $paymentUrl,
                'order_id' => $order->id,
                'amount' => $order->amount
            ]);

        } catch (\Exception $e) {
            Log::error('Payment Gateway Error: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'user_id' => $user->id ?? null
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'خطا در اتصال به درگاه پرداخت: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle wallet package payment callback
     */
    public function walletCallback(Request $request)
    {
        try {
            $authority = $request->Authority;
            $status = $request->Status;

            if ($status === 'OK') {
                // Find order by transaction ID
                $order = Order::where('transaction_id', $authority)
                    ->where('type', 'wallet_package')
                    ->where('status', 'pending')
                    ->first();

                if (!$order) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'سفارش یافت نشد.'
                    ], 404);
                }

                // Verify payment
                $payment = Payment::via('zarinpal');
                $receipt = $payment->amount($order->amount)->transactionId($authority)->verify();

                if ($receipt->getReferenceId()) {
                    DB::beginTransaction();

                    // Update order
                    $order->update([
                        'status' => 'completed',
                        'payment_status' => 'paid',
                        'paid_at' => now(),
                        'reference_id' => $receipt->getReferenceId()
                    ]);

                    // Add credit to wallet
                    $user = $order->user;
                    $walletAmount = $order->metadata['wallet_amount'];
                    
                    \App\Models\WalletTransaction::createAndUpdateBalance([
                        'user_id' => $user->id,
                        'type' => \App\Models\WalletTransaction::TYPE_PACKAGE_PURCHASE,
                        'amount' => $walletAmount,
                        'description' => "شارژ کیف پول - {$order->item_title}",
                        'order_id' => $order->id,
                        'status' => \App\Models\WalletTransaction::STATUS_COMPLETED
                    ]);

                    DB::commit();

                    return response()->json([
                        'status' => 'success',
                        'message' => 'پرداخت با موفقیت انجام شد و کیف پول شما شارژ شد.',
                        'data' => [
                            'reference_id' => $receipt->getReferenceId(),
                            'charged_amount' => $walletAmount,
                            'new_balance' => $user->fresh()->wallet_balance,
                        ]
                    ]);
                }
            }

            return response()->json([
                'status' => 'error',
                'message' => 'پرداخت ناموفق یا لغو شده.'
            ], 400);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Wallet Payment Callback Error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'خطا در پردازش پرداخت: ' . $e->getMessage()
            ], 500);
        }
    }
}