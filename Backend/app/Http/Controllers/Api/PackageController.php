<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\UserPackage;
use App\Events\FeaturePackagePurchased;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;

class PackageController extends Controller
{
    /**
     * لیست تمام پکیج‌های فعال
     * GET /api/salons/{salon}/feature-packages?discount_code=WINTER30
     */
    public function index(Request $request, $salon)
    {
        try {
            // Verify salon belongs to user
            $user = auth()->user();
            $userSalon = $user->salons()->find($salon);
            
            if (!$userSalon) {
                return response()->json([
                    'success' => false,
                    'message' => 'سالن یافت نشد یا به شما تعلق ندارد'
                ], 403);
            }

            // Check for discount code in request
            $discountCode = null;
            $discountCodeModel = null;

            if ($request->filled('discount_code')) {
                $discountCodeModel = \App\Models\DiscountCode::where('code', $request->discount_code)
                    ->where('is_active', true)
                    ->first();

                if ($discountCodeModel && $discountCodeModel->isValid()) {
                    // Check if user can use this code
                    if ($user && $discountCodeModel->canUserUse($user)) {
                        $discountCode = $discountCodeModel;
                    }
                }
            }

            $packages = Package::with('options')
                ->where('is_active', true)
                ->where('is_gift_only', false) // فقط پکیج‌های عادی نمایش داده شوند
                ->get()
                ->map(function ($package) use ($discountCode) {
                    $originalPrice = (int) $package->price;
                    
                    // Feature packages don't have built-in discounts, so package discount is always 0
                    $packageDiscountPercentage = 0;
                    $packageDiscountAmount = 0;
                    
                    $finalPrice = $originalPrice;
                    $appliedDiscountPercentage = 0;
                    $appliedDiscountAmount = 0;
                    $discountSource = 'none';
                    $codeDiscountPercentage = 0;

                    // Apply discount code if available
                    if ($discountCode) {
                        // Calculate discount code percentage
                        if ($discountCode->type === 'percentage') {
                            $codeDiscountPercentage = $discountCode->value;
                        } elseif ($discountCode->type === 'fixed') {
                            // Convert fixed amount to percentage for comparison
                            $codeDiscountPercentage = ($discountCode->value / $originalPrice) * 100;
                        }

                        // Feature packages have no built-in discount, so any code discount is better
                        // Check minimum order amount against original price
                        if (!$discountCode->min_order_amount || $originalPrice >= $discountCode->min_order_amount) {
                            $codeDiscountAmount = $discountCode->calculateDiscount($originalPrice);
                            $finalPrice = $originalPrice - $codeDiscountAmount;
                            $appliedDiscountPercentage = $codeDiscountPercentage;
                            $appliedDiscountAmount = $codeDiscountAmount;
                            $discountSource = 'code';
                        }
                    }

                    return [
                        'id' => $package->id,
                        'name' => $package->name,
                        'description' => $package->description,
                        'price' => $originalPrice,
                        'final_price' => $finalPrice,
                        'discount_percentage' => round($appliedDiscountPercentage, 2),
                        'discount_amount' => $appliedDiscountAmount,
                        'package_discount_percentage' => $packageDiscountPercentage,
                        'package_discount_amount' => $packageDiscountAmount,
                        'code_discount_percentage' => $discountSource === 'code' ? round($codeDiscountPercentage, 2) : 0,
                        'discount_source' => $discountSource,
                        'gift_sms_count' => $package->gift_sms_count,
                        'duration_days' => $package->duration_days,
                        'is_active' => $package->is_active,
                        'options' => $package->options->map(function ($option) {
                            return [
                                'id' => $option->id,
                                'name' => $option->name,
                                'details' => $option->details,
                            ];
                        }),
                        'formatted_price' => number_format($originalPrice / 10) . ' تومان',
                        'formatted_final_price' => number_format($finalPrice / 10) . ' تومان',
                        'formatted_you_save' => $appliedDiscountAmount > 0 
                            ? number_format($appliedDiscountAmount / 10) . ' تومان صرفه‌جویی' 
                            : null,
                        'created_at' => $package->created_at->toDateTimeString(),
                    ];
                });

            $response = [
                'success' => true,
                'data' => $packages
            ];

            // Add discount code info if applied
            if ($discountCode) {
                $response['discount_code'] = [
                    'code' => $discountCode->code,
                    'description' => $discountCode->description,
                    'type' => $discountCode->type,
                    'value' => $discountCode->value,
                    'applied' => true,
                ];
            } elseif ($request->filled('discount_code')) {
                $response['discount_code'] = [
                    'code' => $request->discount_code,
                    'applied' => false,
                    'message' => 'کد تخفیف نامعتبر یا منقضی شده است.',
                ];
            }

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Error fetching packages: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت لیست پکیج‌ها'
            ], 500);
        }
    }

    /**
     * جزئیات یک پکیج
     * GET /api/salons/{salon}/feature-packages/{id}
     */
    public function show($salon, $id)
    {
        try {
            // Verify salon belongs to user
            $user = auth()->user();
            $userSalon = $user->salons()->find($salon);
            
            if (!$userSalon) {
                return response()->json([
                    'success' => false,
                    'message' => 'سالن یافت نشد یا به شما تعلق ندارد'
                ], 403);
            }

            $package = Package::with('options')
                ->where('is_active', true)
                ->where('is_gift_only', false) // فقط پکیج‌های عادی قابل مشاهده هستند
                ->find($id);

            if (!$package) {
                return response()->json([
                    'success' => false,
                    'message' => 'پکیج یافت نشد'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $package->id,
                    'name' => $package->name,
                    'description' => $package->description,
                    'price' => (int) $package->price,
                    'gift_sms_count' => $package->gift_sms_count,
                    'duration_days' => $package->duration_days,
                    'is_active' => $package->is_active,
                    'options' => $package->options->map(function ($option) {
                        return [
                            'id' => $option->id,
                            'name' => $option->name,
                            'details' => $option->details,
                        ];
                    }),
                    'created_at' => $package->created_at->toDateTimeString(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching package details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت جزئیات پکیج'
            ], 500);
        }
    }

    /**
     * شروع فرآیند خرید پکیج (دقیقاً مثل purchase در ZarinpalController)
     * POST /api/salons/{salon}/feature-packages/{id}/purchase
     */
    public function purchase(Request $request, $salon, $id)
    {
        $request->validate([
            'callback_url' => ['required', 'regex:/^[a-zA-Z][a-zA-Z0-9+.-]*:\/\//'], // Allow custom schemes
            'discount_code' => 'nullable|string|exists:discount_codes,code',
        ]);

        $user = Auth::user();
        $packageId = $id;
        $callbackUrl = $request->callback_url;

        // Verify salon belongs to user
        $userSalon = $user->salons()->find($salon);
        
        if (!$userSalon) {
            return response()->json([
                'status' => 'NOK',
                'message' => 'سالن یافت نشد یا به شما تعلق ندارد'
            ], 403);
        }

        // SECURITY: Always calculate amount from database, never trust client input
        $package = Package::where('id', $packageId)
            ->where('is_active', true)
            ->where('is_gift_only', false) // پکیج‌های هدیه فقط از پنل ادمین قابل فعال‌سازی هستند
            ->first();

        if (!$package) {
            return response()->json([
                'status' => 'NOK',
                'message' => 'پکیج یافت نشد یا غیرفعال است'
            ], 404);
        }

        $amount = $package->price;
        $originalAmount = $amount;
        $discountPercentage = null;
        $discountCode = null;

        // Check and apply discount code if provided (same as SMS)
        if ($request->discount_code) {
            $discountCodeModel = \App\Models\DiscountCode::where('code', $request->discount_code)
                ->where('is_active', true)
                ->first();

            if (!$discountCodeModel) {
                return response()->json([
                    'status' => 'NOK',
                    'message' => 'کد تخفیف نامعتبر است.',
                ], 400);
            }

            // Check if discount code is valid (including time and usage limits)
            if (!$discountCodeModel->isValid()) {
                return response()->json([
                    'status' => 'NOK',
                    'message' => 'کد تخفیف منقضی شده یا غیرفعال است.',
                ], 400);
            }

            // SECURITY: Check if user can use this discount code based on filter criteria
            if (!$discountCodeModel->canUserUse($user)) {
                Log::warning('Unauthorized discount code usage attempt', [
                    'user_id' => $user->id,
                    'salon_id' => $salon,
                    'discount_code' => $request->discount_code,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
                
                return response()->json([
                    'status' => 'NOK',
                    'message' => 'شما مجاز به استفاده از این کد تخفیف نیستید.',
                ], 403);
            }

            // Check minimum order amount
            if ($discountCodeModel->min_order_amount && $amount < $discountCodeModel->min_order_amount) {
                return response()->json([
                    'status' => 'NOK',
                    'message' => "حداقل مبلغ سفارش برای استفاده از این کد تخفیف " . number_format($discountCodeModel->min_order_amount) . " تومان است.",
                ], 400);
            }

            // Apply discount using the model's calculation method
            $originalAmount = $amount;
            $discountAmount = $discountCodeModel->calculateDiscount($amount);
            $amount = $amount - $discountAmount;
            $discountCode = $request->discount_code;
            
            // Store discount info based on new structure
            if ($discountCodeModel->type === 'percentage') {
                $discountPercentage = $discountCodeModel->value;
            } else {
                $discountPercentage = ($discountAmount / $originalAmount) * 100;
            }
        }

        // Create a new Order record (same structure as SMS)
        $order = Order::create([
            'user_id' => $user->id,
            'salon_id' => $salon,
            'package_id' => $packageId,
            'sms_package_id' => null,
            'sms_count' => 0,
            'type' => 'feature',
            'amount' => $amount,
            'original_amount' => $originalAmount,
            'status' => 'pending',
            'discount_code' => $discountCode,
            'discount_percentage' => $discountPercentage,
        ]);

        // Create a new Transaction record associated with the Order
        $transaction = Transaction::create([
            'order_id' => $order->id,
            'gateway' => 'zarinpal',
            'amount' => $amount,
            'status' => 'pending',
            'description' => 'در انتظار پرداخت',
        ]);

        try {
            $invoice = new Invoice();
            $invoice->amount($amount);
            $invoice->detail('description', "خرید پکیج ویژگی: {$package->name} - سفارش {$order->id}");
            $invoice->detail('mobile', $user->mobile);

            // 1. Create the payment object and set the callback URL.
            // Support deep-link callbacks like return://ziboxcrm.ir through proxy.
            // Security: Only return://ziboxcrm.ir is allowed to prevent arbitrary redirects.
            $providedCallback = $callbackUrl;
            if (Str::startsWith($providedCallback, ['http://', 'https://']) === false) {
                // Validate that only our specific app scheme is allowed
                if ($providedCallback !== 'return://ziboxcrm.ir') {
                    Log::warning('Unauthorized callback scheme in package purchase', [
                        'callback_url' => $providedCallback,
                        'user_id' => $user->id,
                        'package_id' => $packageId,
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);
                    return response()->json([
                        'status' => 'NOK',
                        'message' => 'callback_url نامعتبر است.',
                    ], 400);
                }
                
                // Build a proxy callback URL
                $proxy = route('payment.callback_proxy') . '?app_return=' . urlencode($providedCallback);
                $payment = Payment::via('zarinpal')->callbackUrl($proxy);
            } else {
                $payment = Payment::via('zarinpal')->callbackUrl($providedCallback);
            }

            // 2. Prepare the invoice and the transaction callback.
            $payment->purchase(
                $invoice,
                function ($driver, $transactionId) use ($transaction) {
                    // Store the gateway transaction ID (Authority)
                    $transaction->update(['transaction_id' => $transactionId]);
                }
            );

            // 3. Redirect to the payment gateway.
            $redirectUrl = $payment->pay()->getAction();

            return response()->json([
                'status' => 'OK',
                'authority' => $transaction->transaction_id,
                'payment_url' => $redirectUrl,
                'order_id' => $order->id,
                'amount' => $amount,
                'original_amount' => $originalAmount,
                'package_name' => $package->name,
                'discount_applied' => !is_null($discountCode),
                'discount_amount' => $originalAmount - $amount,
            ]);
        } catch (InvalidPaymentException $exception) {
            Log::error('Payment creation failed', [
                'error' => $exception->getMessage(),
                'order_id' => $order->id,
                'package_id' => $packageId,
                'amount' => $amount,
            ]);

            return response()->json([
                'status' => 'NOK',
                'message' => 'خطا در ایجاد لینک پرداخت.',
            ], 500);
        }
    }

    /**
     * تایید پرداخت (دقیقاً مثل verify در ZarinpalController)
     * POST /api/salons/{salon}/feature-packages/verify
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
                                      $query->where('user_id', $user->id)
                                            ->where('type', 'feature');
                                  })
                                  ->first();

        if (!$transaction) {
            return response()->json([
                'status' => 'NOK',
                'message' => 'تراکنش یافت نشد یا متعلق به این کاربر نیست.',
            ], 404);
        }

        $order = $transaction->order;

        // If the order is already completed, this transaction is a duplicate attempt
        if ($order->status === 'completed') {
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
                // SECURITY: Verify amount matches original transaction before processing
                $expectedAmount = $transaction->amount;
                
                // Verify the payment with Zarinpal
                $receipt = Payment::via('zarinpal')
                    ->amount($expectedAmount) // Use stored amount, not user input
                    ->transactionId($authority)
                    ->verify();

                $referenceId = $receipt->getReferenceId();

                // Update the successful transaction
                $transaction->update([
                    'status' => 'completed',
                    'reference_id' => $referenceId,
                    'description' => 'پرداخت با موفقیت تایید شد',
                ]);

                // Dispatch the event to activate the package (same pattern as SMS)
                event(new FeaturePackagePurchased($order, $transaction));

                Log::info('FeaturePackagePurchased event dispatched.', [
                    'order_id' => $order->id,
                    'transaction_id' => $transaction->id,
                    'amount' => $expectedAmount,
                    'reference_id' => $referenceId,
                ]);
            });

            // Reload the order and transaction to get the latest status after the listener
            $order->refresh();
            $transaction->refresh();

            // Get the activated user package for response (similar to SMS balance)
            $userPackage = UserPackage::where('order_id', $order->id)
                ->where('status', 'active')
                ->with(['package', 'salon'])
                ->first();

            return response()->json([
                'status' => 'OK',
                'message' => 'پرداخت با موفقیت تایید شد.',
                'package_name' => $order->package->name ?? 'نامشخص',
                'salon_name' => $userPackage->salon->name ?? 'نامشخص',
                'expires_at' => $userPackage->expires_at ?? null,
                'amount_paid' => (int) $order->amount,
                'reference_id' => $transaction->reference_id,
                'order_id' => $order->id,
                'package_id' => $order->package_id,
                'user_package_id' => $userPackage->id ?? null,
            ]);
            
        } catch (InvalidPaymentException $exception) {
            Log::error('Payment verification failed', [
                'error' => $exception->getMessage(),
                'authority' => $authority,
                'order_id' => $order->id,
            ]);

            // Update transaction as failed
            $transaction->update([
                'status' => 'failed',
                'description' => 'تایید پرداخت ناموفق: ' . $exception->getMessage(),
            ]);

            return response()->json([
                'status' => 'NOK',
                'message' => 'تایید پرداخت ناموفق بود.',
            ], 400);
        } catch (\Exception $e) {
            Log::error('Verification process failed', [
                'error' => $e->getMessage(),
                'authority' => $authority,
                'order_id' => $order->id,
            ]);

            return response()->json([
                'status' => 'NOK',
                'message' => 'خطا در تایید پرداخت.',
            ], 500);
        }
    }

    /**
     * دریافت پکیج فعال سالن 
     * GET /api/salons/{salon}/feature-packages/my-package
     */
    public function myPackage($salon)
    {
        try {
            $user = auth()->user();
            
            // Verify salon belongs to user
            $userSalon = $user->salons()->find($salon);
            
            if (!$userSalon) {
                return response()->json([
                    'success' => false,
                    'message' => 'سالن یافت نشد یا به شما تعلق ندارد'
                ], 403);
            }
            
            $userPackage = UserPackage::with(['package.options', 'order', 'salon'])
                ->where('user_id', $user->id)
                ->where('salon_id', $salon)
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->first();

            if (!$userPackage) {
                return response()->json([
                    'success' => false,
                    'message' => 'این سالن پکیج فعالی ندارد'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $userPackage->id,
                    'salon' => [
                        'id' => $userPackage->salon->id,
                        'name' => $userPackage->salon->name,
                    ],
                    'package' => [
                        'id' => $userPackage->package->id,
                        'name' => $userPackage->package->name,
                        'description' => $userPackage->package->description,
                        'options' => $userPackage->package->options->map(function ($option) {
                            return [
                                'id' => $option->id,
                                'name' => $option->name,
                                'details' => $option->details,
                            ];
                        }),
                    ],
                    'amount_paid' => (int) $userPackage->amount_paid,
                    'status' => $userPackage->status,
                    'purchased_at' => $userPackage->purchased_at->toDateTimeString(),
                    'expires_at' => $userPackage->expires_at->toDateTimeString(),
                    'days_remaining' => now()->diffInDays($userPackage->expires_at),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching salon package: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت اطلاعات پکیج'
            ], 500);
        }
    }

    /**
     * تاریخچه خریدهای سالن
     * GET /api/salons/{salon}/feature-packages/my-packages
     */
    public function myPackages($salon)
    {
        try {
            $user = auth()->user();
            
            // Verify salon belongs to user
            $userSalon = $user->salons()->find($salon);
            
            if (!$userSalon) {
                return response()->json([
                    'success' => false,
                    'message' => 'سالن یافت نشد یا به شما تعلق ندارد'
                ], 403);
            }
            
            $userPackages = UserPackage::with(['package', 'order', 'salon'])
                ->where('user_id', $user->id)
                ->where('salon_id', $salon)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($userPackage) {
                    return [
                        'id' => $userPackage->id,
                        'salon' => [
                            'id' => $userPackage->salon ? $userPackage->salon->id : null,
                            'name' => $userPackage->salon ? $userPackage->salon->name : 'نامشخص',
                        ],
                        'package_name' => $userPackage->package ? $userPackage->package->name : 'نامشخص',
                        'amount_paid' => (int) $userPackage->amount_paid,
                        'status' => $userPackage->status,
                        'purchased_at' => $userPackage->purchased_at ? $userPackage->purchased_at->toDateTimeString() : null,
                        'expires_at' => $userPackage->expires_at ? $userPackage->expires_at->toDateTimeString() : null,
                        'is_active' => $userPackage->status === 'active' && $userPackage->expires_at > now(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $userPackages
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user packages history: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت تاریخچه خریدها'
            ], 500);
        }
    }
}
