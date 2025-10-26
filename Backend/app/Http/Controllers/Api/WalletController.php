<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReferralSetting;
use App\Models\WalletTransaction;
use App\Models\Package;
use App\Models\SmsPackage;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    /**
     * Get wallet information with statistics
     */
    public function getWalletInfo(Request $request)
    {
        try {
            $user = $request->user();
            
            $stats = [
                'balance' => $user->wallet_balance ?? 0,
                'formatted_balance' => number_format(($user->wallet_balance ?? 0) / 10) . ' تومان',
                'total_earned' => WalletTransaction::where('user_id', $user->id)
                    ->where('amount', '>', 0)
                    ->sum('amount'),
                'total_spent' => abs(WalletTransaction::where('user_id', $user->id)
                    ->where('amount', '<', 0)
                    ->sum('amount')),
                'referral_earnings' => WalletTransaction::where('user_id', $user->id)
                    ->whereIn('type', ['referral_reward', 'order_reward'])
                    ->sum('amount'),
                'transactions_count' => WalletTransaction::where('user_id', $user->id)->count(),
            ];
            
            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در دریافت اطلاعات کیف پول: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get wallet transactions
     */
    public function getTransactions(Request $request)
    {
        try {
            $user = $request->user();
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 20);
            $type = $request->get('type'); // filter by type
            
            $query = $user->walletTransactions();
            
            if ($type) {
                $query->where('type', $type);
            }
            
            $transactions = $query->paginate($perPage, ['*'], 'page', $page);
            
            // Add display attributes
            $transactions->getCollection()->transform(function ($transaction) {
                $transaction->type_display = $transaction->getTypeDisplayAttribute();
                $transaction->status_display = $transaction->getStatusDisplayAttribute();
                $transaction->formatted_amount = number_format(abs($transaction->amount)) . ' تومان';
                $transaction->is_credit = $transaction->isCredit();
                return $transaction;
            });
            
            return response()->json([
                'status' => 'success',
                'data' => $transactions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در دریافت تراکنش‌ها: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add credit to wallet (for testing or admin purposes)
     */
    public function addCredit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $user = $request->user();
            
            DB::beginTransaction();

            // Add to wallet balance
            $user->increment('wallet_balance', $request->amount);

            // Record transaction
            WalletTransaction::create([
                'user_id' => $user->id,
                'amount' => $request->amount,
                'type' => 'manual_credit',
                'description' => $request->description ?? 'شارژ دستی کیف پول',
                'status' => 'completed',
                'meta_data' => [
                    'added_by' => 'user',
                    'method' => 'manual'
                ]
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'موجودی با موفقیت اضافه شد',
                'data' => [
                    'new_balance' => $user->fresh()->wallet_balance,
                    'added_amount' => $request->amount
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در اضافه کردن موجودی: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available packages for purchase
     */
    public function getAvailablePackages(Request $request)
    {
        try {
            $user = $request->user();
            
            $packages = Package::where('is_active', true)
                ->orderBy('price', 'asc')
                ->get()
                ->map(function ($package) use ($user) {
                    return [
                        'id' => $package->id,
                        'name' => $package->name,
                        'price' => $package->price,
                        'formatted_price' => number_format($package->price / 10) . ' تومان',
                        'description' => $package->description,
                        'can_afford' => $user->wallet_balance >= $package->price,
                        'shortage' => $user->wallet_balance < $package->price 
                            ? $package->price - $user->wallet_balance 
                            : 0,
                    ];
                });

            $smsPackages = SmsPackage::where('is_active', true)
                ->orderBy('price', 'asc')
                ->get()
                ->map(function ($package) use ($user) {
                    $price = $package->discount_price ?: $package->price;
                    return [
                        'id' => $package->id,
                        'name' => $package->name,
                        'price' => $price,
                        'formatted_price' => number_format($price / 10) . ' تومان',
                        'sms_count' => $package->sms_count,
                        'description' => $package->description,
                        'can_afford' => $user->wallet_balance >= $price,
                        'shortage' => $user->wallet_balance < $price 
                            ? $price - $user->wallet_balance 
                            : 0,
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'packages' => $packages,
                    'sms_packages' => $smsPackages,
                    'user_balance' => $user->wallet_balance,
                    'formatted_balance' => number_format($user->wallet_balance / 10) . ' تومان',
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در دریافت پکیج‌ها: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Purchase package using wallet
     */
    public function purchasePackage(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'package_id' => 'required|exists:packages,id',
                'salon_id' => 'required|exists:salons,id',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first()
                ], 422);
            }
            
            $user = $request->user();
            
            // Check if salon belongs to the user
            $salon = $user->salons()->find($request->salon_id);
            if (!$salon) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'سالن انتخابی متعلق به شما نیست.'
                ], 403);
            }
            
            $package = Package::findOrFail($request->package_id);
            
            if (!$user->hasSufficientBalance($package->price)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'موجودی کیف پول کافی نیست.',
                    'data' => [
                        'required' => $package->price,
                        'available' => $user->wallet_balance,
                        'shortage' => $package->price - $user->wallet_balance,
                    ]
                ], 400);
            }
            
            DB::beginTransaction();
            
            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'salon_id' => $request->salon_id,
                'package_id' => $package->id,
                'amount' => $package->price,
                'sms_count' => 0, // برای پکیج امکانات پیامک نداریم
                'status' => 'completed',
            ]);
            
            // Deduct from wallet
            $transaction = $user->deductFromWallet(
                $package->price,
                "خرید پکیج {$package->name} - سفارش: {$order->id}",
                WalletTransaction::TYPE_PACKAGE_PURCHASE,
                $order->id
            );
            
            // Process purchase for referral rewards
            $user->processPurchaseForReferral($package->price);
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'پکیج با موفقیت خریداری شد.',
                'data' => [
                    'order_id' => $order->id,
                    'package_name' => $package->name,
                    'amount_paid' => $package->price,
                    'new_balance' => $user->fresh()->wallet_balance,
                    'transaction_id' => $transaction->id,
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در خرید پکیج: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Purchase SMS package using wallet
     */
    public function purchaseSmsPackage(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'sms_package_id' => 'required|exists:sms_packages,id',
                'salon_id' => 'required|exists:salons,id',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first()
                ], 422);
            }
            
            $user = $request->user();
            
            // Check if salon belongs to the user
            $salon = $user->salons()->find($request->salon_id);
            if (!$salon) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'سالن انتخابی متعلق به شما نیست.'
                ], 403);
            }
            
            $smsPackage = SmsPackage::findOrFail($request->sms_package_id);
            $price = $smsPackage->discount_price ?: $smsPackage->price;
            
            if (!$user->hasSufficientBalance($price)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'موجودی کیف پول کافی نیست.',
                    'data' => [
                        'required' => $price,
                        'available' => $user->wallet_balance,
                        'shortage' => $price - $user->wallet_balance,
                    ]
                ], 400);
            }
            
            DB::beginTransaction();
            
            // Create order for SMS package
            $order = Order::create([
                'user_id' => $user->id,
                'salon_id' => $request->salon_id,
                'sms_package_id' => $smsPackage->id,
                'amount' => $price,
                'sms_count' => $smsPackage->sms_count,
                'status' => 'completed',
            ]);
            
            // Deduct from wallet
            $transaction = $user->deductFromWallet(
                $price,
                "خرید پکیج پیامک {$smsPackage->name} - سفارش: {$order->id}",
                WalletTransaction::TYPE_SMS_PACKAGE_PURCHASE,
                $order->id
            );
            
            // Add SMS credits to salon
            $salon->increment('sms_balance', $smsPackage->sms_count);
            
            // Process purchase for referral rewards
            $user->processPurchaseForReferral($price);
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'پکیج پیامک با موفقیت خریداری شد.',
                'data' => [
                    'order_id' => $order->id,
                    'package_name' => $smsPackage->name,
                    'sms_count' => $smsPackage->sms_count,
                    'amount_paid' => $price,
                    'new_balance' => $user->fresh()->wallet_balance,
                    'transaction_id' => $transaction->id,
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در خرید پکیج پیامک: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Purchase feature package using wallet (new method for wallet payment)
     */
    public function purchaseFeaturePackageWithWallet(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'package_id' => 'required|exists:packages,id',
                'salon_id' => 'required|exists:salons,id',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first()
                ], 422);
            }
            
            $user = $request->user();
            
            // Check if salon belongs to the user
            $salon = $user->salons()->find($request->salon_id);
            if (!$salon) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'سالن انتخابی متعلق به شما نیست.'
                ], 403);
            }
            
            $package = Package::findOrFail($request->package_id);
            
            if (!$user->hasSufficientBalance($package->price)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'موجودی کیف پول کافی نیست.',
                    'data' => [
                        'required' => $package->price,
                        'available' => $user->wallet_balance,
                        'shortage' => $package->price - $user->wallet_balance,
                    ]
                ], 400);
            }
            
            DB::beginTransaction();
            
            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'salon_id' => $request->salon_id,
                'package_id' => $package->id,
                'amount' => $package->price,
                'sms_count' => 0,
                'status' => 'completed',
            ]);
            
            // Deduct from wallet
            $transaction = $user->deductFromWallet(
                $package->price,
                "خرید پکیج امکانات {$package->name} - سفارش: {$order->id}",
                WalletTransaction::TYPE_PACKAGE_PURCHASE,
                $order->id
            );
            
            // Activate package for salon
            $this->activateFeaturePackage($order, $package);
            
            // Process purchase for referral rewards
            $user->processPurchaseForReferral($package->price);
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'پکیج امکانات با موفقیت خریداری شد.',
                'data' => [
                    'order_id' => $order->id,
                    'package_name' => $package->name,
                    'amount_paid' => $package->price,
                    'new_balance' => $user->fresh()->wallet_balance,
                    'transaction_id' => $transaction->id,
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در خرید پکیج امکانات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activate feature package for salon
     */
    private function activateFeaturePackage($order, $package)
    {
        // Deactivate all previous active packages for this user and salon
        \App\Models\UserPackage::where('user_id', $order->user_id)
            ->where('salon_id', $order->salon_id)
            ->where('status', 'active')
            ->update([
                'status' => 'expired',
                'updated_at' => now()
            ]);

        // Create or update the new user package for this salon
        $durationDays = $package->duration_days ?? 365;
        
        \App\Models\UserPackage::updateOrCreate(
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
                'expires_at' => \Carbon\Carbon::now()->addDays($durationDays)
            ]
        );

        // If package has gift SMS, increment salon SMS balance
        if ($package && $package->gift_sms_count > 0) {
            $salonSmsBalance = \App\Models\SalonSmsBalance::firstOrCreate(
                ['salon_id' => $order->salon_id],
                ['balance' => 0]
            );
            $salonSmsBalance->increment('balance', $package->gift_sms_count);

            // Create SMS transaction record for gift
            // \App\Models\SmsTransaction::create([
            //     'salon_id' => $order->salon_id,
            //     'type' => 'gift',
            //     'amount' => $package->gift_sms_count,
            //     'description' => "هدیه بسته امکانات - سفارش {$order->id}",
            //     'status' => 'completed',
            // ]);
        }
    }

    /**
     * Charge wallet via ZarinPal
     */
    public function chargeWallet(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:10000|max:50000000', // حداقل 10 هزار تومان، حداکثر 50 میلیون
                'description' => 'nullable|string|max:255',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first()
                ], 422);
            }
            
            $user = $request->user();
            $amount = $request->amount;
            $description = $request->description ?? 'شارژ کیف پول';
            
            DB::beginTransaction();
            
            // Create a pending wallet charge order
            $order = Order::create([
                'user_id' => $user->id,
                'salon_id' => null, // شارژ کیف پول مربوط به کاربر است نه سالن خاص
                'type' => 'wallet_charge',
                'amount' => $amount,
                'sms_count' => 0,
                'status' => 'pending',
            ]);
            
            // Create wallet transaction record (pending)
            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'wallet_charge',
                'amount' => $amount,
                'status' => 'pending',
                'description' => $description,
                'order_id' => $order->id,
                'metadata' => [
                    'payment_method' => 'zarinpal',
                    'order_id' => $order->id,
                ]
            ]);
            
            DB::commit();
            
            // Here you would integrate with ZarinPal
            // For now, returning the data needed for payment gateway
            return response()->json([
                'status' => 'success',
                'message' => 'درخواست شارژ کیف پول ایجاد شد.',
                'data' => [
                    'order_id' => $order->id,
                    'transaction_id' => $transaction->id,
                    'amount' => $amount,
                    'description' => $description,
                    'redirect_url' => url("/api/wallet/charge/verify/{$order->id}"),
                    // Add ZarinPal specific data here when implemented
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در ایجاد درخواست شارژ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify wallet charge payment
     */
    public function verifyWalletCharge(Request $request, $orderId)
    {
        try {
            $order = Order::where('id', $orderId)
                ->where('type', 'wallet_charge')
                ->where('status', 'pending')
                ->firstOrFail();
            
            $user = $order->user;
            
            // Here you would verify payment with ZarinPal
            // For now, assuming payment is successful
            $paymentVerified = true; // Replace with actual ZarinPal verification
            
            if ($paymentVerified) {
                DB::beginTransaction();
                
                // Update order status
                $order->update(['status' => 'completed']);
                
                // Add to wallet balance
                $user->increment('wallet_balance', $order->amount);
                
                // Update transaction status
                $transaction = WalletTransaction::where('order_id', $order->id)->first();
                if ($transaction) {
                    $transaction->update(['status' => 'completed']);
                }
                
                DB::commit();
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'کیف پول با موفقیت شارژ شد.',
                    'data' => [
                        'order_id' => $order->id,
                        'amount_charged' => $order->amount,
                        'new_balance' => $user->fresh()->wallet_balance,
                    ]
                ]);
            } else {
                // Payment failed
                $order->update(['status' => 'failed']);
                
                $transaction = WalletTransaction::where('order_id', $order->id)->first();
                if ($transaction) {
                    $transaction->update(['status' => 'failed']);
                }
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'پرداخت ناموفق بود.'
                ], 400);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در تایید پرداخت: ' . $e->getMessage()
            ], 500);
        }
    }
}
