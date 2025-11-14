<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WalletPackage;
use App\Models\WalletTransaction;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletPackageController extends Controller
{
    /**
     * Get all active wallet packages
     */
    public function index(Request $request)
    {
        try {
            $packages = WalletPackage::active()
                ->orderBy('sort_order', 'asc')
                ->orderBy('price', 'asc')
                ->get()
                ->map(function ($package) {
                    return [
                        'id' => $package->id,
                        'title' => $package->title,
                        'description' => $package->description,
                        'amount' => $package->amount,
                        'price' => $package->price,
                        'final_price' => $package->final_price,
                        'discount_percentage' => $package->discount_percentage,
                        'discount_amount' => $package->discount_amount,
                        'is_featured' => $package->is_featured,
                        'icon' => $package->icon,
                        'color' => $package->color,
                        'formatted_amount' => $package->formatted_amount,
                        'formatted_price' => $package->formatted_price,
                        'formatted_final_price' => $package->formatted_final_price,
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $packages
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در دریافت پکیج‌ها: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific wallet package
     */
    public function show($salon, WalletPackage $walletPackage)
    {
        try {
            if (!$walletPackage->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'این پکیج در حال حاضر قابل دسترس نیست.'
                ], 404);
            }

            $data = [
                'id' => $walletPackage->id,
                'title' => $walletPackage->title,
                'description' => $walletPackage->description,
                'amount' => $walletPackage->amount,
                'price' => $walletPackage->price,
                'final_price' => $walletPackage->final_price,
                'discount_percentage' => $walletPackage->discount_percentage,
                'discount_amount' => $walletPackage->discount_amount,
                'is_featured' => $walletPackage->is_featured,
                'icon' => $walletPackage->icon,
                'color' => $walletPackage->color,
                'formatted_amount' => $walletPackage->formatted_amount,
                'formatted_price' => $walletPackage->formatted_price,
                'formatted_final_price' => $walletPackage->formatted_final_price,
            ];

            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در دریافت اطلاعات پکیج: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Purchase wallet package
     */
    public function purchase(Request $request, $salon, WalletPackage $walletPackage)
    {
        try {
            $validated = $request->validate([
                'callback_url' => ['required', 'regex:/^[a-zA-Z][a-zA-Z0-9+.-]*:\/\//'], // Allow custom schemes
                'discount_code' => 'nullable|string|exists:discount_codes,code',
            ]);
            
            $user = $request->user();
            $package = $walletPackage;

            if (!$package->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'این پکیج در حال حاضر قابل دسترس نیست.'
                ], 400);
            }

            // Calculate amount (always from database, never trust client)
            $amount = $package->final_price;
            $originalAmount = $package->price;
            $discountPercentage = $package->discount_percentage;
            $discountCode = null;

            // Check and apply additional discount code if provided
            if ($request->discount_code) {
                $discountCodeModel = \App\Models\DiscountCode::where('code', $request->discount_code)
                    ->where('is_active', true)
                    ->first();

                if (!$discountCodeModel) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'کد تخفیف نامعتبر است.',
                    ], 400);
                }

                if (!$discountCodeModel->isValid()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'کد تخفیف منقضی شده یا غیرفعال است.',
                    ], 400);
                }

                if (!$discountCodeModel->canUserUse($user)) {
                    \Illuminate\Support\Facades\Log::warning('Unauthorized discount code usage attempt', [
                        'user_id' => $user->id,
                        'discount_code' => $request->discount_code,
                        'ip' => $request->ip(),
                    ]);
                    
                    return response()->json([
                        'status' => 'error',
                        'message' => 'شما مجاز به استفاده از این کد تخفیف نیستید.',
                    ], 403);
                }

                // Check minimum order amount
                if ($discountCodeModel->min_order_amount && $amount < $discountCodeModel->min_order_amount) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "حداقل مبلغ سفارش برای استفاده از این کد تخفیف " . number_format($discountCodeModel->min_order_amount) . " تومان است.",
                    ], 400);
                }

                // Apply additional discount
                $discountAmount = $discountCodeModel->calculateDiscount($amount);
                $amount = $amount - $discountAmount;
                $discountCode = $request->discount_code;
                
                if ($discountCodeModel->type === 'percentage') {
                    $discountPercentage = $discountPercentage + $discountCodeModel->value;
                } else {
                    $discountPercentage = (($originalAmount - $amount) / $originalAmount) * 100;
                }
            }

            DB::beginTransaction();

            // Create order record
            $order = Order::create([
                'user_id' => $user->id,
                'salon_id' => null, // Wallet packages are not tied to a specific salon
                'type' => 'wallet_package',
                'item_id' => $package->id,
                'item_title' => $package->title,
                'amount' => $amount,
                'original_amount' => $originalAmount,
                'discount_amount' => $originalAmount - $amount,
                'status' => 'pending',
                'payment_method' => 'online',
                'sms_count' => 0,
                'discount_code' => $discountCode,
                'discount_percentage' => $discountPercentage,
                'metadata' => [
                    'wallet_amount' => $package->amount,
                    'package_details' => $package->toArray(),
                ]
            ]);

            // Create transaction record
            $transaction = \App\Models\Transaction::create([
                'order_id' => $order->id,
                'gateway' => 'zarinpal',
                'amount' => $amount,
                'status' => 'pending',
                'description' => 'در انتظار پرداخت',
            ]);

            try {
                $invoice = new \Shetabit\Multipay\Invoice();
                $invoice->amount($amount);
                $invoice->detail('description', "شارژ کیف پول: {$package->title} - سفارش {$order->id}");
                $invoice->detail('mobile', $user->mobile);

                // Handle callback URL (support deep-links)
                $providedCallback = $request->callback_url;
                if (\Illuminate\Support\Str::startsWith($providedCallback, ['http://', 'https://']) === false) {
                    if ($providedCallback !== 'return://ziboxcrm.ir') {
                        \Illuminate\Support\Facades\Log::warning('Unauthorized callback scheme in wallet purchase', [
                            'callback_url' => $providedCallback,
                            'user_id' => $user->id,
                            'ip' => $request->ip(),
                        ]);
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => 'callback_url نامعتبر است.',
                        ], 400);
                    }
                    
                    $proxy = route('payment.callback_proxy') . '?app_return=' . urlencode($providedCallback);
                    $payment = \Shetabit\Payment\Facade\Payment::via('zarinpal')->callbackUrl($proxy);
                } else {
                    $payment = \Shetabit\Payment\Facade\Payment::via('zarinpal')->callbackUrl($providedCallback);
                }

                $payment->purchase(
                    $invoice,
                    function ($driver, $transactionId) use ($transaction) {
                        $transaction->update(['transaction_id' => $transactionId]);
                    }
                );

                $redirectionForm = $payment->pay();
                $paymentUrl = (string) $redirectionForm;

                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'سفارش خرید پکیج ایجاد شد.',
                    'data' => [
                        'order_id' => $order->id,
                        'amount' => $amount,
                        'wallet_amount' => $package->amount,
                        'payment_url' => $paymentUrl,
                        'authority' => $transaction->transaction_id,
                    ]
                ]);

            } catch (\Exception $e) {
                $transaction->update(['status' => 'failed', 'description' => 'خطا در ایجاد لینک پرداخت: ' . $e->getMessage()]);
                $order->update(['status' => 'failed']);
                DB::rollBack();
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'خطا در ایجاد لینک پرداخت.',
                ], 500);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در ایجاد سفارش: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify wallet package payment
     */
    public function verify(Request $request)
    {
        $request->validate([
            'authority' => 'required|string',
        ]);

        $authority = $request->authority;
        $user = $request->user();

        // Find transaction by authority and ensure it belongs to the current user
        $transaction = \App\Models\Transaction::where('transaction_id', $authority)
            ->whereHas('order', function ($query) use ($user) {
                $query->where('user_id', $user->id)->where('type', 'wallet_package');
            })
            ->first();

        if (!$transaction) {
            return response()->json([
                'status' => 'error',
                'message' => 'تراکنش یافت نشد یا متعلق به این کاربر نیست.',
            ], 404);
        }

        $order = $transaction->order;

        // If order is already paid
        if ($order->status === 'paid') {
            $transaction->update([
                'status' => 'expired',
                'description' => 'این سفارش قبلا با موفقیت پرداخت شده است.',
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'این خرید قبلا با موفقیت انجام شده است.',
                'reference_id' => $transaction->reference_id,
            ], 409);
        }

        // If transaction is already completed
        if ($transaction->status === 'completed') {
            return response()->json([
                'status' => 'error',
                'message' => 'این تراکنش قبلا با موفقیت تایید شده است.',
                'reference_id' => $transaction->reference_id,
            ], 409);
        }

        try {
            DB::transaction(function () use ($transaction, $order, $authority) {
                $expectedAmount = $transaction->amount;
                
                // Verify payment with Zarinpal
                $receipt = \Shetabit\Payment\Facade\Payment::via('zarinpal')
                    ->amount($expectedAmount)
                    ->transactionId($authority)
                    ->verify();

                $referenceId = $receipt->getReferenceId();

                // Update transaction
                $transaction->update([
                    'status' => 'completed',
                    'reference_id' => $referenceId,
                    'description' => 'پرداخت با موفقیت تایید شد',
                ]);

                // Dispatch event to update wallet balance
                event(new \App\Events\PaymentSuccessful($order, $transaction));

                \Illuminate\Support\Facades\Log::info('Wallet PaymentSuccessful event dispatched.', [
                    'order_id' => $order->id,
                    'transaction_id' => $transaction->id,
                    'amount' => $expectedAmount,
                    'reference_id' => $referenceId,
                ]);
            });

            $order->refresh();
            $transaction->refresh();
            $user->refresh();

            return response()->json([
                'status' => 'success',
                'message' => 'پرداخت با موفقیت تایید شد.',
                'data' => [
                    'wallet_amount_added' => $order->metadata['wallet_amount'] ?? 0,
                    'new_wallet_balance' => $user->wallet_balance ?? 0,
                    'reference_id' => $transaction->reference_id,
                    'order_status' => $order->status,
                ]
            ]);
            
        } catch (\Shetabit\Multipay\Exceptions\InvalidPaymentException $e) {
            $transaction->update(['status' => 'failed', 'description' => $e->getMessage()]);
            $order->update(['status' => 'failed']);
            
            \Illuminate\Support\Facades\Log::warning('Wallet payment verification failed: ' . $e->getMessage(), [
                'authority' => $authority,
                'order_id' => $order->id
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'پرداخت ناموفق بود یا توسط کاربر لغو شده است.',
                'error' => $e->getMessage(),
            ], 400);
            
        } catch (\Exception $e) {
            $transaction->update(['status' => 'failed', 'description' => 'خطای ناشناخته: ' . $e->getMessage()]);
            $order->update(['status' => 'failed']);
            
            \Illuminate\Support\Facades\Log::error('Wallet payment verification error: ' . $e->getMessage(), [
                'authority' => $authority,
                'order_id' => $order->id
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'خطا در فرآیند تایید پرداخت.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
