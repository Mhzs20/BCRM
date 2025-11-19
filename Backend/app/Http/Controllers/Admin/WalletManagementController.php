<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WalletManagementController extends Controller
{
    /**
     * Display wallet management page
     */
    public function index()
    {
        return view('admin.wallet.charge');
    }

    /**
     * Create wallet charge request for user
     */
    public function createChargeRequest(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'amount' => 'required|numeric|min:10000|max:50000000',
                'description' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $user = User::findOrFail($request->user_id);
            $amount = $request->amount;
            $description = $request->description ?? 'شارژ کیف پول توسط مدیر';

            DB::beginTransaction();

            // Create order for wallet charge
            $order = Order::create([
                'user_id' => $user->id,
                'salon_id' => null,
                'type' => 'wallet_charge',
                'amount' => $amount,
                'sms_count' => 0,
                'status' => 'pending',
                'metadata' => [
                    'description' => $description,
                ],
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
                    'created_by_admin' => true,
                    'admin_id' => auth()->id(),
                ]
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'درخواست شارژ با موفقیت ایجاد شد',
                'data' => [
                    'order_id' => $order->id,
                    'transaction_id' => $transaction->id,
                    'amount' => $amount,
                    'user_name' => $user->name,
                    'payment_url' => url("/admin/wallet/charge/payment/{$order->id}"),
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد درخواست شارژ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show payment page for wallet charge
     */
    public function showPaymentPage($orderId)
    {
        try {
            $order = Order::where('id', $orderId)
                ->where('type', 'wallet_charge')
                ->where('status', 'pending')
                ->with('user')
                ->firstOrFail();

            return view('admin.wallet.payment', compact('order'));

        } catch (\Exception $e) {
            return redirect()->route('admin.wallet.charge')->with('error', 'سفارش یافت نشد');
        }
    }

    /**
     * Process payment (simulate or integrate with ZarinPal)
     */
    public function processPayment(Request $request, $orderId)
    {
        try {
            $order = Order::where('id', $orderId)
                ->where('type', 'wallet_charge')
                ->where('status', 'pending')
                ->firstOrFail();

            $user = $order->user;

            // For demo purposes, we'll simulate successful payment
            // In real implementation, integrate with ZarinPal here
            $paymentSuccessful = true;

            if ($paymentSuccessful) {
                DB::beginTransaction();

                // Update order status
                $order->update([
                    'status' => 'completed',
                    'payment_ref_id' => 'DEMO_' . time(), // Replace with actual ref ID
                ]);

                // Add to wallet balance
                $user->increment('wallet_balance', $order->amount);

                // Update transaction status
                $transaction = WalletTransaction::where('order_id', $order->id)->first();
                if ($transaction) {
                    $transaction->update([
                        'status' => 'completed',
                        'metadata' => array_merge($transaction->metadata ?? [], [
                            'payment_ref_id' => 'DEMO_' . time(),
                            'completed_at' => now()->toISOString(),
                        ])
                    ]);
                }

                DB::commit();

                return redirect()->route('admin.wallet.charge')->with('success', 
                    "کیف پول {$user->name} با مبلغ " . number_format($order->amount) . " ریال شارژ شد"
                );
            } else {
                // Payment failed
                $order->update(['status' => 'failed']);
                
                $transaction = WalletTransaction::where('order_id', $order->id)->first();
                if ($transaction) {
                    $transaction->update(['status' => 'failed']);
                }

                return redirect()->route('admin.wallet.charge')->with('error', 'پرداخت ناموفق بود');
            }

        } catch (\Exception $e) {
            return redirect()->route('admin.wallet.charge')->with('error', 'خطا در پردازش پرداخت: ' . $e->getMessage());
        }
    }

    /**
     * Get wallet charge history
     */
    public function getChargeHistory(Request $request)
    {
        try {
            $query = Order::where('type', 'wallet_charge')
                ->with(['user:id,name,mobile'])
                ->orderBy('created_at', 'desc');

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            $orders = $query->paginate($request->get('per_page', 20));

            // Transform data for display
            $orders->getCollection()->transform(function ($order) {
                return [
                    'id' => $order->id,
                    'user_name' => $order->user->name ?? 'نامشخص',
                    'user_mobile' => $order->user->mobile ?? 'نامشخص',
                    'amount' => $order->amount,
                    'formatted_amount' => number_format($order->amount) . ' ریال',
                    'status' => $order->status,
                    'status_display' => $this->getStatusDisplay($order->status),
                    'created_at' => $order->created_at->format('Y/m/d H:i'),
                    'persian_date' => verta($order->created_at)->format('Y/m/d H:i'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $orders
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت تاریخچه: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get status display text
     */
    private function getStatusDisplay($status)
    {
        $statuses = [
            'pending' => 'در انتظار پرداخت',
            'completed' => 'تکمیل شده',
            'failed' => 'ناموفق',
            'cancelled' => 'لغو شده',
        ];

        return $statuses[$status] ?? $status;
    }

    /**
     * Manual wallet adjustment (direct credit/debit)
     */
    public function manualAdjustment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'amount' => 'required|numeric',
                'type' => 'required|in:credit,debit',
                'description' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $user = User::findOrFail($request->user_id);
            $amount = abs($request->amount);
            $isCredit = $request->type === 'credit';
            $finalAmount = $isCredit ? $amount : -$amount;

            // Check if debit amount is not more than current balance
            if (!$isCredit && $user->wallet_balance < $amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'موجودی کیف پول کاربر کافی نیست'
                ], 400);
            }

            DB::beginTransaction();

            // Update wallet balance
            if ($isCredit) {
                $user->increment('wallet_balance', $amount);
            } else {
                $user->decrement('wallet_balance', $amount);
            }

            // Create transaction record
            WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'admin_adjustment',
                'amount' => $finalAmount,
                'status' => 'completed',
                'description' => $request->description,
                'metadata' => [
                    'adjustment_type' => $request->type,
                    'admin_id' => auth()->id(),
                    'admin_name' => auth()->user()->name,
                ]
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تعدیل کیف پول با موفقیت انجام شد',
                'data' => [
                    'user_name' => $user->name,
                    'amount' => $finalAmount,
                    'new_balance' => $user->fresh()->wallet_balance,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'خطا در تعدیل کیف پول: ' . $e->getMessage()
            ], 500);
        }
    }
}