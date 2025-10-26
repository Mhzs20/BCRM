<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminTransactionController extends Controller
{
    /**
     * Display a paginated list of all payment transactions (orders + gateway transaction info).
     */
    public function index(Request $request)
    {
        $query = Order::query()
            ->with([
                'salon:id,name,mobile,user_id',
                'salon.user:id,mobile',
                'user:id,name',
                'smsPackage:id,name',
                'package:id,name',
                'transactions' => function($q){ $q->latest(); }
            ])
            ->latest();

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('gateway')) {
            $query->whereHas('transactions', function($q) use ($request){
                $q->where('gateway', $request->gateway);
            });
        }
        if ($request->filled('salon')) {
            $query->whereHas('salon', function($q) use ($request){
                $q->where('name','like','%'.$request->salon.'%');
            });
        }
        if ($request->filled('ref')) {
            $query->whereHas('transactions', function($q) use ($request){
                $q->where('reference_id','like','%'.$request->ref.'%')
                  ->orWhere('transaction_id','like','%'.$request->ref.'%');
            });
        }

        $orders = $query->paginate(20)->appends($request->query());

        // Aggregations - جداگانه برای SMS و Feature
        $baseAggQuery = Order::where('status','completed');  // تغییر از 'paid' به 'completed'
        $now = now();

        $daily = (clone $baseAggQuery)
            ->whereDate('created_at', $now->toDateString())
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount),0) as total_amount, COALESCE(SUM(sms_count),0) as total_sms')
            ->first();

        // Weekly (start of week to now)
        $weekly = (clone $baseAggQuery)
            ->whereBetween('created_at', [$now->copy()->startOfWeek(), $now])
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount),0) as total_amount, COALESCE(SUM(sms_count),0) as total_sms')
            ->first();

        // Monthly (start of month to now)
        $monthly = (clone $baseAggQuery)
            ->whereBetween('created_at', [$now->copy()->startOfMonth(), $now])
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount),0) as total_amount, COALESCE(SUM(sms_count),0) as total_sms')
            ->first();

        // Grouped breakdown based on requested period (period=daily|weekly|monthly)
        $period = $request->get('period','daily');
        $groupData = collect();
        if ($period === 'daily') {
            $groupData = Order::selectRaw('DATE(created_at) as label, COUNT(*) as count, SUM(amount) as total_amount, SUM(sms_count) as total_sms')
                ->where('created_at','>=',$now->copy()->subDays(14))
                ->where('status','paid')
                ->groupBy('label')
                ->orderBy('label','desc')
                ->limit(14)
                ->get();
        } elseif ($period === 'weekly') {
            $groupData = Order::selectRaw('YEARWEEK(created_at,1) as label, COUNT(*) as count, SUM(amount) as total_amount, SUM(sms_count) as total_sms')
                ->where('created_at','>=',$now->copy()->subWeeks(8))
                ->where('status','paid')
                ->groupBy('label')
                ->orderBy('label','desc')
                ->limit(8)
                ->get();
        } else { // monthly
            $groupData = Order::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as label, COUNT(*) as count, SUM(amount) as total_amount, SUM(sms_count) as total_sms')
                ->where('created_at','>=',$now->copy()->subMonths(6))
                ->where('status','paid')
                ->groupBy('label')
                ->orderBy('label','desc')
                ->limit(6)
                ->get();
        }

        return view('admin.transactions.index', [
            'orders' => $orders,
            'filters' => $request->only(['status','type','gateway','salon','ref','period']),
            'period' => $period,
            'stats' => [
                'daily' => $daily,
                'weekly' => $weekly,
                'monthly' => $monthly,
            ],
            'groupData' => $groupData,
        ]);
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,completed,failed'   
        ]);

        $oldStatus = $order->status;
        $order->status = $request->status;
        $order->save();

        try {
            // If order is feature package and status changed to completed, activate the package
            if ($order->type === 'feature' && $order->package_id && $request->status === 'completed' && $oldStatus !== 'completed') {
                $this->activateFeaturePackage($order);
            }

            // If order is SMS package and status changed to completed, activate the SMS balance
            if ($order->sms_package_id && $request->status === 'completed' && $oldStatus !== 'completed') {
                $this->activateSmsPackage($order);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در فعال‌سازی: ' . $e->getMessage()
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'وضعیت سفارش با موفقیت به‌روزرسانی شد.',
            'new_status' => $order->status
        ]);
    }

    /**
     * Update transaction status
     */
    public function updateTransactionStatus(Request $request, Transaction $transaction)
    {
        $request->validate([
            'status' => 'required|in:pending,completed,failed,expired'
        ]);

        $transaction->status = $request->status;
        $transaction->save();

        return response()->json([
            'success' => true,
            'message' => 'وضعیت تراکنش با موفقیت به‌روزرسانی شد.',
            'new_status' => $transaction->status
        ]);
    }

    /**
     * Activate SMS package for order
     */
    private function activateSmsPackage($order)
    {
        // Increment the SalonSmsBalance for the associated salon
        $salonSmsBalance = \App\Models\SalonSmsBalance::firstOrCreate(
            ['salon_id' => $order->salon_id],
            ['balance' => 0]
        );
        $salonSmsBalance->increment('balance', $order->sms_count);

        // Create SMS transaction record for purchase history
        // \App\Models\SmsTransaction::create([
        //     'salon_id' => $order->salon_id,
        //     'sms_package_id' => $order->sms_package_id,
        //     'type' => 'purchase',
        //     'amount' => $order->sms_count,
        //     'description' => "خرید بسته پیامک - سفارش {$order->id}",
        //     'status' => 'completed',
        // ]);
    }

    /**
     * Activate feature package for order
     */
    private function activateFeaturePackage($order)
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
        $package = $order->package;
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
}
