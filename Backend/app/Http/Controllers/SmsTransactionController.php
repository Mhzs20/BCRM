<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Transaction;
use App\Models\Salon;
use App\Models\SmsTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Morilog\Jalali\Jalalian;

class SmsTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Salon $salon = null)
    {
        $user = Auth::user();
        
        // Verify user has access to this salon
        if ($salon && !$user->salons()->where('id', $salon->id)->exists()) {
            return response()->json(['error' => 'Unauthorized access to salon'], 403);
        }

        $query = SmsTransaction::query()
            ->with(['smsPackage', 'customer:id,name,phone_number', 'appointment:id,appointment_date,start_time,end_time'])
            ->latest();

        // Filter by salon if provided in route
        if ($salon) {
            $query->where('salon_id', $salon->id);
            // For salon-specific requests, show only purchase transactions
            $query->where('type', 'purchase');
        } else {
            // If no specific salon, filter by user's salons
            $query->where('user_id', $user->id);
        }

        // Additional filters (these can override the default purchase filter if needed)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by SMS type if provided
        if ($request->filled('sms_type')) {
            $query->where('sms_type', $request->sms_type);
        }

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range if provided
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Filter by amount range if provided
        if ($request->filled('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }
        if ($request->filled('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }

        $transactions = $query->paginate($request->get('per_page', 20));

        $formattedTransactions = $transactions->getCollection()->map(function ($transaction) {
            $data = [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'sms_type' => $transaction->sms_type,
                'status' => $transaction->status,
                'amount' => $transaction->amount,
                'sms_count' => $transaction->sms_count,
                'receptor' => $transaction->receptor,
                'content' => $transaction->content,
                'description' => $transaction->description,
                'reference_id' => $transaction->reference_id,
                'transaction_id' => $transaction->transaction_id,
                'date' => Jalalian::fromDateTime($transaction->created_at)->format('Y/m/d'),
                'time' => Jalalian::fromDateTime($transaction->created_at)->format('H:i:s'),
                'sent_at' => $transaction->sent_at ? Jalalian::fromDateTime($transaction->sent_at)->format('Y/m/d H:i:s') : null,
                'balance_deducted_at_submission' => $transaction->balance_deducted_at_submission,
                'approval_status' => $transaction->approval_status,
                'rejection_reason' => $transaction->rejection_reason,
                'approved_at' => $transaction->approved_at ? Jalalian::fromDateTime($transaction->approved_at)->format('Y/m/d H:i:s') : null,
                'batch_id' => $transaction->batch_id,
                'recipients_type' => $transaction->recipients_type,
                'recipients_count' => $transaction->recipients_count,
                'sms_parts' => $transaction->sms_parts,
            ];

            // Add SMS package info if exists
            if ($transaction->smsPackage) {
                $data['package'] = [
                    'id' => $transaction->smsPackage->id,
                    'name' => $transaction->smsPackage->name,
                    'sms_count' => $transaction->smsPackage->sms_count,
                    'price' => $transaction->smsPackage->price,
                    'discount_price' => $transaction->smsPackage->discount_price,
                ];
            }

            // Add customer info if exists
            if ($transaction->customer) {
                $data['customer'] = [
                    'id' => $transaction->customer->id,
                    'name' => $transaction->customer->name,
                    'phone' => $transaction->customer->phone_number,
                ];
            }

            // Add appointment info if exists
            if ($transaction->appointment) {
                // Combine appointment_date and start_time to create a datetime
                $appointmentDateTime = $transaction->appointment->appointment_date->format('Y-m-d') . ' ' . $transaction->appointment->start_time;
                
                $data['appointment'] = [
                    'id' => $transaction->appointment->id,
                    'appointment_date' => Jalalian::fromDateTime($transaction->appointment->appointment_date)->format('Y/m/d'),
                    'start_time' => $transaction->appointment->start_time,
                    'end_time' => $transaction->appointment->end_time,
                    'scheduled_at' => Jalalian::fromDateTime($appointmentDateTime)->format('Y/m/d H:i:s'),
                ];
            }

            return $data;
        });

                // Calculate summary statistics
        $summaryQuery = SmsTransaction::query();
        
        if ($salon) {
            $summaryQuery->where('salon_id', $salon->id)->where('type', 'purchase');
        } else {
            $summaryQuery->where('user_id', $user->id);
        }
        
        $summary = [
            'total_transactions' => (clone $summaryQuery)->count(),
            'total_amount' => (clone $summaryQuery)->sum('amount'),
            'total_sms_sent' => (clone $summaryQuery)->sum('sms_count'),
            'transactions_by_type' => (clone $summaryQuery)->selectRaw('type, COUNT(*) as count, COALESCE(SUM(amount), 0) as total_amount, COALESCE(SUM(sms_count), 0) as total_sms')
                ->groupBy('type')
                ->get()
                ->keyBy('type'),
            'transactions_by_status' => (clone $summaryQuery)->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->keyBy('status'),
        ];

        return response()->json([
            'data' => $formattedTransactions,
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
            ],
            'summary' => $summary,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(SmsTransaction $smsTransaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SmsTransaction $smsTransaction)
    {
        //
    }

    /**
     * Display financial transactions for a specific salon
     */
    public function financialTransactions(Request $request, Salon $salon = null)
    {
        $user = Auth::user();
        
        // Verify user has access to this salon
        if ($salon && !$user->salons()->where('id', $salon->id)->exists()) {
            return response()->json(['error' => 'Unauthorized access to salon'], 403);
        }

        $query = Order::query()
            ->with(['salon:id,name', 'user:id,name', 'smsPackage:id,name', 'transactions' => function($q) {
                $q->latest();
            }])
            ->latest();

        // Filter by salon if provided
        if ($salon) {
            $query->where('salon_id', $salon->id);
        } else {
            // If no specific salon, filter by user's salons
            $query->where('user_id', $user->id);
        }

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range if provided
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $orders = $query->paginate($request->get('per_page', 20));

        $formattedOrders = $orders->getCollection()->map(function ($order) {
            $data = [
                'id' => $order->id,
                'amount' => $order->amount,
                'sms_count' => $order->sms_count,
                'status' => $order->status,
                'description' => $order->discount_code ? "خرید پکیج با کد تخفیف {$order->discount_code}" : 'خرید بسته پیامک',
                'date' => Jalalian::fromDateTime($order->created_at)->format('Y/m/d'),
                'time' => Jalalian::fromDateTime($order->created_at)->format('H:i:s'),
                'created_at' => $order->created_at,
            ];

            // Add salon info if exists
            if ($order->salon) {
                $data['salon'] = [
                    'id' => $order->salon->id,
                    'name' => $order->salon->name,
                ];
            }

            // Add user info if exists
            if ($order->user) {
                $data['user'] = [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                ];
            }

            // Add SMS package info if exists
            if ($order->smsPackage) {
                $data['package'] = [
                    'id' => $order->smsPackage->id,
                    'name' => $order->smsPackage->name,
                    'sms_count' => $order->smsPackage->sms_count,
                    'price' => $order->smsPackage->price,
                    'discount_price' => $order->smsPackage->discount_price,
                ];
            }

            // Add transaction details if exists
            if ($order->transactions && $order->transactions->isNotEmpty()) {
                $transaction = $order->transactions->first();
                $data['transaction'] = [
                    'id' => $transaction->id,
                    'gateway' => $transaction->gateway,
                    'transaction_id' => $transaction->transaction_id,
                    'reference_id' => $transaction->reference_id,
                    'status' => $transaction->status,
                    'description' => $transaction->description,
                ];
            }

            return $data;
        });

        // Calculate summary statistics
        $summaryQuery = Order::query();
        
        if ($salon) {
            $summaryQuery->where('salon_id', $salon->id);
        } else {
            $summaryQuery->where('user_id', $user->id);
        }

        $summary = [
            'total_orders' => (clone $summaryQuery)->count(),
            'total_amount' => (clone $summaryQuery)->sum('amount'),
            'total_sms_purchased' => (clone $summaryQuery)->sum('sms_count'),
            'orders_by_status' => (clone $summaryQuery)->selectRaw('status, COUNT(*) as count, COALESCE(SUM(amount), 0) as total_amount')
                ->groupBy('status')
                ->get()
                ->keyBy('status'),
        ];

        return response()->json([
            'data' => $formattedOrders,
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'from' => $orders->firstItem(),
                'to' => $orders->lastItem(),
            ],
            'summary' => $summary,
        ]);
    }
}
