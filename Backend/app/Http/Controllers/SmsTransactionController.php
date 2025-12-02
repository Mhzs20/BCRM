<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Transaction;
use App\Models\Salon;
use App\Models\SmsTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Morilog\Jalali\Jalalian;
use Hekmatinasser\Verta\Verta; // for parsing Persian (Jalali) input dates

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

        // Build a base query with relationships
        $query = SmsTransaction::query()
            ->with(['smsPackage', 'customer:id,name,phone_number', 'appointment:id,appointment_date,start_time,end_time', 'salon:id,name'])
            ->latest();

        // Filter by salon if provided in route
        if ($salon) {
            $query->where('salon_id', $salon->id);
        } else {
            // If no specific salon, filter by user's salons
            $query->where('user_id', $user->id);
        }
        
        // حذف پیامک‌های دسته‌جمعی که توسط ادمین برای کل کاربران فرستاده شده
        // این پیامک‌ها با description شروع می‌شوند: "پیامک گروهی توسط ادمین"
        $query->where(function($q) {
            $q->where('description', 'NOT LIKE', 'پیامک گروهی توسط ادمین%')
              ->orWhereNull('description');
        });

        // Additional filters (these can override the default purchase filter if needed)
        if ($request->filled('type')) {
            if ($request->type === 'send') {
                // برای تراکنش‌های ارسال، چک کنیم type='send' یا sms_type مربوط به ارسال باشد
                $query->where(function($q) {
                    $q->where('type', 'send')
                      ->orWhereIn('sms_type', [
                          'appointment_confirmation', 'appointment_reminder', 'manual_reminder',
                          'appointment_cancellation', 'appointment_modification', 'satisfaction_survey',
                          'birthday_greeting', 'service_specific_notes', 'manual_sms', 'bulk'
                      ]);
                });
            } else {
                $query->where('type', $request->type);
            }
        }

        // Filter by SMS type if provided
        if ($request->filled('sms_type')) {
            $query->where('sms_type', $request->sms_type);
        }

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by approval_status if provided
        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }

        // Parse Jalali input dates (accepts Y/m/d) and convert to Carbon via Verta
        $fromDate = null;
        $toDate = null;
        try {
            if ($request->filled('from_date')) {
                $fromDate = Verta::parse($request->from_date)->toCarbon()->startOfDay();
            }
            if ($request->filled('to_date')) {
                $toDate = Verta::parse($request->to_date)->toCarbon()->endOfDay();
            }
        } catch (\Exception $e) {
            // gracefully ignore invalid dates, fallback to defaults
            $fromDate = null;
            $toDate = null;
        }

        // Default to current Jalali month if no dates provided
        if (!$fromDate || !$toDate) {
            $vNow = Verta::now();
            if (!$fromDate) {
                $fromDate = $vNow->startMonth()->toCarbon()->startOfDay();
            }
            if (!$toDate) {
                $toDate = $vNow->endMonth()->toCarbon()->endOfDay();
            }
        }

        // Apply a date filter that selects transactions which were either created in the period (purchases) or sent in the period (consumption)
        $query->where(function ($q) use ($fromDate, $toDate) {
            $q->whereBetween('created_at', [$fromDate, $toDate])
              ->orWhereBetween('sent_at', [$fromDate, $toDate]);
        });

        // Filter by amount range if provided
        if ($request->filled('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }
        if ($request->filled('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }

        // Build the paginated dataset (purchase + consumption combined) according to filters
        $transactions = $query->paginate($request->get('per_page', 20));

        $formattedTransactions = $transactions->getCollection()->map(function ($transaction) {
            // محاسبه sms_count برای تراکنش‌های قدیمی که این فیلد null است
            $smsCount = $transaction->sms_count;
            if ($smsCount === null && $transaction->content) {
                // محاسبه تعداد پارت‌های پیامک بر اساس محتوا
                $smsService = app(\App\Services\SmsService::class);
                $smsCount = $smsService->calculateSmsParts($transaction->content);
            } elseif ($smsCount === null && $transaction->type === 'gift') {
                // برای هدایا، sms_count برابر amount است
                $smsCount = (int) $transaction->amount;
            }
            
            $data = [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'sms_type' => $transaction->sms_type,
                'status' => $transaction->status,
                'amount' => $transaction->amount,
                'sms_count' => $smsCount,
                'receptor' => $transaction->receptor,
                'content' => $transaction->content,
                'description' => $transaction->description,
                'reference_id' => $transaction->reference_id,
                'transaction_id' => $transaction->transaction_id,
                // Prefer sent_at for sent messages, otherwise created_at for purchases
                'date' => Jalalian::fromDateTime($transaction->sent_at ?? $transaction->created_at)->format('Y/m/d'),
                'time' => Jalalian::fromDateTime($transaction->sent_at ?? $transaction->created_at)->format('H:i:s'),
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

                // Calculate summary statistics for the selected period
                $periodBaseQuery = SmsTransaction::query();
                if ($salon) {
                    $periodBaseQuery->where('salon_id', $salon->id);
                } else {
                    $periodBaseQuery->where('user_id', $user->id);
                }
                $periodBaseQuery->where(function ($q) use ($fromDate, $toDate) {
                    $q->whereBetween('created_at', [$fromDate, $toDate])
                      ->orWhereBetween('sent_at', [$fromDate, $toDate]);
                });
                
                // حذف پیامک‌های دسته‌جمعی ادمین از آمار
                $periodBaseQuery->where(function($q) {
                    $q->where('description', 'NOT LIKE', 'پیامک گروهی توسط ادمین%')
                      ->orWhereNull('description');
                });

                // Piloting definitions for what counts as 'consumption' (delivered messages that decreased balance)
                // فقط پیامک‌هایی که واقعاً ارسال شده‌اند (delivered, sent) یا کسر اعتبار شده (manual_sms با approval_status=approved)
                $consumptionQuery = (clone $periodBaseQuery)->where(function($q) {
                    $q->where(function($subQ) {
                        // پیامک‌های ارسال شده
                        $subQ->whereIn('status', ['delivered', 'sent'])
                             ->where(function($typeQ) {
                                 $typeQ->whereIn('type', ['send', 'deduction', 'manual_send'])
                                       ->orWhereIn('sms_type', [
                                           'appointment_confirmation', 'appointment_reminder', 'manual_reminder',
                                           'appointment_cancellation', 'appointment_modification', 'satisfaction_survey',
                                           'birthday_greeting', 'service_specific_notes'
                                       ]);
                             });
                    })
                    ->orWhere(function($manualQ) {
                        // پیامک‌های manual که تایید شده و کسر اعتبار شده
                        $manualQ->where('sms_type', 'manual_sms')
                                ->where('approval_status', 'approved')
                                ->where('balance_deducted_at_submission', '>', 0);
                    });
                });

                // Purchase transactions (in this project they are stored as SmsTransaction with type = 'purchase' or via Order)
                $purchaseQuery = (clone $periodBaseQuery)->where(function($q) {
                    $q->where('type', 'purchase')
                      ->orWhere('type', 'gift')
                      ->orWhere('sms_type', 'purchase');
                });

                // محاسبه با در نظر گرفتن تراکنش‌های قدیمی که sms_count ندارند
                $totalPurchased = (clone $purchaseQuery)->get()->sum(function($t) {
                    if ($t->sms_count !== null) {
                        return $t->sms_count;
                    }
                    if ($t->type === 'gift' || $t->type === 'purchase') {
                        return (int) $t->amount;
                    }
                    return 0;
                });
                
                $totalConsumed = (clone $consumptionQuery)->get()->sum(function($t) {
                    if ($t->sms_count !== null) {
                        return $t->sms_count;
                    }
                    if ($t->content) {
                        $smsService = app(\App\Services\SmsService::class);
                        return $smsService->calculateSmsParts($t->content);
                    }
                    return 0;
                });

                // Current remaining balance from SalonSmsBalance if salon present
                $remainingBalance = null;
                if ($salon) {
                    $salon->loadMissing('smsBalance');
                    $remainingBalance = $salon->smsBalance->balance ?? 0;
                } else {
                    $remainingBalance = null; // Not meaningful across multiple salons
                }

                // Percent consumed relative to consumed + remaining (avoid division by zero)
                $percentConsumed = 0;
                if (($totalConsumed + ($remainingBalance ?? 0)) > 0) {
                    $percentConsumed = round(($totalConsumed / ($totalConsumed + ($remainingBalance ?? 0))) * 100);
                }

                // محاسبه آمار transactions_by_type با در نظر گرفتن sms_count واقعی
                $transactionsByType = (clone $periodBaseQuery)->get()->groupBy('type')->map(function($transactions, $type) {
                    $smsService = app(\App\Services\SmsService::class);
                    $totalSms = $transactions->sum(function($t) use ($smsService) {
                        if ($t->sms_count !== null) {
                            return $t->sms_count;
                        }
                        if ($t->type === 'gift' || $t->type === 'purchase') {
                            return (int) $t->amount;
                        }
                        if ($t->content) {
                            return $smsService->calculateSmsParts($t->content);
                        }
                        return 0;
                    });
                    
                    return (object) [
                        'type' => $type,
                        'count' => $transactions->count(),
                        'total_amount' => $transactions->sum('amount'),
                        'total_sms' => $totalSms,
                    ];
                })->keyBy('type');

                $summary = [
                    'total_transactions' => (clone $periodBaseQuery)->count(),
                    'purchased_sms' => (int) $totalPurchased,
                    'consumed_sms' => (int) $totalConsumed,
                    'remaining_sms' => $remainingBalance,
                    'percent_consumed' => $percentConsumed,
                    'transactions_by_type' => $transactionsByType,
                    'transactions_by_status' => (clone $periodBaseQuery)->selectRaw('status, COUNT(*) as count')
                        ->groupBy('status')
                        ->get()
                        ->keyBy('status'),
                ];

        // Prepare sent messages status list grouped by status (for UI table)
        $sentMessagesStatus = (clone $periodBaseQuery)
            ->selectRaw('status, COUNT(*) as count, COALESCE(SUM(sms_count), 0) as total_sms')
            ->where('amount', '>', 0)
            ->groupBy('status')
            ->get();

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
            'sent_messages_status' => $sentMessagesStatus,
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

    /**
     * Display sent messages that resulted in cost deductions for a salon
     */
    public function salonSentMessages(Request $request, Salon $salon)
    {
        $user = Auth::user();
        
        // Verify user has access to this salon
        if (!$user->salons()->where('id', $salon->id)->exists()) {
            return response()->json(['error' => 'Unauthorized access to salon'], 403);
        }

        $query = SmsTransaction::query()
            ->with([
                'smsPackage', 
                'customer:id,name,phone_number', 
                'appointment:id,appointment_date,start_time,end_time',
                'salon:id,name'
            ])
            ->where('status', 'delivered') // Only delivered messages
            ->where('amount', '>', 0) // Only transactions with cost
            ->where('salon_id', $salon->id) // Only for this specific salon
            ->latest();

        // Filter by SMS type if provided
        if ($request->filled('sms_type')) {
            $query->where('sms_type', $request->sms_type);
        }

        // Filter by date range if provided
        if ($request->filled('from_date')) {
            $query->whereDate('sent_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('sent_at', '<=', $request->to_date);
        }

        // Filter by amount range if provided
        if ($request->filled('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }
        if ($request->filled('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }

        // Search in content or recipient
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('content', 'like', "%{$search}%")
                  ->orWhere('receptor', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('phone_number', 'like', "%{$search}%");
                  });
            });
        }

        $transactions = $query->paginate($request->get('per_page', 20));

        $formattedTransactions = $transactions->getCollection()->map(function ($transaction) {
            $data = [
                'id' => $transaction->id,
                'sms_type' => $transaction->sms_type,
                'amount' => $transaction->amount,
                'sms_count' => $transaction->sms_count,
                'sms_parts' => $transaction->sms_parts,
                'receptor' => $transaction->receptor,
                'content' => $transaction->content,
                'description' => $transaction->description,
                'reference_id' => $transaction->reference_id,
                'transaction_id' => $transaction->transaction_id,
                'sent_date' => $transaction->sent_at ? Jalalian::fromDateTime($transaction->sent_at)->format('Y/m/d') : null,
                'sent_time' => $transaction->sent_at ? Jalalian::fromDateTime($transaction->sent_at)->format('H:i:s') : null,
                'sent_at' => $transaction->sent_at ? Jalalian::fromDateTime($transaction->sent_at)->format('Y/m/d H:i:s') : null,
                'batch_id' => $transaction->batch_id,
                'recipients_type' => $transaction->recipients_type,
                'recipients_count' => $transaction->recipients_count,
            ];

            // Add salon info
            if ($transaction->salon) {
                $data['salon'] = [
                    'id' => $transaction->salon->id,
                    'name' => $transaction->salon->name,
                ];
            }

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
        $summaryQuery = SmsTransaction::query()
            ->where('status', 'delivered')
            ->where('amount', '>', 0)
            ->where('salon_id', $salon->id);

        // Apply same filters to summary
        if ($request->filled('sms_type')) {
            $summaryQuery->where('sms_type', $request->sms_type);
        }
        if ($request->filled('from_date')) {
            $summaryQuery->whereDate('sent_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $summaryQuery->whereDate('sent_at', '<=', $request->to_date);
        }
        if ($request->filled('min_amount')) {
            $summaryQuery->where('amount', '>=', $request->min_amount);
        }
        if ($request->filled('max_amount')) {
            $summaryQuery->where('amount', '<=', $request->max_amount);
        }

        $summary = [
            'total_sent_messages' => (clone $summaryQuery)->count(),
            'total_cost_deducted' => (clone $summaryQuery)->sum('amount'),
            'total_sms_parts_sent' => (clone $summaryQuery)->sum('sms_parts'),
            'total_sms_count' => (clone $summaryQuery)->sum('sms_count'),
            'messages_by_type' => (clone $summaryQuery)->selectRaw('sms_type, COUNT(*) as count, COALESCE(SUM(amount), 0) as total_amount, COALESCE(SUM(sms_count), 0) as total_sms')
                ->groupBy('sms_type')
                ->get()
                ->keyBy('sms_type'),
            'average_cost_per_message' => (clone $summaryQuery)->avg('amount'),
            'messages_by_date' => (clone $summaryQuery)->selectRaw('DATE(sent_at) as sent_date, COUNT(*) as count, COALESCE(SUM(amount), 0) as total_amount')
                ->whereNotNull('sent_at')
                ->groupBy('sent_date')
                ->orderBy('sent_date', 'desc')
                ->limit(30)
                ->get(),
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
}
