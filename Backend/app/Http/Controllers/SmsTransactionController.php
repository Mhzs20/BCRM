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
        
        $query->where(function($q) {
            $q->where('description', 'NOT LIKE', 'پیامک گروهی توسط ادمین%')
              ->orWhereNull('description');
        });

        if ($request->filled('type')) {
            if ($request->type === 'send') {
                $query->where(function($q) {
                    $q->where('type', 'send')
                      ->orWhereIn('sms_type', [
                          'appointment_confirmation', 'appointment_reminder', 'manual_reminder',
                          'appointment_cancellation', 'appointment_modification', 'satisfaction_survey',
                          'birthday_greeting', 'service_specific_notes', 'manual_sms', 'bulk'
                      ]);
                });
            } elseif ($request->type === 'purchase') {
                $query->where(function($q) {
                    $q->where('type', 'purchase')
                      ->orWhere('sms_type', 'purchase');
                });
            } elseif ($request->type === 'gift') {
                $query->where(function($q) {
                    $q->where('type', 'gift')
                      ->orWhere('sms_type', 'gift');
                });
            } else {
                $query->where('type', $request->type);
            }
        }

        if ($request->filled('sms_type')) {
            $query->where('sms_type', $request->sms_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }

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

        // Group by batch_id for manual_send and bulk SMS types
        $processedTransactions = collect();
        $processedBatchIds = [];
        $processedCampaignIds = [];

        // Get campaigns for this salon that should appear in the list
        $campaignsQuery = \App\Models\SmsCampaign::query()
            ->where('salon_id', $salon ? $salon->id : null)
            ->where('approval_status', 'approved')
            ->latest();

        // Apply date filter to campaigns if provided
        if ($fromDate && $toDate) {
            $campaignsQuery->whereBetween('created_at', [$fromDate, $toDate]);
        }

        $campaigns = $campaignsQuery->get();

        foreach ($transactions->getCollection() as $transaction) {
            // Check if this is a group/campaign message (has batch_id and is manual_send or bulk)
            $isGroupMessage = $transaction->batch_id && 
                             in_array($transaction->sms_type, ['manual_sms', 'bulk']) &&
                             ($transaction->recipients_count ?? 0) > 1;

            if ($isGroupMessage) {
                // Skip if we already processed this batch
                if (in_array($transaction->batch_id, $processedBatchIds)) {
                    continue;
                }
                $processedBatchIds[] = $transaction->batch_id;
            }

            $processedTransactions->push($transaction);
        }

        // Add campaigns as pseudo-transactions
        foreach ($campaigns as $campaign) {
            if (in_array($campaign->id, $processedCampaignIds)) {
                continue;
            }
            $processedCampaignIds[] = $campaign->id;

            // Create a pseudo-transaction object for the campaign
            $campaignTransaction = (object) [
                'id' => $campaign->id,
                'campaign_id' => $campaign->id,
                'type' => 'campaign',
                'sms_type' => 'bulk',
                'status' => $campaign->status,
                'amount' => $campaign->total_cost,
                'sms_count' => $campaign->customer_count,
                'receptor' => null,
                'content' => $campaign->message,
                'description' => 'کمپین پیامکی',
                'reference_id' => null,
                'transaction_id' => null,
                'sent_at' => $campaign->created_at,
                'created_at' => $campaign->created_at,
                'balance_deducted_at_submission' => $campaign->total_cost,
                'approval_status' => $campaign->approval_status,
                'rejection_reason' => $campaign->rejection_reason,
                'approved_at' => $campaign->approved_at,
                'batch_id' => null,
                'recipients_type' => 'campaign',
                'recipients_count' => $campaign->customer_count,
                'sms_parts' => 1,
                'smsPackage' => null,
                'customer' => null,
                'appointment' => null,
                'salon' => $campaign->salon,
            ];

            $processedTransactions->push($campaignTransaction);
        }

        // Pre-fetch missing SMS packages from Orders and load all packages for fallback matching
        $missingPackageOrderIds = [];
        $needsOrderFallback = false;

        foreach ($processedTransactions as $transaction) {
            if ($transaction instanceof SmsTransaction && 
                !$transaction->smsPackage && 
                $transaction->type === 'purchase') {
                
                if ($transaction->description && preg_match('/سفارش (\d+)/', $transaction->description, $matches)) {
                    $missingPackageOrderIds[$transaction->id] = $matches[1];
                    $needsOrderFallback = true;
                }
            }
        }

        $orders = [];
        // Always fetch all packages to support "nearest match" fallback efficiently
        // This is lightweight enough (usually < 20 packages) to do on every request involving purchases
        $allPackages = \App\Models\SmsPackage::all();

        if ($needsOrderFallback && !empty($missingPackageOrderIds)) {
            $orders = \App\Models\Order::whereIn('id', array_unique($missingPackageOrderIds))->get()->keyBy('id');
        }

        $formattedTransactions = $processedTransactions->map(function ($transaction) use ($missingPackageOrderIds, $orders, $allPackages) {
             $smsCount = $transaction->sms_count;

            if ($smsCount === null && $transaction->content) {
                 $smsService = app(\App\Services\SmsService::class);
                $smsCount = $smsService->calculateSmsParts($transaction->content);
            } elseif ($smsCount === null && $transaction->type === 'gift') {
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
                'group_count' => ($transaction->recipients_count ?? 1) * ($transaction->sms_parts ?? $smsCount ?? 1),
            ];

            // Add campaign_id if it's a campaign pseudo-transaction
            if (isset($transaction->campaign_id)) {
                $data['campaign_id'] = $transaction->campaign_id;
            }

            // Add SMS package info if exists
            $smsPackage = $transaction->smsPackage;

            // Fallback Logic to find package if relation is missing
            if (!$smsPackage && $transaction->type === 'purchase') {
                // Priority 1: Check if sms_package_id exists on transaction
                if (!empty($transaction->sms_package_id)) {
                    $smsPackage = $allPackages->firstWhere('id', $transaction->sms_package_id);
                }

                // Priority 2: Check Order in description
                if (!$smsPackage && isset($missingPackageOrderIds[$transaction->id])) {
                    $orderId = $missingPackageOrderIds[$transaction->id];
                    if (isset($orders[$orderId])) {
                        $order = $orders[$orderId];
                        if ($order->sms_package_id) {
                            $smsPackage = $allPackages->firstWhere('id', $order->sms_package_id);
                        }
                    }
                }

                // Priority 3: Find nearest package by sms_count and price
                if (!$smsPackage && $allPackages->isNotEmpty()) {
                    // Try exact match on count and price
                    $smsPackage = $allPackages->first(function($p) use ($transaction, $smsCount) {
                        return $p->sms_count == $smsCount && $p->price == $transaction->amount;
                    });
                    
                    // Try match on count only
                    if (!$smsPackage && $smsCount > 0) {
                        $smsPackage = $allPackages->first(function($p) use ($smsCount) {
                            return $p->sms_count == $smsCount;
                        });
                    }
                    
                    // Try match on price only
                    if (!$smsPackage && $transaction->amount > 0) {
                        $smsPackage = $allPackages->first(function($p) use ($transaction) {
                            return $p->price == $transaction->amount;
                        });
                    }
                }
            }

            if ($smsPackage) {
                $data['package'] = [
                    'id' => $smsPackage->id,
                    'name' => $smsPackage->name,
                    'sms_count' => $smsPackage->sms_count,
                    'price' => $smsPackage->price,
                    'discount_price' => $smsPackage->discount_price,
                ];
            } elseif ($transaction->type === 'purchase') {
                // Final Fallback: Create a virtual package from transaction data
                // This ensures the frontend always receives a package object structure
                $data['package'] = [
                    'id' => null, // No real ID
                    'name' => 'بسته پیامک (آرشیو شده)', // Generic name
                    'sms_count' => $smsCount ?? 0,
                    'price' => $transaction->amount,
                    'discount_price' => $transaction->amount,
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

    /**
     * Show details of a manual SMS batch by batch_id
     */
    public function showBatchDetails(Request $request, Salon $salon, string $batchId)
    {
        $user = Auth::user();
        
        // Verify user has access to this salon
        if (!$user->salons()->where('id', $salon->id)->exists()) {
            return response()->json(['error' => 'Unauthorized access to salon'], 403);
        }

        $query = SmsTransaction::where('batch_id', $batchId)
            ->where('salon_id', $salon->id)
            ->with(['customer:id,name,phone_number'])
            ->latest();

        $transactions = $query->paginate($request->get('per_page', 20));

        if ($transactions->isEmpty()) {
            return response()->json(['message' => 'هیچ پیامکی برای این شناسه دسته یافت نشد.'], 404);
        }

        $firstTransaction = $transactions->first();
        
        // Batch summary
        $batchSummary = [
            'batch_id' => $batchId,
            'content' => $firstTransaction->content,
            'recipients_type' => $firstTransaction->recipients_type,
            'recipients_count' => $firstTransaction->recipients_count,
            'sms_parts' => $firstTransaction->sms_parts,
            'approval_status' => $firstTransaction->approval_status,
            'approved_at' => $firstTransaction->approved_at ? Jalalian::fromDateTime($firstTransaction->approved_at)->format('Y/m/d H:i:s') : null,
            'created_at' => Jalalian::fromDateTime($firstTransaction->created_at)->format('Y/m/d H:i:s'),
            'total_sms_count' => SmsTransaction::where('batch_id', $batchId)->sum('sms_count'),
            'total_deducted' => SmsTransaction::where('batch_id', $batchId)->sum('balance_deducted_at_submission'),
        ];

        $formattedTransactions = $transactions->getCollection()->map(function ($transaction) {
            $smsCount = $transaction->sms_count;
            if ($smsCount === null && $transaction->content) {
                $smsService = app(\App\Services\SmsService::class);
                $smsCount = $smsService->calculateSmsParts($transaction->content);
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
                'date' => Jalalian::fromDateTime($transaction->sent_at ?? $transaction->created_at)->format('Y/m/d'),
                'time' => Jalalian::fromDateTime($transaction->sent_at ?? $transaction->created_at)->format('H:i:s'),
                'sent_at' => $transaction->sent_at ? Jalalian::fromDateTime($transaction->sent_at)->format('Y/m/d H:i:s') : null,
                'balance_deducted_at_submission' => $transaction->balance_deducted_at_submission,
                'approval_status' => $transaction->approval_status,
                'sms_parts' => $transaction->sms_parts,
            ];

            if ($transaction->customer) {
                $data['customer'] = [
                    'id' => $transaction->customer->id,
                    'name' => $transaction->customer->name,
                    'phone' => $transaction->customer->phone_number,
                ];
            }

            return $data;
        });

        return response()->json([
            'batch_summary' => $batchSummary,
            'data' => $formattedTransactions,
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
            ],
        ]);
    }

    /**
     * Show details of a campaign by campaign_id
     */
    public function showCampaignDetails(Request $request, Salon $salon, int $campaignId)
    {
        $user = Auth::user();
        
        // Verify user has access to this salon
        if (!$user->salons()->where('id', $salon->id)->exists()) {
            return response()->json(['error' => 'Unauthorized access to salon'], 403);
        }

        $campaign = \App\Models\SmsCampaign::where('id', $campaignId)
            ->where('salon_id', $salon->id)
            ->first();

        if (!$campaign) {
            return response()->json(['message' => 'کمپین یافت نشد.'], 404);
        }

        $query = \App\Models\SmsCampaignMessage::where('campaign_id', $campaignId)
            ->with(['customer:id,name,phone_number'])
            ->latest();

        $messages = $query->paginate($request->get('per_page', 20));

        // Campaign summary
        $campaignSummary = [
            'campaign_id' => $campaign->id,
            'message' => $campaign->message,
            'customer_count' => $campaign->customer_count,
            'total_cost' => $campaign->total_cost,
            'approval_status' => $campaign->approval_status,
            'approved_at' => $campaign->approved_at ? Jalalian::fromDateTime($campaign->approved_at)->format('Y/m/d H:i:s') : null,
            'created_at' => Jalalian::fromDateTime($campaign->created_at)->format('Y/m/d H:i:s'),
            'status_counts' => \App\Models\SmsCampaignMessage::where('campaign_id', $campaignId)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status'),
        ];

        $formattedMessages = $messages->getCollection()->map(function ($message) {
            $data = [
                'id' => $message->id,
                'status' => $message->status,
                'receptor' => $message->receptor,
                'sent_at' => $message->sent_at ? Jalalian::fromDateTime($message->sent_at)->format('Y/m/d H:i:s') : null,
                'delivered_at' => $message->delivered_at ? Jalalian::fromDateTime($message->delivered_at)->format('Y/m/d H:i:s') : null,
                'external_response' => $message->external_response,
            ];

            if ($message->customer) {
                $data['customer'] = [
                    'id' => $message->customer->id,
                    'name' => $message->customer->name,
                    'phone' => $message->customer->phone_number,
                ];
            }

            return $data;
        });

        return response()->json([
            'campaign_summary' => $campaignSummary,
            'data' => $formattedMessages,
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
                'from' => $messages->firstItem(),
                'to' => $messages->lastItem(),
            ],
        ]);
    }
}
