<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Salon;
use App\Models\User;
use App\Models\City;
use App\Models\Province;
use App\Models\BusinessCategory;
use App\Models\BusinessSubcategory;
use App\Models\SalonSmsBalance;
use Carbon\Carbon;
use App\Models\SmsTransaction;
use App\Models\SalonNote;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Hekmatinasser\Verta\Verta; // Using Hekmatinasser\Verta\Verta package

class AdminSalonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Salon::with(['user', 'city', 'province', 'businessCategory', 'businessSubcategories', 'smsBalance'])
                      ->leftJoin('sms_transactions', 'salons.id', '=', 'sms_transactions.salon_id')
                      ->selectRaw('salons.*,
                                  SUM(CASE WHEN (sms_transactions.type IN ("send", "deduction", "manual_send") 
                                               OR sms_transactions.sms_type IN ("send", "deduction", "manual_send", "manual_sms", "manual_reminder", "appointment_cancellation", "appointment_confirmation", "satisfaction_survey", "appointment_modification", "bulk"))
                                               AND sms_transactions.amount IS NOT NULL 
                                               AND sms_transactions.amount != ""
                                               THEN ABS(COALESCE(sms_transactions.amount, 0)) ELSE 0 END) as total_consumed,
                                  MAX(CASE WHEN sms_transactions.type = "purchase" OR sms_transactions.sms_type = "purchase" 
                                           THEN sms_transactions.created_at END) as last_purchase_date')
                      ->groupBy('salons.id');        if ($request->filled('search')) {
            $query->whereSearch($request->input('search'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status'));
        }

        if ($request->filled('province_id')) {
            $query->where('province_id', $request->input('province_id'));
        }

        if ($request->filled('city_id')) {
            $query->where('city_id', $request->input('city_id'));
        }

        if ($request->filled('business_category_id')) {
            $query->where('business_category_id', $request->input('business_category_id'));
        }

        if ($request->filled('business_subcategory_id')) {
            $subcategoryId = $request->input('business_subcategory_id');
            $query->whereHas('businessSubcategories', function ($q) use ($subcategoryId) {
                $q->where('business_subcategory_id', $subcategoryId);
            });
        }

        // New filters for owner gender and age range
        if ($request->filled('owner_gender')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('gender', $request->input('owner_gender'));
            });
        }

        if ($request->filled('owner_min_age') || $request->filled('owner_max_age')) {
            $query->whereHas('user', function ($q) use ($request) {
                if ($request->filled('owner_min_age') && $request->filled('owner_max_age')) {
                    $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN ? AND ?', [$request->owner_min_age, $request->owner_max_age]);
                } elseif ($request->filled('owner_min_age')) {
                    $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= ?', [$request->owner_min_age]);
                } elseif ($request->filled('owner_max_age')) {
                    $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?', [$request->owner_max_age]);
                }
            });
        }

        if ($request->filled('created_at_start')) {
            try {
                $jalaliDate = $request->input('created_at_start');
                $gregorianDate = Verta::parse($jalaliDate)->toCarbon()->startOfDay();
                $query->where('created_at', '>=', $gregorianDate);
            } catch (\Exception $e) {
                \Log::error('Invalid start date format: ' . $request->input('created_at_start'));
            }
        }

        if ($request->filled('created_at_end')) {
            try {
                $jalaliDate = $request->input('created_at_end');
                $gregorianDate = Verta::parse($jalaliDate)->toCarbon()->endOfDay();
                $query->where('created_at', '<=', $gregorianDate);
            } catch (\Exception $e) {
                \Log::error('Invalid end date format: ' . $request->input('created_at_end'));
            }
        }

        // Apply SMS balance range filter
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

        // Apply last SMS purchase date range filter
        if ($request->filled('last_sms_purchase_start') || $request->filled('last_sms_purchase_end')) {
            $query->whereHas('smsTransactions', function ($q) use ($request) {
                if ($request->filled('last_sms_purchase_start')) {
                    try {
                        $startDate = Verta::parse($request->last_sms_purchase_start)->toCarbon()->startOfDay();
                        $q->where('created_at', '>=', $startDate);
                    } catch (\Exception $e) {
                        \Log::error('Invalid last_sms_purchase_start date format: ' . $request->last_sms_purchase_start);
                    }
                }
                if ($request->filled('last_sms_purchase_end')) {
                    try {
                        $endDate = Verta::parse($request->last_sms_purchase_end)->toCarbon()->endOfDay();
                        $q->where('created_at', '<=', $endDate);
                    } catch (\Exception $e) {
                        \Log::error('Invalid last_sms_purchase_end date format: ' . $request->last_sms_purchase_end);
                    }
                }
                $q->where('type', 'purchase'); // Only purchase transactions
            });
        }

        // Apply monthly SMS consumption range filter
        if ($request->filled('min_monthly_consumption') || $request->filled('max_monthly_consumption')) {
            $query->whereHas('smsTransactions', function ($q) use ($request) {
                $q->selectRaw('salon_id, SUM(amount) as total_consumption')
                  ->whereBetween('created_at', [Carbon::now()->subMonth(), Carbon::now()])
                  ->where('type', '!=', 'purchase') // Exclude purchase transactions for consumption calculation
                  ->groupBy('salon_id')
                  ->having(function ($havingQ) use ($request) {
                      if ($request->filled('min_monthly_consumption') && $request->filled('max_monthly_consumption')) {
                          $havingQ->havingRaw('total_consumption BETWEEN ? AND ?', [$request->min_monthly_consumption, $request->max_monthly_consumption]);
                      } elseif ($request->filled('min_monthly_consumption')) {
                          $havingQ->havingRaw('total_consumption >= ?', [$request->min_monthly_consumption]);
                      } elseif ($request->filled('max_monthly_consumption')) {
                          $havingQ->havingRaw('total_consumption <= ?', [$request->max_monthly_consumption]);
                      }
                  });
            });
        }

        $salons = $query->paginate(10);

        $provinces = Province::all();
        $cities = City::all();
        $businessCategories = BusinessCategory::all();
        $businessSubcategories = BusinessSubcategory::all();

        return view('admin.salons.index', compact('salons', 'provinces', 'cities', 'businessCategories', 'businessSubcategories'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Salon $salon)
    {
        $businessCategories = BusinessCategory::all();
        $businessSubcategories = BusinessSubcategory::all();
        $provinces = Province::all();
        $cities = City::all(); // You might want to filter cities by province here

        $salon->load(['user', 'city', 'province', 'businessCategory', 'businessSubcategories', 'notes.user', 'smsBalance']);

        return view('admin.salons.show', compact('salon', 'businessCategories', 'businessSubcategories', 'provinces', 'cities'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Salon $salon)
    {
        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'mobile' => ['required', 'string', 'max:15'],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('salons')->ignore($salon->id)],
            'business_category_id' => 'required|exists:business_categories,id',
            'business_subcategory_ids' => 'nullable|array',
            'business_subcategory_ids.*' => 'exists:business_subcategories,id',
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required|exists:cities,id',
            'address' => 'nullable|string|max:255',
            'lat' => 'nullable|numeric',
            'lang' => 'nullable|numeric',
            'whatsapp' => 'nullable|string|max:255',
            'telegram' => 'nullable|string|max:255',
            'instagram' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'support_phone_number' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'owner_name' => 'required|string|max:255',
            'owner_email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($salon->user->id)],
        ], [
            'owner_email.unique' => 'ایمیل مالک قبلاً توسط کاربر دیگری ثبت شده است.',
            'email.unique' => 'این ایمیل قبلاً توسط سالن دیگری ثبت شده است.',
            'business_subcategory_ids.*.exists' => 'یکی از زیرمجموعه‌های فعالیت انتخاب شده نامعتبر است.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $salon->update([
            'name' => $request->name,
            'mobile' => $request->mobile,
            'email' => $request->email,
            'business_category_id' => $request->business_category_id,
            'province_id' => $request->province_id,
            'city_id' => $request->city_id,
            'address' => $request->address,
            'lat' => $request->lat,
            'lang' => $request->lang,
            'whatsapp' => $request->whatsapp,
            'telegram' => $request->telegram,
            'instagram' => $request->instagram,
            'website' => $request->website,
            'support_phone_number' => $request->support_phone_number,
            'bio' => $request->bio,
        ]);

        // Sync business subcategories (for multi-select)
        $salon->businessSubcategories()->sync($request->input('business_subcategory_ids', []));

        // Update owner (user) information
        if ($salon->user) {
            $salon->user->update([
                'name' => $request->owner_name,
                'email' => $request->owner_email,
            ]);
            $salon->user->refresh();
        } else {
            // Handle case where owner might not exist (e.g., create a new user)
            // For now, we assume an owner always exists for an existing salon.
        }

        return redirect()->route('admin.salons.show', $salon->id)->with('success', 'اطلاعات سالن با موفقیت به‌روزرسانی شد.');
    }

    /**
     * Toggle the active status of the specified salon.
     */
    public function toggleStatus(Salon $salon)
    {
        $salon->is_active = !$salon->is_active;
        $salon->save();

        return back()->with('success', 'وضعیت سالن با موفقیت تغییر یافت.');
    }

    /**
     * Reset the password for the salon's owner.
     */
    public function resetPassword(Request $request, Salon $salon)
    {
        $request->validate([
            'new_password' => 'required|string|min:8|confirmed',
        ], [
            'new_password.required' => 'رمز عبور جدید الزامی است.',
            'new_password.min' => 'رمز عبور جدید باید حداقل ۸ کاراکتر باشد.',
            'new_password.confirmed' => 'تایید رمز عبور جدید با رمز عبور مطابقت ندارد.',
        ]);

        $salon->user->password = Hash::make($request->new_password);
        $salon->user->save();

        return back()->with('success', 'رمز عبور مالک سالن با موفقیت بازنشانی شد.');
    }

    /**
     * Display purchase history for the specified salon.
     */
    public function purchaseHistory(Salon $salon)
    {
        // Get completed orders for this salon (new payment system)
        $purchaseTransactions = $salon->orders()
                                      ->with(['smsPackage', 'transactions' => function($query) {
                                          $query->where('status', 'completed');
                                      }])
                                      ->where('status', 'paid')
                                      ->latest()
                                      ->paginate(10);

        return view('admin.salons.purchase_history', compact('salon', 'purchaseTransactions'));
    }

    /**
     * Send bulk SMS gift to filtered salons.
     */
    public function bulkSmsGift(Request $request, SmsService $smsService)
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
            'message' => 'nullable|string|max:500',
            'filter_status' => 'nullable|boolean',
            'filter_city_id' => 'nullable|exists:cities,id',
            'filter_salon_id' => 'nullable|exists:salons,id',
            'filter_owner_gender' => 'nullable|in:male,female,other',
            'filter_owner_min_age' => 'nullable|integer|min:18|max:120',
            'filter_owner_max_age' => 'nullable|integer|min:18|max:120',
        ]);

        $query = Salon::query();

        if ($request->filled('filter_salon_id')) {
            $query->where('id', $request->filter_salon_id);
        } else {
            if ($request->filled('filter_status')) {
                $query->where('is_active', $request->filter_status);
            }
            if ($request->filled('filter_city_id')) {
                $query->where('city_id', $request->filter_city_id);
            }
            if ($request->filled('filter_owner_gender')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('gender', $request->filter_owner_gender);
                });
            }
            if ($request->filled('filter_owner_min_age') || $request->filled('filter_owner_max_age')) {
                $query->whereHas('user', function ($q) use ($request) {
                    if ($request->filled('filter_owner_min_age') && $request->filled('filter_owner_max_age')) {
                        $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN ? AND ?', [$request->filter_owner_min_age, $request->filter_owner_max_age]);
                    } elseif ($request->filled('filter_owner_min_age')) {
                        $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= ?', [$request->filter_owner_min_age]);
                    } elseif ($request->filled('filter_owner_max_age')) {
                        $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?', [$request->filter_owner_max_age]);
                    }
                });
            }
        }

        $salons = $query->get();
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
                'type' => 'gift',
                'amount' => $amount,
                'description' => 'شارژ هدیه توسط ادمین' . ($message ? ': ' . $message : ''),
                'receptor' => $salon->user->mobile,
                'content' => $message,
                'status' => 'delivered',
            ]);

            // Optionally send a notification SMS
            if ($message && $salon->user->mobile) {
                $smsService->sendSms($salon->user->mobile, $message);
            }
            $count++;
        }

        return back()->with('success', "شارژ پیامک هدیه برای {$count} سالن با موفقیت انجام شد.");
    }

    /**
     * Store a new note for the specified salon.
     */
    public function storeNote(Request $request, Salon $salon)
    {
        $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $salon->notes()->create([
            'user_id' => auth()->id(),
            'content' => $request->note,
        ]);

        return back()->with('success', 'یادداشت با موفقیت ثبت شد.');
    }

    /**
     * Add SMS credit to the specified salon.
     */
    public function addSmsCredit(Request $request, Salon $salon)
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
            'description' => 'nullable|string|max:500',
        ]);

        $smsBalance = $salon->smsBalance()->firstOrCreate(
            ['salon_id' => $salon->id],
            ['balance' => 0]
        );
        $smsBalance->balance += $request->amount;
        $smsBalance->save();

        // Reload the salon and its smsBalance to ensure fresh data
        $salon->refresh();
        $salon->load('smsBalance');

        SmsTransaction::create([
            'salon_id' => $salon->id,
            'type' => 'gift',
            'amount' => $request->amount,
            'description' => $request->description ?? 'شارژ هدیه توسط ادمین',
            'status' => 'completed',
        ]);

        return back()->with('success', 'اعتبار پیامک با موفقیت به سالن اضافه شد.');
    }

    /**
     * Reduce SMS credit from the specified salon.
     */
    public function reduceSmsCredit(Request $request, Salon $salon)
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
            'description' => 'nullable|string|max:500',
        ]);

        $smsBalance = $salon->smsBalance()->firstOrCreate(
            ['salon_id' => $salon->id],
            ['balance' => 0]
        );

        // Check if salon has enough balance
        if ($smsBalance->balance < $request->amount) {
            return back()->with('error', 'موجودی سالن برای کاهش این مقدار کافی نیست. موجودی فعلی: ' . $smsBalance->balance);
        }

        $smsBalance->balance -= $request->amount;
        $smsBalance->save();

        // Reload the salon and its smsBalance to ensure fresh data
        $salon->refresh();
        $salon->load('smsBalance');

        SmsTransaction::create([
            'salon_id' => $salon->id,
            'type' => 'deduction',
            'amount' => -$request->amount, // Store as negative value to indicate deduction
            'description' => $request->description ?? 'کسر اعتبار توسط ادمین',
            'status' => 'completed',
        ]);

        return back()->with('success', 'اعتبار پیامک با موفقیت از سالن کاهش یافت.');
    }

    /**
     * Get active discount codes for a specific salon
     */
    public function getActiveDiscountCodes(Request $request, Salon $salon)
    {
        try {
            // Load the salon with its related models
            $salon->load(['user', 'city.province', 'province', 'businessCategory', 'businessSubcategories', 'smsBalance']);
            
            // Get all valid discount codes (using the isValid method)
            $allDiscountCodes = \App\Models\DiscountCode::where('is_active', true)
                ->withCount('salonUsages')
                ->get();
                
            // Filter to only valid codes (proper date checking)
            $activeDiscountCodes = $allDiscountCodes->filter(function($code) {
                return $code->isValid();
            });

        // Filter codes that can be used by this salon
        $availableDiscountCodes = $activeDiscountCodes->filter(function($discountCode) use ($salon) {
            // Check if this discount code can be used by the salon
            if (!$salon->user) {
                return false;
            }
            
            // Check directly with salon filters
            return $discountCode->canSalonUse($salon);
        });

        // Transform the data to include additional information
        $transformedCodes = $availableDiscountCodes->map(function($code) use ($salon) {
            return [
                'id' => $code->id,
                'code' => $code->code,
                'type' => $code->type,
                'value' => $code->value,
                'is_active' => $code->is_active,
                'description' => $code->description,
                'starts_at' => $code->starts_at ? $code->starts_at->toISOString() : null,
                'expires_at' => $code->expires_at ? $code->expires_at->toISOString() : null,
                'usage_limit' => $code->usage_limit,
                'usage_count' => $code->salon_usages_count ?? 0,
                'min_order_amount' => $code->min_order_amount,
                'max_discount_amount' => $code->max_discount_amount,
                'user_filter_type' => $code->user_filter_type,
                'target_users' => $code->target_users,
                'has_been_used_by_salon' => $code->hasBeenUsedBySalon($salon->id),
                'remaining_uses' => $code->usage_limit ? max(0, $code->usage_limit - ($code->salon_usages_count ?? 0)) : null,
            ];
        });

        if ($request->ajax()) {
            return response()->json([
                'salon' => [
                    'id' => $salon->id,
                    'name' => $salon->name,
                    'city' => $salon->city->name ?? null,
                    'province' => $salon->province->name ?? ($salon->city->province->name ?? null),
                    'business_category' => $salon->businessCategory->name ?? null,
                ],
                'discountCodes' => $transformedCodes->values(),
                'totalCodes' => $transformedCodes->count(),
                'summary' => [
                    'total_available' => $transformedCodes->count(),
                    'already_used' => $transformedCodes->where('has_been_used_by_salon', true)->count(),
                    'can_still_use' => $transformedCodes->where('has_been_used_by_salon', false)->count(),
                ]
            ]);
        }

            return response()->json([
                'salon' => [
                    'id' => $salon->id,
                    'name' => $salon->name,
                    'city' => $salon->city->name ?? null,
                    'province' => $salon->province->name ?? ($salon->city->province->name ?? null),
                    'business_category' => $salon->businessCategory->name ?? null,
                ],
                'discountCodes' => $transformedCodes->values(),
                'totalCodes' => $transformedCodes->count(),
                'summary' => [
                    'total_available' => $transformedCodes->count(),
                    'already_used' => $transformedCodes->where('has_been_used_by_salon', true)->count(),
                    'can_still_use' => $transformedCodes->where('has_been_used_by_salon', false)->count(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'خطا در بارگذاری کدهای تخفیف',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get age range from string value
     */
    private function getAgeRange($range)
    {
        $ranges = [
            '18-25' => ['min' => 18, 'max' => 25],
            '26-35' => ['min' => 26, 'max' => 35],
            '36-45' => ['min' => 36, 'max' => 45],
            '46-55' => ['min' => 46, 'max' => 55],
            '56-65' => ['min' => 56, 'max' => 65],
            '66+' => ['min' => 66, 'max' => 120],
        ];

        return $ranges[$range] ?? null;
    }
}
