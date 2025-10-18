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
    public function show(WalletPackage $package)
    {
        try {
            if (!$package->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'این پکیج در حال حاضر قابل دسترس نیست.'
                ], 404);
            }

            $data = [
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
    public function purchase(Request $request, WalletPackage $package)
    {
        try {
            $user = $request->user();

            if (!$package->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'این پکیج در حال حاضر قابل دسترس نیست.'
                ], 400);
            }

            DB::beginTransaction();

            // Create order record
            $order = Order::create([
                'user_id' => $user->id,
                'type' => 'wallet_package',
                'item_id' => $package->id,
                'item_title' => $package->title,
                'amount' => $package->final_price,
                'original_amount' => $package->price,
                'discount_amount' => $package->discount_amount,
                'status' => 'pending',
                'payment_method' => $request->payment_method ?: 'online',
                'sms_count' => 0, // Default for wallet package
                'metadata' => [
                    'wallet_amount' => $package->amount,
                    'package_details' => $package->toArray()
                ]
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'سفارش خرید پکیج ایجاد شد.',
                'data' => [
                    'order_id' => $order->id,
                    'amount' => $package->final_price,
                    'wallet_amount' => $package->amount,
                    'payment_url' => route('payment.gateway', $order->id),
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در ایجاد سفارش: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process successful payment and charge wallet
     */
    public function processPayment(Request $request)
    {
        try {
            $order = Order::findOrFail($request->order_id);
            $user = $order->user;

            if ($order->status !== 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'وضعیت سفارش قابل پردازش نیست.'
                ], 400);
            }

            DB::beginTransaction();

            // Update order status
            $order->update([
                'status' => 'completed',
                'payment_status' => 'paid',
                'paid_at' => now()
            ]);

            // Charge user wallet
            $walletAmount = $order->metadata['wallet_amount'];
            
            WalletTransaction::createAndUpdateBalance([
                'user_id' => $user->id,
                'type' => WalletTransaction::TYPE_PACKAGE_PURCHASE,
                'amount' => $walletAmount,
                'description' => "شارژ کیف پول - {$order->item_title}",
                'order_id' => $order->id,
                'status' => WalletTransaction::STATUS_COMPLETED
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'کیف پول شما با موفقیت شارژ شد.',
                'data' => [
                    'charged_amount' => $walletAmount,
                    'new_balance' => $user->fresh()->wallet_balance,
                    'formatted_charged_amount' => number_format($walletAmount) . ' ریال',
                    'formatted_new_balance' => number_format($user->fresh()->wallet_balance) . ' ریال',
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در پردازش پرداخت: ' . $e->getMessage()
            ], 500);
        }
    }
}
