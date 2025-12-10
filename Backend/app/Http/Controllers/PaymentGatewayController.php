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
                $description = "شارژ کیف پول - {$order->item_title} - سفارش# {$order->id}";
            } else if ($order->type === 'wallet_charge') {
                $amountInToman = number_format($order->amount / 10);
                $customDescription = $order->metadata['description'] ?? 'شارژ کیف پول';
                $description = "شارژ کیف پول - {$amountInToman} تومان - سفارش# {$order->id}";
            } else if ($order->sms_package_id) {
                $package = SmsPackage::find($order->sms_package_id);
                $description = "خرید بسته پیامک - {$package->name} - سفارش# {$order->id}";
            } else {
                $description = "پرداخت سفارش {$order->id}";
            }

            // Create invoice
            $invoice = new Invoice();
            $invoice->amount($order->amount);
            $invoice->detail('description', $description);
            $invoice->detail('mobile', $user->mobile);

            // Prefer callback_url from order metadata if provided, otherwise
            // fall back to default callbacks based on order type.
            $callbackUrl = null;
            if (!empty($order->metadata) && is_array($order->metadata) && !empty($order->metadata['callback_url'])) {
                $callbackUrl = $order->metadata['callback_url'];
            }

            if (empty($callbackUrl)) {
                $callbackUrl = $order->type === 'wallet_package'
                    ? route('payment.wallet.callback')
                    : route('payment.verify');
            }

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
                    ->whereIn('status', ['pending', 'failed', 'canceled'])
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

    /**
     * Proxy gateway redirect to app deep-link.
     *
     * This endpoint is intentionally public: the payment gateway will redirect
     * the user's browser here after payment. We take the original app deep-link
     * provided by the client in the `app_return` query parameter and append the
     * gateway's query params (e.g. Authority, Status) so the mobile app receives
     * them when it opens.
     * 
     * Security: Only allows return://ziboxcrm.ir to prevent arbitrary deep-link redirects.
     */
    public function callbackProxy(Request $request)
    {
        $appReturn = $request->query('app_return');

        if (empty($appReturn)) {
            return response()->json(['message' => 'app_return parameter missing'], 400);
        }

        // Security: Only allow our specific app scheme to prevent arbitrary redirects
        if ($appReturn !== 'return://ziboxcrm.ir') {
            Log::warning('Unauthorized callback proxy attempt', [
                'app_return' => $appReturn,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return response()->json(['message' => 'Invalid app_return parameter'], 403);
        }

        // Build query string of gateway params, excluding app_return itself
        $params = $request->query();
        unset($params['app_return']);

        $append = http_build_query($params);

        $redirectUrl = $appReturn;
        if (!empty($append)) {
            $divider = strpos($appReturn, '?') === false ? '?' : '&';
            $redirectUrl .= $divider . $append;
        }

        // Use an open redirect to the deep-link. For custom schemes the browser
        // will try to open the mobile app.
        return redirect()->away($redirectUrl);
    }
}