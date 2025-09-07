<?php

namespace App\Http\Controllers;

use App\Models\SmsTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Morilog\Jalali\Jalalian;

class SmsTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $salon = $request->route('salon');
        
        // Verify user has access to this salon
        if ($salon && !$user->salons()->where('id', $salon->id)->exists()) {
            return response()->json(['error' => 'Unauthorized access to salon'], 403);
        }

        $query = SmsTransaction::query()
            ->with(['smsPackage', 'customer:id,name,phone_number', 'appointment:id,scheduled_at'])
            ->latest();

        // Filter by salon if provided in route
        if ($salon) {
            $query->where('salon_id', $salon->id);
        } else {
            // If no specific salon, filter by user's salons
            $query->where('user_id', $user->id);
        }

        // Filter by transaction type if provided
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
                $data['appointment'] = [
                    'id' => $transaction->appointment->id,
                    'scheduled_at' => Jalalian::fromDateTime($transaction->appointment->scheduled_at)->format('Y/m/d H:i:s'),
                ];
            }

            return $data;
        });

        // Calculate summary statistics
        $summary = [
            'total_transactions' => $transactions->total(),
            'total_amount' => SmsTransaction::when($salon, function($q) use ($salon) {
                    return $q->where('salon_id', $salon->id);
                }, function($q) use ($user) {
                    return $q->where('user_id', $user->id);
                })
                ->sum('amount'),
            'total_sms_sent' => SmsTransaction::when($salon, function($q) use ($salon) {
                    return $q->where('salon_id', $salon->id);
                }, function($q) use ($user) {
                    return $q->where('user_id', $user->id);
                })
                ->sum('sms_count'),
            'transactions_by_type' => SmsTransaction::when($salon, function($q) use ($salon) {
                    return $q->where('salon_id', $salon->id);
                }, function($q) use ($user) {
                    return $q->where('user_id', $user->id);
                })
                ->selectRaw('type, COUNT(*) as count, COALESCE(SUM(amount), 0) as total_amount, COALESCE(SUM(sms_count), 0) as total_sms')
                ->groupBy('type')
                ->get()
                ->keyBy('type'),
            'transactions_by_status' => SmsTransaction::when($salon, function($q) use ($salon) {
                    return $q->where('salon_id', $salon->id);
                }, function($q) use ($user) {
                    return $q->where('user_id', $user->id);
                })
                ->selectRaw('status, COUNT(*) as count')
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
     * Remove the specified resource from storage.
     */
    public function destroy(SmsTransaction $smsTransaction)
    {
        //
    }
}
