<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use App\Services\UserFilterService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Morilog\Jalali\Jalalian;

class DiscountCodeController extends Controller
{
    /**
     * Convert Persian date to Carbon
     */
    private function convertPersianDate($persianDate)
    {
        \Log::info('convertPersianDate called with:', ['input' => $persianDate]);
        
        if (!$persianDate) {
            \Log::info('convertPersianDate: input is empty, returning null');
            return null;
        }
        
        // Convert Persian digits to English digits
        $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $englishDate = str_replace($persianDigits, $englishDigits, $persianDate);
        
        \Log::info('Converted digits:', ['persian' => $persianDate, 'english' => $englishDate]);
        
        try {
            $carbonDate = Jalalian::fromFormat('Y/m/d', $englishDate)->toCarbon();
            \Log::info('convertPersianDate successful:', [
                'input' => $persianDate,
                'english_digits' => $englishDate,
                'output' => $carbonDate->toDateTimeString()
            ]);
            return $carbonDate;
        } catch (\Exception $e) {
            \Log::error('convertPersianDate failed:', [
                'input' => $persianDate,
                'english_digits' => $englishDate,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = DiscountCode::with(['orders', 'salonUsages.salon'])
            ->withCount('salonUsages')
            ->orderBy('created_at', 'desc');

        // Search filter
        if ($request->filled('search')) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }

        // Status filter
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->where('is_active', true)
                          ->where(function($q) {
                              $q->whereNull('expires_at')
                                ->orWhere('expires_at', '>', now());
                          });
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'expired':
                    $query->where('is_active', true)
                          ->where('expires_at', '<', now());
                    break;
            }
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // User filter type
        if ($request->filled('user_filter_type')) {
            $query->where('user_filter_type', $request->user_filter_type);
        }

        // Date range filter
        if ($request->filled('created_from')) {
            $fromDate = $this->convertPersianDate($request->created_from);
            if ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            }
        }

        if ($request->filled('created_to')) {
            $toDate = $this->convertPersianDate($request->created_to);
            if ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            }
        }

        $discountCodes = $query->paginate(15)->appends($request->all());
            
        return view('admin.discount-codes.index', compact('discountCodes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(UserFilterService $filterService)
    {
        $provinces = \App\Models\Province::all();
        $businessCategories = \App\Models\BusinessCategory::all();

        // Initialize empty collections
        $cities = collect();
        $businessSubcategories = collect();
        $filteredSalons = null;

        // Check if filters are applied
        if (request()->has('filter_applied')) {
            // Load cities if province is selected
            if (request()->filled('province_id')) {
                $cities = \App\Models\City::where('province_id', request('province_id'))->get();
            }

            // Load subcategories if category is selected
            if (request()->filled('business_category_id')) {
                $businessSubcategories = \App\Models\BusinessSubcategory::where('category_id', request('business_category_id'))->get();
            }

            // Use UserFilterService to get filtered salons
            $filters = [
                'province_id' => request('province_id'),
                'city_id' => request('city_id'),
                'business_category_id' => request('business_category_id'),
                'business_subcategory_id' => request('business_subcategory_id'),
                'status' => request('status'),
                'sms_balance_status' => request('sms_balance_status'),
                'last_sms_purchase' => request('last_sms_purchase'),
                'monthly_sms_consumption' => request('monthly_sms_consumption'),
            ];

            $filteredSalons = $filterService->getFilteredSalons($filters)->get();
        }

        return view('admin.discount-codes.create', compact(
            'provinces', 
            'businessCategories', 
            'cities', 
            'businessSubcategories',
            'filteredSalons'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, UserFilterService $filterService)
    {
        \Log::info('=== Discount Code Store Method Started ===');
        \Log::info('Request data:', $request->all());
        
        // Convert Persian dates to Carbon before validation
        $data = $request->all();
        
        \Log::info('Original starts_at:', ['starts_at' => $request->starts_at]);
        \Log::info('Original expires_at:', ['expires_at' => $request->expires_at]);
        
        if ($request->filled('starts_at')) {
            $convertedStartsAt = $this->convertPersianDate($request->starts_at);
            \Log::info('Converted starts_at:', ['original' => $request->starts_at, 'converted' => $convertedStartsAt]);
            $data['starts_at'] = $convertedStartsAt;
            $request->merge(['starts_at' => $convertedStartsAt]);
        }
        if ($request->filled('expires_at')) {
            $convertedExpiresAt = $this->convertPersianDate($request->expires_at);
            \Log::info('Converted expires_at:', ['original' => $request->expires_at, 'converted' => $convertedExpiresAt]);
            $data['expires_at'] = $convertedExpiresAt;
            $request->merge(['expires_at' => $convertedExpiresAt]);
        }

        \Log::info('Data before validation:', $data);

        $request->validate([
            'code' => ['required', 'string', 'min:3', 'max:20', 'unique:discount_codes,code', 'regex:/^[A-Z0-9]+$/'],
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:0', function ($attribute, $value, $fail) use ($request) {
                if ($request->type === 'percentage' && $value > 100) {
                    $fail('درصد تخفیف نمی‌تواند بیشتر از 100 باشد.');
                }
            }],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string', 'max:500'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'user_filter_type' => ['required', 'in:all,filtered'],
        ], [
            'code.required' => 'کد تخفیف الزامی است.',
            'code.unique' => 'این کد تخفیف قبلاً استفاده شده است.',
            'code.regex' => 'کد تخفیف فقط می‌تواند شامل حروف بزرگ انگلیسی و اعداد باشد.',
            'code.min' => 'کد تخفیف باید حداقل 3 کاراکتر باشد.',
            'code.max' => 'کد تخفیف نمی‌تواند بیشتر از 20 کاراکتر باشد.',
            'type.required' => 'نوع تخفیف الزامی است.',
            'value.required' => 'مقدار تخفیف الزامی است.',
            'value.min' => 'مقدار تخفیف باید حداقل 0 باشد.',
            'min_order_amount.min' => 'حداقل مبلغ سفارش نمی‌تواند منفی باشد.',
            'max_discount_amount.min' => 'حداکثر مبلغ تخفیف نمی‌تواند منفی باشد.',
            'expires_at.after_or_equal' => 'تاریخ انقضا باید بعد از تاریخ شروع باشد.',
            'description.max' => 'توضیحات نمی‌تواند بیشتر از 500 کاراکتر باشد.',
            'usage_limit.min' => 'محدودیت استفاده باید حداقل 1 باشد.',
            'user_filter_type.required' => 'نوع هدف‌گذاری کاربر الزامی است.',
        ]);

        $data = $request->all();
        
        // Handle user filters
        if ($request->user_filter_type === 'filtered') {
            $filters = [];
            $filterMapping = [
                'filter_province_id' => 'province_id',
                'filter_city_id' => 'city_id', 
                'filter_business_category_id' => 'business_category_id',
                'filter_business_subcategory_id' => 'business_subcategory_id',
                'filter_status' => 'status',
                'filter_sms_balance_status' => 'sms_balance_status',
                'filter_last_sms_purchase' => 'last_sms_purchase',
                'filter_monthly_sms_consumption' => 'monthly_sms_consumption'
            ];
            
            foreach ($filterMapping as $formField => $dbField) {
                if ($request->filled($formField)) {
                    $filters[$dbField] = $request->$formField;
                }
            }
            
            $data['target_users'] = $filters;
        } else {
            $data['target_users'] = null;
        }

        $discountCode = DiscountCode::create($data);
        
        // Calculate target users count for display
        $discountTypeText = $data['type'] === 'percentage' ? intval($data['value']) . '% درصدی' : number_format($data['value']) . ' تومان مبلغ ثابت';
        
        if ($request->user_filter_type === 'filtered' && !empty($data['target_users'])) {
            $targetCount = $filterService->getFilteredSalonsCount($data['target_users']);
            $message = "کد تخفیف {$discountTypeText} با موفقیت ایجاد شد. تعداد کاربران هدف: {$targetCount}";
        } else {
            $message = "کد تخفیف {$discountTypeText} با موفقیت ایجاد شد. برای همه کاربران قابل استفاده است.";
        }
        
        return redirect()->route('admin.discount-codes.index')->with('success', $message);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DiscountCode $discountCode, UserFilterService $filterService)
    {
        $discountCode->load('orders');
        $filterOptions = $filterService->getFilterOptions();
        
        // Apply filters if provided
        $filteredSalons = null;
        if (request('filter_applied')) {
            $filters = [];
            $filterFields = ['province_id', 'city_id', 'business_category_id', 'business_subcategory_id', 'status', 'sms_balance_status', 'last_sms_purchase', 'monthly_sms_consumption'];
            
            foreach ($filterFields as $field) {
                if (request($field)) {
                    $filters[$field] = request($field);
                }
            }
            
            if (!empty($filters)) {
                $filteredSalons = $filterService->getFilteredSalons($filters, 50);
            }
        }
        
        // Load related data for filters
        if (request('province_id')) {
            $cities = City::where('province_id', request('province_id'))->get();
            $filterOptions['cities'] = $cities;
        }
        
        if (request('business_category_id')) {
            $businessSubcategories = BusinessSubcategory::where('business_category_id', request('business_category_id'))->get();
            $filterOptions['businessSubcategories'] = $businessSubcategories;
        }
        
        return view('admin.discount-codes.edit', array_merge(
            compact('discountCode', 'filteredSalons'), 
            $filterOptions
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DiscountCode $discountCode, UserFilterService $filterService)
    {
        // Convert Persian dates to Carbon before validation
        $data = $request->all();
        if ($request->filled('starts_at')) {
            $data['starts_at'] = $this->convertPersianDate($request->starts_at);
            $request->merge(['starts_at' => $data['starts_at']]);
        }
        if ($request->filled('expires_at')) {
            $data['expires_at'] = $this->convertPersianDate($request->expires_at);
            $request->merge(['expires_at' => $data['expires_at']]);
        }

        $request->validate([
            'code' => ['required', 'string', 'min:3', 'max:20', Rule::unique('discount_codes', 'code')->ignore($discountCode->id), 'regex:/^[A-Z0-9]+$/'],
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:0', function ($attribute, $value, $fail) use ($request) {
                if ($request->type === 'percentage' && $value > 100) {
                    $fail('درصد تخفیف نمی‌تواند بیشتر از 100 باشد.');
                }
            }],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string', 'max:500'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'user_filter_type' => ['required', 'in:all,filtered'],
        ], [
            'code.required' => 'کد تخفیف الزامی است.',
            'code.unique' => 'این کد تخفیف قبلاً استفاده شده است.',
            'code.regex' => 'کد تخفیف فقط می‌تواند شامل حروف بزرگ انگلیسی و اعداد باشد.',
            'code.min' => 'کد تخفیف باید حداقل 3 کاراکتر باشد.',
            'code.max' => 'کد تخفیف نمی‌تواند بیشتر از 20 کاراکتر باشد.',
            'type.required' => 'نوع تخفیف الزامی است.',
            'value.required' => 'مقدار تخفیف الزامی است.',
            'value.min' => 'مقدار تخفیف باید حداقل 0 باشد.',
            'min_order_amount.min' => 'حداقل مبلغ سفارش نمی‌تواند منفی باشد.',
            'max_discount_amount.min' => 'حداکثر مبلغ تخفیف نمی‌تواند منفی باشد.',
            'starts_at.date' => 'فرمت تاریخ شروع صحیح نیست.',
            'expires_at.date' => 'فرمت تاریخ انقضا صحیح نیست.',
            'expires_at.after_or_equal' => 'تاریخ انقضا باید بعد از تاریخ شروع باشد.',
            'usage_limit.min' => 'محدودیت استفاده باید حداقل 1 باشد.',
            'user_filter_type.required' => 'نوع هدف‌گذاری کاربر الزامی است.',
        ]);

        $data = $request->all();
        
        // Handle user filters
        if ($request->user_filter_type === 'filtered') {
            $filters = [];
            $filterMapping = [
                'filter_province_id' => 'province_id',
                'filter_city_id' => 'city_id', 
                'filter_business_category_id' => 'business_category_id',
                'filter_business_subcategory_id' => 'business_subcategory_id',
                'filter_status' => 'status',
                'filter_sms_balance_status' => 'sms_balance_status',
                'filter_last_sms_purchase' => 'last_sms_purchase',
                'filter_monthly_sms_consumption' => 'monthly_sms_consumption'
            ];
            
            foreach ($filterMapping as $formField => $dbField) {
                if ($request->filled($formField)) {
                    $filters[$dbField] = $request->$formField;
                }
            }
            
            $data['target_users'] = $filters;
        } else {
            $data['target_users'] = null;
        }

        $discountCode->update($data);
        
        // Calculate target users count for display
        $discountTypeText = $data['type'] === 'percentage' ? intval($data['value']) . '% درصدی' : number_format($data['value']) . ' تومان مبلغ ثابت';
        
        if ($request->user_filter_type === 'filtered' && !empty($data['target_users'])) {
            $targetCount = $filterService->getFilteredSalonsCount($data['target_users']);
            $message = "کد تخفیف {$discountTypeText} با موفقیت به‌روزرسانی شد. تعداد کاربران هدف: {$targetCount}";
        } else {
            $message = "کد تخفیف {$discountTypeText} با موفقیت به‌روزرسانی شد. برای همه کاربران قابل استفاده است.";
        }
        
        return redirect()->route('admin.discount-codes.index')->with('success', $message);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DiscountCode $discountCode)
    {
        $discountCode->delete();
        return redirect()->route('admin.discount-codes.index')->with('success', 'کد تخفیف با موفقیت حذف شد.');
    }

    /**
     * Preview target users for filters
     */
    public function previewTargetUsers(Request $request, UserFilterService $filterService)
    {
        \Log::info('=== NEW Preview Target Users Method Called ===');
        \Log::info('Request data:', $request->all());
        
        try {
            $filters = $request->only(['province_id', 'city_id', 'business_category_id', 'business_subcategory_id', 'status', 'sms_balance_status', 'last_sms_purchase', 'monthly_sms_consumption']);
            
            \Log::info('Extracted filters:', $filters);
            
            // Remove empty filters
            $filters = array_filter($filters, function ($value) {
                return $value !== null && $value !== '';
            });

            \Log::info('Filtered (non-empty) filters:', $filters);

        // Get filtered salons with full details
        $salons = $filterService->getFilteredSalons($filters)
            ->with(['owner', 'city.province', 'businessCategory', 'businessSubcategories'])
            ->get()
            ->map(function ($salon) {
                return [
                    'id' => $salon->id,
                    'name' => $salon->name,
                    'owner_name' => $salon->owner->name ?? 'نامشخص',
                    'phone' => $salon->phone,
                    'city_name' => $salon->city->name ?? 'نامشخص',
                    'province_name' => $salon->city->province->name ?? 'نامشخص',
                    'category_name' => $salon->businessCategory->name ?? 'نامشخص',
                    'subcategory_name' => $salon->businessSubcategories->pluck('name')->implode(', ') ?: '-',
                    'address' => $salon->address,
                    'status' => $salon->status,
                    'created_at_jalali' => \Morilog\Jalali\Jalalian::forge($salon->created_at)->format('Y/m/d'),
                ];
            });
            
            $count = $salons->count();
            $formattedFilters = $filterService->formatFiltersForDisplay($filters);

            \Log::info('NEW method - Salons count:', ['count' => $count]);
            \Log::info('NEW method - Sample salon data:', $salons->take(1)->toArray());

            return response()->json([
                'count' => $count,
                'salons' => $salons->values()->toArray(),
                'filters' => $formattedFilters,
                'message' => $count > 0 ? "تعداد {$count} سالن با این فیلترها پیدا شد." : 'هیچ سالنی با این فیلترها پیدا نشد.'
            ]);
        } catch (\Exception $e) {
            \Log::error('NEW method - Error in previewTargetUsers:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'count' => 0,
                'salons' => [],
                'filters' => [],
                'message' => 'خطا در بارگذاری اطلاعات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show target users list for a discount code
     */
    public function showTargetUsers(DiscountCode $discountCode, UserFilterService $filterService, Request $request)
    {
        if ($discountCode->user_filter_type === 'all' || !$discountCode->target_users) {
            // Show all salons if no filter is applied
            $query = Salon::with(['owner', 'city.province', 'smsBalance', 'smsTransactions', 'businessCategory', 'businessSubcategories']);
        } else {
            // Apply filters
            $query = $filterService->getFilteredSalons($discountCode->target_users);
        }

        // Apply additional search from request
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('phone', 'like', "%{$searchTerm}%")
                  ->orWhere('address', 'like', "%{$searchTerm}%")
                  ->orWhereHas('owner', function ($ownerQuery) use ($searchTerm) {
                      $ownerQuery->where('name', 'like', "%{$searchTerm}%")
                                ->orWhere('mobile', 'like', "%{$searchTerm}%");
                  })
                  ->orWhereHas('city', function ($cityQuery) use ($searchTerm) {
                      $cityQuery->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        $salons = $query->paginate(15);
        $formattedFilters = $filterService->formatFiltersForDisplay($discountCode->target_users ?? []);

        // Get salons that have actually used this discount code
        $usedSalons = $discountCode->salonUsages()
            ->with(['salon.owner', 'salon.city', 'order'])
            ->latest('used_at')
            ->get();

        return view('admin.discount-codes.target-users', compact('discountCode', 'salons', 'formattedFilters', 'usedSalons'));
    }
}
