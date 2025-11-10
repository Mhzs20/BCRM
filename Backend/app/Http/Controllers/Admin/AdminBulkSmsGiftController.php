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
use App\Models\Package;
use App\Models\UserPackage;
use Carbon\Carbon;
use Morilog\Jalali\Jalali; // Explicitly import Jalali
use Illuminate\Support\Facades\DB;

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

        if ($request->filled('min_sms_balance') || $request->filled('max_sms_balance')) {
            $query->whereHas('smsBalance', function ($q) use ($request) {
                if ($request->filled('min_sms_balance') && $request->filled('max_sms_balance')) {
                    $q->whereBetween('balance', [$request->min_sms_balance, $request->max_sms_balance]);
                } elseif ($request->filled('min_sms_balance')) {
                    $q->where('balance', '>=', $request->min_sms_balance);
                } elseif ($request->filled('max_sms_balance')) {
                    $q->where('balance', '<=', $request->max_sms_balance);
                }
            });
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

        if ($request->filled('gender')) {
            $query->whereHas('owner', function ($q) use ($request) {
                $q->where('gender', $request->gender);
            });
        }

        if ($request->filled('min_age') || $request->filled('max_age')) {
            $query->whereHas('owner', function ($q) use ($request) {
                if ($request->filled('min_age') && $request->filled('max_age')) {
                    $q->whereRaw("TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN ? AND ?", [$request->min_age, $request->max_age]);
                } elseif ($request->filled('min_age')) {
                    $q->whereRaw("TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= ?", [$request->min_age]);
                } elseif ($request->filled('max_age')) {
                    $q->whereRaw("TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?", [$request->max_age]);
                }
            });
        }

        $salons = $query->paginate(10); // Paginate the results
        
        // Get total count of filtered salons (for select all functionality)
        $totalFilteredCount = $query->count();

        // Get all active packages for the bulk activation feature
        $packages = Package::where('is_active', true)->get();

        return view('admin.bulk_sms_gift.index', compact('salons', 'provinces', 'cities', 'businessCategories', 'businessSubcategories', 'packages', 'totalFilteredCount'));
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
            'min_sms_balance' => 'nullable|integer|min:0',
            'max_sms_balance' => 'nullable|integer|min:0',
            'last_sms_purchase' => 'nullable|string|in:last_month,last_3_months,last_6_months,more_than_6_months,never',
            'monthly_sms_consumption' => 'nullable|string|in:high,medium,low',
            'gender' => 'nullable|in:male,female,other',
            'min_age' => 'nullable|integer|min:18|max:120',
            'max_age' => 'nullable|integer|min:18|max:120',
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

        if ($request->filled('min_sms_balance') || $request->filled('max_sms_balance')) {
            $query->whereHas('smsBalance', function ($q) use ($request) {
                if ($request->filled('min_sms_balance') && $request->filled('max_sms_balance')) {
                    $q->whereBetween('balance', [$request->min_sms_balance, $request->max_sms_balance]);
                } elseif ($request->filled('min_sms_balance')) {
                    $q->where('balance', '>=', $request->min_sms_balance);
                } elseif ($request->filled('max_sms_balance')) {
                    $q->where('balance', '<=', $request->max_sms_balance);
                }
            });
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

        if ($request->filled('gender')) {
            $query->whereHas('owner', function ($q) use ($request) {
                $q->where('gender', $request->gender);
            });
        }

        if ($request->filled('min_age') || $request->filled('max_age')) {
            $query->whereHas('owner', function ($q) use ($request) {
                if ($request->filled('min_age') && $request->filled('max_age')) {
                    $q->whereRaw("TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN ? AND ?", [$request->min_age, $request->max_age]);
                } elseif ($request->filled('min_age')) {
                    $q->whereRaw("TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= ?", [$request->min_age]);
                } elseif ($request->filled('max_age')) {
                    $q->whereRaw("TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?", [$request->max_age]);
                }
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

    private function getAgeRange(string $ageRange): array
    {
        return match ($ageRange) {
            '18-25' => [18, 25],
            '26-35' => [26, 35],
            '36-45' => [36, 45],
            '46-60' => [46, 60],
            '60+' => [60, 150], // Assuming max age 150
            default => [0, 150],
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

    public function bulkActivatePackage(Request $request, SmsService $smsService)
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id',
            'select_all_filtered' => 'nullable|in:0,1',
            'salon_ids' => 'nullable|array',
            'salon_ids.*' => 'exists:salons,id',
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|boolean',
            'province_id' => 'nullable|exists:provinces,id',
            'city_id' => 'nullable|exists:cities,id',
            'business_category_id' => 'nullable|exists:business_categories,id',
            'business_subcategory_id' => 'nullable|exists:business_subcategories,id',
            'sms_balance_status' => 'nullable|string|in:less_than_50,less_than_200,zero',
            'min_sms_balance' => 'nullable|integer|min:0',
            'max_sms_balance' => 'nullable|integer|min:0',
            'last_sms_purchase' => 'nullable|string|in:last_month,last_3_months,last_6_months,more_than_6_months,never',
            'monthly_sms_consumption' => 'nullable|string|in:high,medium,low',
            'gender' => 'nullable|in:male,female,other',
            'min_age' => 'nullable|integer|min:18|max:120',
            'max_age' => 'nullable|integer|min:18|max:120',
        ]);

        $query = Salon::query();

        // Apply filters from the form (same logic as sendGift)
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

        if ($request->filled('min_sms_balance') || $request->filled('max_sms_balance')) {
            $query->whereHas('smsBalance', function ($q) use ($request) {
                if ($request->filled('min_sms_balance') && $request->filled('max_sms_balance')) {
                    $q->whereBetween('balance', [$request->min_sms_balance, $request->max_sms_balance]);
                } elseif ($request->filled('min_sms_balance')) {
                    $q->where('balance', '>=', $request->min_sms_balance);
                } elseif ($request->filled('max_sms_balance')) {
                    $q->where('balance', '<=', $request->max_sms_balance);
                }
            });
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

        if ($request->filled('gender')) {
            $query->whereHas('owner', function ($q) use ($request) {
                $q->where('gender', $request->gender);
            });
        }

        if ($request->filled('min_age') || $request->filled('max_age')) {
            $query->whereHas('owner', function ($q) use ($request) {
                if ($request->filled('min_age') && $request->filled('max_age')) {
                    $q->whereRaw("TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN ? AND ?", [$request->min_age, $request->max_age]);
                } elseif ($request->filled('min_age')) {
                    $q->whereRaw("TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= ?", [$request->min_age]);
                } elseif ($request->filled('max_age')) {
                    $q->whereRaw("TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?", [$request->max_age]);
                }
            });
        }

        $salons = $query->get();

        // Check if user wants to select all filtered salons
        $selectAllFiltered = $request->select_all_filtered == '1';
        
        if (!$selectAllFiltered) {
            // If not selecting all, use only the specific salon_ids
            if ($request->filled('salon_ids')) {
                $salons = $salons->whereIn('id', $request->salon_ids);
            } else {
                return back()->with('error', 'لطفاً حداقل یک سالن را انتخاب کنید.');
            }
        }
        // If selectAllFiltered is true, use all filtered salons

        $package = Package::findOrFail($request->package_id);
        $durationDays = $package->duration_days ?? 365; // استفاده از duration_days پکیج
        $count = 0;
        $errors = [];

        foreach ($salons as $salon) {
            try {
                DB::beginTransaction();

                // Deactivate previous active packages for this salon
                UserPackage::where('salon_id', $salon->id)
                    ->where('status', 'active')
                    ->update(['status' => 'expired']);

                // Create new user package
                UserPackage::create([
                    'user_id' => $salon->user_id,
                    'salon_id' => $salon->id,
                    'package_id' => $package->id,
                    'amount_paid' => 0, // هدیه
                    'status' => 'active',
                    'purchased_at' => now(),
                    'expires_at' => now()->addDays($durationDays), // استفاده از روز به جای ماه
                ]);

                // If package has gift SMS, increment salon SMS balance
                if ($package->gift_sms_count > 0) {
                    $salonSmsBalance = SalonSmsBalance::firstOrCreate(
                        ['salon_id' => $salon->id],
                        ['balance' => 0]
                    );
                    $salonSmsBalance->increment('balance', $package->gift_sms_count);

                    // Create SMS transaction record for gift
                    SmsTransaction::create([
                        'salon_id' => $salon->id,
                        'type' => 'gift',
                        'amount' => $package->gift_sms_count,
                        'description' => "هدیه فعال‌سازی گروهی پکیج {$package->name}",
                        'status' => 'completed',
                    ]);
                }

                DB::commit();
                $count++;

            } catch (\Exception $e) {
                DB::rollBack();
                $errors[] = "خطا در فعال‌سازی پکیج برای سالن {$salon->name}: " . $e->getMessage();
            }
        }

        $message = "پکیج {$package->name} برای {$count} سالن با موفقیت فعال شد.";
        if (!empty($errors)) {
            $message .= " خطاها: " . implode(', ', $errors);
        }

        return back()->with('success', $message);
    }
}
