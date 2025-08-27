<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Province; // Import Province model
use App\Models\Salon;
use App\Models\SalonSmsBalance;
use App\Models\SmsTransaction;
use App\Services\SmsService;
use Illuminate\Http\Request;
use App\Models\BusinessCategory;
use App\Models\BusinessSubcategory;
use Carbon\Carbon;
use Morilog\Jalali\Jalali; // Explicitly import Jalali

class AdminBulkSmsGiftController extends Controller
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

        $salons = $query->paginate(10); // Paginate the results

        return view('admin.bulk_sms_gift.index', compact('salons', 'provinces', 'cities', 'businessCategories', 'businessSubcategories'));
    }

    public function sendGift(Request $request, SmsService $smsService)
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
            'message' => 'nullable|string|max:500',
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

        $query = Salon::query();

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
            }, '>=', 1); // Ensure there's at least one smsBalance record
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

        $amount = $request->amount;
        $message = $request->message;
        $count = 0;

        foreach ($salons as $salon) {
            // Ensure salon has an SMS balance record, create if not
            $smsBalance = $salon->smsBalance()->firstOrCreate(['salon_id' => $salon->id], ['balance' => 0]);
            $smsBalance->balance += $amount;
            $smsBalance->save();

            // Record transaction
            SmsTransaction::create([
                'salon_id' => $salon->id,
                'sms_type' => 'gift',
                'amount' => $amount,
                'description' => 'شارژ هدیه گروهی توسط ادمین' . ($message ? ': ' . $message : ''),
                'receptor' => $salon->mobile,
                'content' => $message,
                'status' => 'delivered',
            ]);

            // Optionally send a notification SMS
            if ($message && $salon->mobile) {
                $smsService->sendSms($salon->mobile, $message);
            }
            $count++;
        }

        return back()->with('success', "شارژ پیامک هدیه برای {$count} سالن با موفقیت انجام شد.");
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

    public function giftHistory(Request $request)
    {
        $giftTransactions = SmsTransaction::with('salon.owner')
            ->where('sms_type', 'gift')
            ->latest()
            ->paginate(10);

        return view('admin.bulk_sms_gift.history', compact('giftTransactions'));
    }
}
