<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserReferral;
use App\Models\ReferralSetting;
use App\Models\WalletTransaction;
use App\Services\ReferralService;
use App\Services\ReferralSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ReferralManagementController extends Controller
{
    protected ReferralService $referralService;

    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    /**
     * Dashboard view
     */
    public function dashboard()
    {
        // آمار کلی
        $stats = [
            'total_referrals' => UserReferral::count(),
            'successful_referrals' => UserReferral::where('status', 'completed')->count(),
            'total_rewards' => WalletTransaction::whereIn('type', ['referral_reward', 'order_reward'])->sum('amount'),
        ];
        
        $stats['success_rate'] = $stats['total_referrals'] > 0 
            ? round(($stats['successful_referrals'] / $stats['total_referrals']) * 100, 1) 
            : 0;

        // آخرین فعالیت‌ها
        $recent_activities = WalletTransaction::with('user')
            ->whereHas('user') // فقط تراکنش‌هایی که کاربر آنها موجود است
            ->latest()
            ->limit(10)
            ->get();

        // داده‌های نمودار روند دعوت‌نامه‌ها (7 روز گذشته)
        $dates = collect();
        for ($i = 6; $i >= 0; $i--) {
            $dates->push(now()->subDays($i));
        }

        $referrals_trend = $dates->map(function($date) {
            return [
                'date' => $date->format('Y-m-d'),
                'label' => verta($date)->format('m/d'),
                'count' => UserReferral::whereDate('created_at', $date)->count()
            ];
        });

        // توزیع پاداش‌ها
        $rewards_distribution = [
            'labels' => ['پاداش رفرال', 'پاداش خرید', 'شارژ دستی', 'سایر'],
            'data' => [
                WalletTransaction::where('type', 'referral_reward')->count(),
                WalletTransaction::where('type', 'order_reward')->count(),
                WalletTransaction::where('type', 'manual_credit')->count(),
                WalletTransaction::whereNotIn('type', ['referral_reward', 'order_reward', 'manual_credit'])->count(),
            ]
        ];

        $charts = [
            'referrals_trend' => [
                'labels' => $referrals_trend->pluck('label')->toArray(),
                'data' => $referrals_trend->pluck('count')->toArray()
            ],
            'rewards_distribution' => $rewards_distribution
        ];

        return view('admin.referral.dashboard', compact('stats', 'recent_activities', 'charts'));
    }

    /**
     * Users management view
     */
    public function users(Request $request)
    {
        $query = User::whereNotNull('referral_code')
            ->withCount([
                'referrals as referrals_count',
                'referrals as successful_referrals_count' => function($q) {
                    $q->where('status', 'completed');
                }
            ]);

        // Filters
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%")
                  ->orWhere('referral_code', 'like', "%{$request->search}%");
            });
        }

        if ($request->balance_filter === 'has_balance') {
            $query->where('wallet_balance', '>', 0);
        } elseif ($request->balance_filter === 'no_balance') {
            $query->where('wallet_balance', '<=', 0);
        }

        // Sorting
        switch ($request->sort) {
            case 'referrals_count_desc':
                $query->orderBy('referrals_count', 'desc');
                break;
            case 'wallet_balance_desc':
                $query->orderBy('wallet_balance', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $users = $query->paginate(20);

        // Summary stats
        $summary = [
            'total_users' => User::whereNotNull('referral_code')->count(),
            'total_wallet_balance' => User::sum('wallet_balance'),
            'total_referrals' => UserReferral::count(),
        ];

        return view('admin.referral.users', compact('users', 'summary'));
    }

    /**
     * Referrals management view
     */
    public function referrals(Request $request)
    {
        $query = UserReferral::with(['referrer', 'referred']);

        // Filters
        if ($request->search) {
            $query->whereHas('referrer', function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            })->orWhereHas('referred', function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $referrals = $query->orderBy('created_at', 'desc')->paginate(20);

        // Stats
        $stats = [
            'total' => UserReferral::count(),
            'pending' => UserReferral::where('status', 'pending')->count(),
            'completed' => UserReferral::where('status', 'completed')->count(),
        ];
        $stats['success_rate'] = $stats['total'] > 0 
            ? round(($stats['completed'] / $stats['total']) * 100, 1) 
            : 0;

        return view('admin.referral.referrals', compact('referrals', 'stats'));
    }

    /**
     * Wallet management view
     */
    public function wallet(Request $request)
    {
        $query = WalletTransaction::with('user')
            ->whereHas('user'); // فقط تراکنش‌هایی که کاربر آنها موجود است

        // Filters
        if ($request->search) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(20);

        // Stats
        $stats = [
            'total_balance' => User::sum('wallet_balance'),
            'total_credits' => WalletTransaction::where('amount', '>', 0)->sum('amount'),
            'total_debits' => abs(WalletTransaction::where('amount', '<', 0)->sum('amount')),
            'today_transactions' => WalletTransaction::whereDate('created_at', today())->count(),
        ];

        // Charts data
        $dates = collect();
        for ($i = 6; $i >= 0; $i--) {
            $dates->push(now()->subDays($i));
        }

        $daily_transactions = $dates->map(function($date) {
            return [
                'date' => $date->format('Y-m-d'),
                'label' => verta($date)->format('m/d'),
                'count' => WalletTransaction::whereDate('created_at', $date)->count()
            ];
        });

        $transaction_types = [
            'labels' => ['پاداش رفرال', 'پاداش خرید', 'شارژ دستی', 'خرید'],
            'data' => [
                WalletTransaction::where('type', 'referral_reward')->count(),
                WalletTransaction::where('type', 'order_reward')->count(),
                WalletTransaction::where('type', 'manual_credit')->count(),
                WalletTransaction::where('type', 'purchase')->count(),
            ]
        ];

        $charts = [
            'daily_transactions' => [
                'labels' => $daily_transactions->pluck('label')->toArray(),
                'data' => $daily_transactions->pluck('count')->toArray()
            ],
            'transaction_types' => $transaction_types
        ];

        return view('admin.referral.wallet', compact('transactions', 'stats', 'charts'));
    }

    /**
     * User referrals view
     */
    public function userReferrals(Request $request, User $user)
    {
        $query = $user->referrals()->with('referred');

        // Filters
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('referred_phone', 'like', "%{$request->search}%")
                  ->orWhereHas('referred', function($subQ) use ($request) {
                      $subQ->where('name', 'like', "%{$request->search}%")
                           ->orWhere('phone', 'like', "%{$request->search}%");
                  });
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Sorting
        switch ($request->sort) {
            case 'created_at_asc':
                $query->orderBy('created_at', 'asc');
                break;
            case 'reward_desc':
                $query->orderBy('reward_amount', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $referrals = $query->paginate(15);

        // Stats
        $stats = [
            'total_referrals' => $user->referrals()->count(),
            'successful_referrals' => $user->referrals()->where('status', 'completed')->count(),
            'pending_referrals' => $user->referrals()->where('status', 'pending')->count(),
            'total_rewards' => $user->referrals()->sum('reward_amount'),
        ];

        // Chart data for monthly performance
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months->push(now()->subMonths($i));
        }

        $monthly_data = $months->map(function($month) use ($user) {
            $referrals = $user->referrals()->whereYear('created_at', $month->year)
                              ->whereMonth('created_at', $month->month)->count();
            $successful = $user->referrals()->where('status', 'completed')
                               ->whereYear('created_at', $month->year)
                               ->whereMonth('created_at', $month->month)->count();
            
            return [
                'label' => verta($month)->format('Y/m'),
                'referrals' => $referrals,
                'successful' => $successful
            ];
        });

        $chart = [
            'labels' => $monthly_data->pluck('label')->toArray(),
            'referrals' => $monthly_data->pluck('referrals')->toArray(),
            'successful' => $monthly_data->pluck('successful')->toArray(),
        ];

        return view('admin.referral.user-referrals', compact('user', 'referrals', 'stats', 'chart'));
    }

    /**
     * User wallet view
     */
    public function userWallet(Request $request, User $user)
    {
        $query = $user->walletTransactions();

        // Filters
        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Sorting
        switch ($request->sort) {
            case 'created_at_asc':
                $query->orderBy('created_at', 'asc');
                break;
            case 'amount_desc':
                $query->orderBy('amount', 'desc');
                break;
            case 'amount_asc':
                $query->orderBy('amount', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $transactions = $query->paginate(20);

        // Stats
        $stats = [
            'total_credits' => $user->walletTransactions()->where('amount', '>', 0)->sum('amount'),
            'total_debits' => abs($user->walletTransactions()->where('amount', '<', 0)->sum('amount')),
            'total_transactions' => $user->walletTransactions()->count(),
        ];

        // Chart data for balance history
        $recent_transactions = $user->walletTransactions()
            ->orderBy('created_at', 'asc')
            ->limit(20)
            ->get();

        $chart = [
            'labels' => $recent_transactions->map(function($t) {
                return verta($t->created_at)->format('m/d');
            })->toArray(),
            'balances' => $recent_transactions->pluck('balance_after')->toArray(),
        ];

        return view('admin.referral.user-wallet', compact('user', 'transactions', 'stats', 'chart'));
    }

    /**
     * Manual credit to user wallet
     */
    public function manualCredit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'amount' => 'required|numeric|min:1000',
                'description' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ]);
            }

            $user = User::findOrFail($request->user_id);

            DB::beginTransaction();

            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'manual_credit',
                'amount' => $request->amount,
                'status' => 'completed',
                'description' => $request->description,
                'balance_before' => $user->wallet_balance,
                'balance_after' => $user->wallet_balance + $request->amount,
            ]);

            $user->increment('wallet_balance', $request->amount);

            // Send SMS notification
            ReferralSmsService::sendManualTransactionNotification(
                $user, 
                'credit', 
                $request->amount, 
                $request->description
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'موجودی با موفقیت افزایش یافت.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'خطا در افزایش موجودی: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Manual debit from user wallet
     */
    public function manualDebit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'amount' => 'required|numeric|min:1000',
                'description' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ]);
            }

            $user = User::findOrFail($request->user_id);

            if ($user->wallet_balance < $request->amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'موجودی کیف پول کافی نیست.'
                ]);
            }

            DB::beginTransaction();

            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'manual_debit',
                'amount' => -$request->amount,
                'status' => 'completed',
                'description' => $request->description,
                'balance_before' => $user->wallet_balance,
                'balance_after' => $user->wallet_balance - $request->amount,
            ]);

            $user->decrement('wallet_balance', $request->amount);

            // Send SMS notification
            ReferralSmsService::sendManualTransactionNotification(
                $user, 
                'debit', 
                $request->amount, 
                $request->description
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'موجودی با موفقیت کاهش یافت.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'خطا در کاهش موجودی: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get users with referral information
     */
    public function getUsers(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $hasReferrals = $request->get('has_referrals');
            $hasWallet = $request->get('has_wallet');

            $query = User::with(['referrer:id,name,mobile,referral_code'])
                ->withCount([
                    'referrals as total_referrals_count',
                    'referrals as successful_referrals_count' => function($q) {
                        $q->whereIn('status', [
                            UserReferral::STATUS_REGISTERED,
                            UserReferral::STATUS_PURCHASED,
                            UserReferral::STATUS_REWARDED
                        ]);
                    }
                ])
                ->addSelect([
                    'total_earnings' => UserReferral::selectRaw('SUM(total_reward_amount)')
                        ->whereColumn('referrer_id', 'users.id')
                ])
                ->orderBy('created_at', 'desc');

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('mobile', 'like', "%{$search}%")
                      ->orWhere('referral_code', 'like', "%{$search}%");
                });
            }

            if ($hasReferrals === 'true') {
                $query->has('referrals');
            }

            if ($hasWallet === 'true') {
                $query->where('wallet_balance', '>', 0);
            }

            $users = $query->paginate($perPage, ['*'], 'page', $page);

            // Add display attributes
            $users->getCollection()->transform(function ($user) {
                $user->formatted_wallet_balance = number_format($user->wallet_balance ?? 0) . ' تومان';
                $user->formatted_total_earnings = number_format($user->total_earnings ?? 0) . ' تومان';
                return $user;
            });

            return response()->json([
                'status' => 'success',
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در دریافت کاربران: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get referrals list
     */
    public function getReferrals(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 15);
            $status = $request->get('status');
            $search = $request->get('search');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            $query = UserReferral::with([
                'referrer:id,name,mobile,referral_code',
                'referred:id,name,mobile'
            ])->orderBy('created_at', 'desc');

            if ($status) {
                $query->where('status', $status);
            }

            if ($search) {
                $query->whereHas('referrer', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('mobile', 'like', "%{$search}%");
                })->orWhereHas('referred', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('mobile', 'like', "%{$search}%");
                });
            }

            if ($dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->where('created_at', '<=', $dateTo . ' 23:59:59');
            }

            $referrals = $query->paginate($perPage, ['*'], 'page', $page);

            // Add display attributes
            $referrals->getCollection()->transform(function ($referral) {
                $referral->formatted_total_reward = number_format($referral->total_reward_amount) . ' تومان';
                $referral->formatted_signup_reward = number_format($referral->signup_reward_amount) . ' تومان';
                $referral->formatted_purchase_reward = number_format($referral->purchase_reward_amount) . ' تومان';
                return $referral;
            });

            return response()->json([
                'status' => 'success',
                'data' => $referrals
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در دریافت رفرال‌ها: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update referral status manually
     */
    public function updateReferralStatus(Request $request, $referralId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,registered,purchased,rewarded,cancelled',
                'notes' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $referral = UserReferral::findOrFail($referralId);
            $oldStatus = $referral->status;
            $newStatus = $request->status;

            if ($oldStatus === $newStatus) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'وضعیت جدید با وضعیت فعلی یکسان است.'
                ], 400);
            }

            DB::beginTransaction();

            $referral->status = $newStatus;
            $referral->save();

            // Create admin note transaction if needed
            if ($request->notes) {
                WalletTransaction::create([
                    'user_id' => $referral->referrer_id,
                    'type' => 'admin_note',
                    'amount' => 0,
                    'status' => WalletTransaction::STATUS_COMPLETED,
                    'description' => "یادداشت ادمین برای رفرال #{$referralId}: " . $request->notes,
                    'admin_id' => $request->user()->id,
                    'referral_id' => $referralId,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'وضعیت رفرال با موفقیت به‌روزرسانی شد.',
                'data' => [
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در به‌روزرسانی وضعیت رفرال: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get wallet transactions
     */
    public function getWalletTransactions(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 20);
            $type = $request->get('type');
            $userId = $request->get('user_id');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            $query = WalletTransaction::with(['user:id,name,mobile', 'admin:id,name'])
                ->orderBy('created_at', 'desc');

            if ($type) {
                $query->where('type', $type);
            }

            if ($userId) {
                $query->where('user_id', $userId);
            }

            if ($dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->where('created_at', '<=', $dateTo . ' 23:59:59');
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
     * Add or deduct wallet balance manually
     */
    public function adjustWalletBalance(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'amount' => 'required|numeric',
                'description' => 'required|string|max:500',
                'type' => 'required|in:credit,debit',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $user = User::findOrFail($request->user_id);
            $amount = $request->amount;
            $isDebit = $request->type === 'debit';

            if ($isDebit && $user->wallet_balance < $amount) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'موجودی کیف پول کاربر کافی نیست.',
                    'data' => [
                        'current_balance' => $user->wallet_balance,
                        'requested_amount' => $amount,
                    ]
                ], 400);
            }

            DB::beginTransaction();

            $transactionAmount = $isDebit ? -$amount : $amount;
            $transactionType = $isDebit ? WalletTransaction::TYPE_ADMIN_DEBIT : WalletTransaction::TYPE_ADMIN_CREDIT;

            $transaction = WalletTransaction::createAndUpdateBalance([
                'user_id' => $user->id,
                'type' => $transactionType,
                'amount' => $transactionAmount,
                'status' => WalletTransaction::STATUS_COMPLETED,
                'description' => $request->description,
                'admin_id' => $request->user()->id,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'موجودی کیف پول با موفقیت تنظیم شد.',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'old_balance' => $user->wallet_balance,
                    'new_balance' => $user->fresh()->wallet_balance,
                    'amount_changed' => $transactionAmount,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در تنظیم موجودی کیف پول: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get withdraw requests
     */
    public function getWithdrawRequests(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 15);
            $status = $request->get('status');

            $query = WithdrawRequest::with(['user:id,name,mobile', 'processedBy:id,name'])
                ->orderBy('created_at', 'desc');

            if ($status) {
                $query->where('status', $status);
            }

            $requests = $query->paginate($perPage, ['*'], 'page', $page);

            // Add display attributes
            $requests->getCollection()->transform(function ($request) {
                $request->status_display = $request->getStatusDisplayAttribute();
                $request->formatted_amount = number_format($request->amount) . ' تومان';
                return $request;
            });

            return response()->json([
                'status' => 'success',
                'data' => $requests
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در دریافت درخواست‌های برداشت: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process withdraw request (approve/reject/complete)
     */
    public function processWithdrawRequest(Request $request, $requestId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'action' => 'required|in:approve,reject,complete',
                'admin_notes' => 'nullable|string|max:1000',
                'transaction_id' => 'required_if:action,complete|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $withdrawRequest = WithdrawRequest::findOrFail($requestId);
            $action = $request->action;
            $adminId = $request->user()->id;

            if ($withdrawRequest->status !== WithdrawRequest::STATUS_PENDING && $action !== 'complete') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'این درخواست قابل پردازش نیست.'
                ], 400);
            }

            DB::beginTransaction();

            switch ($action) {
                case 'approve':
                    $success = $withdrawRequest->approve($adminId, $request->admin_notes);
                    $message = 'درخواست برداشت تایید شد.';
                    break;

                case 'reject':
                    $success = $withdrawRequest->reject($adminId, $request->admin_notes);
                    $message = 'درخواست برداشت رد شد و مبلغ به کیف پول کاربر برگردانده شد.';
                    break;

                case 'complete':
                    if ($withdrawRequest->status !== WithdrawRequest::STATUS_APPROVED) {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => 'فقط درخواست‌های تایید شده قابل تکمیل هستند.'
                        ], 400);
                    }
                    $success = $withdrawRequest->complete($adminId, $request->transaction_id, $request->admin_notes);
                    $message = 'درخواست برداشت تکمیل شد.';
                    break;

                default:
                    $success = false;
            }

            if ($success) {
                DB::commit();
                return response()->json([
                    'status' => 'success',
                    'message' => $message,
                    'data' => [
                        'request_id' => $withdrawRequest->id,
                        'new_status' => $withdrawRequest->status,
                    ]
                ]);
            } else {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'خطا در پردازش درخواست.'
                ], 400);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در پردازش درخواست برداشت: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get referral statistics
     */
    public function getStatistics(Request $request)
    {
        try {
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            $stats = $this->referralService->getAdminStats($dateFrom, $dateTo);

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در دریافت آمار: ' . $e->getMessage()
            ], 500);
        }
    }
}
    