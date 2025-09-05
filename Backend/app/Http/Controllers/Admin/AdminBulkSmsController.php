<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Province;
use App\Models\Salon;
use App\Models\SmsTransaction;
use App\Services\SmsService;
use Illuminate\Http\Request;
use App\Models\BusinessCategory;
use App\Models\BusinessSubcategory;
use Carbon\Carbon;

class AdminBulkSmsController extends Controller
{
    public function index(Request $request)
    {
        $provinces = Province::all();
        $businessCategories = BusinessCategory::all();

        $cities = collect();
        if ($request->filled('province_id')) {
            $cities = City::where('province_id', $request->province_id)->get();
        }

        $businessSubcategories = collect();
        if ($request->filled('business_category_id')) {
            $businessSubcategories = BusinessSubcategory::where('category_id', $request->business_category_id)->get();
        }

        $query = Salon::with(['owner', 'city', 'smsBalance', 'smsTransactions']);

        if ($request->filled('search')) {
            $query->whereSearch($request->search);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        if ($request->filled('province_id')) {
            $query->whereHas('city', function ($q) use ($request) {
                $q->where('province_id', $request->province_id);
            });
        }

        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        if ($request->filled('business_category_id')) {
            $query->where('business_category_id', $request->business_category_id);
        }

        if ($request->filled('business_subcategory_id')) {
            $query->where('business_subcategory_id', $request->business_subcategory_id);
        }

        if ($request->filled('sms_balance_status')) {
            $query->whereHas('smsBalance', function ($q) use ($request) {
                if ($request->sms_balance_status == 'less_than_50') {
                    $q->where('balance', '<', 50);
                } elseif ($request->sms_balance_status == 'less_than_200') {
                    $q->where('balance', '<', 200);
                } elseif ($request->sms_balance_status == 'zero') {
                    $q->where('balance', 0);
                }
            }, '>=', 1);
        }

        if ($request->filled('last_sms_purchase')) {
            $now = Carbon::now();
            if ($request->last_sms_purchase == 'last_month') {
                $query->whereHas('smsTransactions', function ($q) use ($now) {
                    $q->where('created_at', '>=', $now->subMonth());
                });
            } elseif ($request->last_sms_purchase == 'last_3_months') {
                $query->whereHas('smsTransactions', function ($q) use ($now) {
                    $q->where('created_at', '>=', $now->subMonths(3));
                });
            } elseif ($request->last_sms_purchase == 'last_6_months') {
                $query->whereHas('smsTransactions', function ($q) use ($now) {
                    $q->where('created_at', '>=', $now->subMonths(6));
                });
            } elseif ($request->last_sms_purchase == 'more_than_6_months') {
                $query->whereHas('smsTransactions', function ($q) use ($now) {
                    $q->where('created_at', '<', $now->subMonths(6));
                });
            } elseif ($request->last_sms_purchase == 'never') {
                $query->whereDoesntHave('smsTransactions', function ($q) {
                    $q->where('sms_type', 'purchase');
                });
            }
        }

        if ($request->filled('monthly_sms_consumption')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('smsTransactions', function ($subQ) use ($request) {
                    $subQ->selectRaw('SUM(amount) as total_amount')
                        ->whereBetween('created_at', [Carbon::now()->subMonth(), Carbon::now()])
                        ->groupBy('salon_id')
                        ->havingRaw($this->getMonthlyConsumptionCondition($request->monthly_sms_consumption));
                });
            });
        }

        $salons = $query->paginate(10);

        return view('admin.bulk_sms.index', compact('salons', 'provinces', 'cities', 'businessCategories', 'businessSubcategories'));
    }

    public function sendSms(Request $request, SmsService $smsService)
    {
        $request->validate([
            'message' => 'required|string|max:500',
            'salon_ids' => 'nullable|array',
            'salon_ids.*' => 'exists:salons,id',
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|boolean',
            'province_id' => 'nullable|exists:provinces,id',
            'city_id' => 'nullable|exists:cities,id',
            'business_category_id' => 'nullable|exists:business_categories,id',
            'business_subcategory_id' => 'nullable|exists:business_subcategories,id',
            'sms_balance_status' => 'nullable|string|in:less_than_50,less_than_200,zero',
            'last_sms_purchase' => 'nullable|string|in:last_month,last_3_months,last_6_months,more_than_6_months,never',
            'monthly_sms_consumption' => 'nullable|string|in:high,medium,low',
        ]);

        $query = Salon::with(['owner', 'city', 'smsBalance', 'smsTransactions']);

        // Apply filters from the form
        if ($request->filled('search')) {
            $query->whereSearch($request->search);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        if ($request->filled('province_id')) {
            $query->whereHas('city', function ($q) use ($request) {
                $q->where('province_id', $request->province_id);
            });
        }

        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        if ($request->filled('business_category_id')) {
            $query->where('business_category_id', $request->business_category_id);
        }

        if ($request->filled('business_subcategory_id')) {
            $query->where('business_subcategory_id', $request->business_subcategory_id);
        }

        if ($request->filled('sms_balance_status')) {
            $query->whereHas('smsBalance', function ($q) use ($request) {
                if ($request->sms_balance_status == 'less_than_50') {
                    $q->where('balance', '<', 50);
                } elseif ($request->sms_balance_status == 'less_than_200') {
                    $q->where('balance', '<', 200);
                } elseif ($request->sms_balance_status == 'zero') {
                    $q->where('balance', 0);
                }
            }, '>=', 1);
        }

        if ($request->filled('last_sms_purchase')) {
            $now = Carbon::now();
            if ($request->last_sms_purchase == 'last_month') {
                $query->whereHas('smsTransactions', function ($q) use ($now) {
                    $q->where('created_at', '>=', $now->subMonth());
                });
            } elseif ($request->last_sms_purchase == 'last_3_months') {
                $query->whereHas('smsTransactions', function ($q) use ($now) {
                    $q->where('created_at', '>=', $now->subMonths(3));
                });
            } elseif ($request->last_sms_purchase == 'last_6_months') {
                $query->whereHas('smsTransactions', function ($q) use ($now) {
                    $q->where('created_at', '>=', $now->subMonths(6));
                });
            } elseif ($request->last_sms_purchase == 'more_than_6_months') {
                $query->whereHas('smsTransactions', function ($q) use ($now) {
                    $q->where('created_at', '<', $now->subMonths(6));
                });
            } elseif ($request->last_sms_purchase == 'never') {
                $query->doesntHave('smsTransactions');
            }
        }

        if ($request->filled('monthly_sms_consumption')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('smsTransactions', function ($subQ) use ($request) {
                    $subQ->selectRaw('SUM(amount) as total_amount')
                        ->whereBetween('created_at', [Carbon::now()->subMonth(), Carbon::now()])
                        ->groupBy('salon_id')
                        ->havingRaw($this->getMonthlyConsumptionCondition($request->monthly_sms_consumption));
                });
            });
        }

        $salons = $query->get();

        // If specific salon_ids are provided, filter the results further
        if ($request->filled('salon_ids')) {
            $salons = $salons->whereIn('id', $request->salon_ids);
        }

        $message = $request->message;
        $successCount = 0;
        $failureCount = 0;
        $errors = [];

        foreach ($salons as $salon) {
            if ($salon->owner && $salon->owner->mobile) {
                try {
                    // Send SMS
                    $result = $smsService->sendSms($salon->owner->mobile, $message);
                    
                    // Record transaction
                    SmsTransaction::create([
                        'salon_id' => $salon->id,
                        'sms_type' => 'bulk',
                        'amount' => 1, // One SMS per salon
                        'description' => 'پیامک گروهی توسط ادمین',
                        'receptor' => $salon->owner->mobile,
                        'content' => $message,
                        'status' => $result ? 'delivered' : 'failed',
                    ]);

                    if ($result) {
                        $successCount++;
                    } else {
                        $failureCount++;
                        $errors[] = "خطا در ارسال پیامک به {$salon->name} ({$salon->owner->mobile})";
                    }
                } catch (\Exception $e) {
                    $failureCount++;
                    $errors[] = "خطا در ارسال پیامک به {$salon->name}: " . $e->getMessage();
                    
                    // Record failed transaction
                    SmsTransaction::create([
                        'salon_id' => $salon->id,
                        'sms_type' => 'bulk',
                        'amount' => 1,
                        'description' => 'پیامک گروهی توسط ادمین (ناموفق)',
                        'receptor' => $salon->owner->mobile,
                        'content' => $message,
                        'status' => 'failed',
                    ]);
                }
            } else {
                $failureCount++;
                $errors[] = "شماره موبایل برای مالک سالن {$salon->name} تعریف نشده است";
            }
        }

        $message = "پیامک گروهی ارسال شد. موفق: {$successCount}، ناموفق: {$failureCount}";
        
        if ($failureCount > 0 && count($errors) > 0) {
            $message .= "\nخطاها:\n" . implode("\n", array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $message .= "\n... و " . (count($errors) - 5) . " خطای دیگر";
            }
        }

        if ($successCount > 0) {
            return back()->with('success', $message);
        } else {
            return back()->with('error', $message);
        }
    }

    private function getMonthlyConsumptionCondition(string $status): string
    {
        return match ($status) {
            'high' => 'total_amount > 500',
            'medium' => 'total_amount >= 100 AND total_amount <= 500',
            'low' => 'total_amount < 100',
            default => '',
        };
    }

    public function history(Request $request)
    {
        $bulkSmsTransactions = SmsTransaction::with('salon.owner')
            ->where('sms_type', 'bulk')
            ->latest()
            ->get();

        // Group by content and created_at to show bulk sends together
        $groupedTransactions = $bulkSmsTransactions->groupBy(function ($transaction) {
            return $transaction->content . '|' . $transaction->created_at->format('Y-m-d H:i');
        })->map(function ($group) {
            $firstTransaction = $group->first();
            return (object) [
                'content' => $firstTransaction->content,
                'created_at' => $firstTransaction->created_at,
                'success_count' => $group->where('status', 'delivered')->count(),
                'failed_count' => $group->where('status', 'failed')->count(),
                'total_count' => $group->count(),
                'transactions' => $group
            ];
        })->values()->sortByDesc('created_at');

        // Paginate manually
        $perPage = 10;
        $currentPage = $request->get('page', 1);
        $total = $groupedTransactions->count();
        $offset = ($currentPage - 1) * $perPage;
        $items = $groupedTransactions->slice($offset, $perPage);

        $paginatedTransactions = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'pageName' => 'page']
        );

        return view('admin.bulk_sms.history', compact('paginatedTransactions'));
    }
}
