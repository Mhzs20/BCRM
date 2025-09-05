@extends('admin.layouts.app')

@section('title', 'ایجاد کد تخفیف جدید')

@section('header')
    <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            ایجاد کد تخفیف جدید
        </h2>
        <a href="{{ route('admin.discount-codes.index') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500">
            <i class="ri-arrow-right-line text-lg ml-2"></i>
            بازگشت به لیست
        </a>
    </div>
@endsection

@section('content')
    <div class="bg-white shadow-sm rounded-lg">
        <div class="p-6">
            <!-- Display Validation Errors -->
            @if($errors->any())
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <strong class="font-bold">خطاهای زیر رخ داده است:</strong>
                    <ul class="list-disc list-inside mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.discount-codes.store') }}" method="POST">
                @csrf
                
                <div class="space-y-6">
                    <!-- Discount Code -->
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 mb-1">کد تخفیف</label>
                        <input type="text" 
                               name="code" 
                               id="code"
                               value="{{ old('code') }}"
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('code') border-red-500 @enderror">
                        @error('code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">کد منحصربه‌فرد برای تخفیف</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">توضیحات</label>
                        <textarea name="description" 
                                  id="description"
                                  rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Discount Type and Value -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">نوع تخفیف</label>
                            <select name="type" id="type" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('type') border-red-500 @enderror">
                                <option value="percentage" {{ old('type') === 'percentage' ? 'selected' : '' }}>درصدی</option>
                                <option value="fixed" {{ old('type') === 'fixed' ? 'selected' : '' }}>مبلغ ثابت</option>
                            </select>
                            @error('type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="value" class="block text-sm font-medium text-gray-700 mb-1">مقدار تخفیف</label>
                            <input type="number" 
                                   name="value" 
                                   id="value"
                                   value="{{ old('value') }}"
                                   required
                                   min="0"
                                   step="0.01"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('value') border-red-500 @enderror">
                            @error('value')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">برای درصدی: عدد بین 0 تا 100، برای مبلغ ثابت: مبلغ به تومان</p>
                        </div>
                    </div>

                    <!-- Minimum Order Amount -->
                    <div>
                        <label for="min_order_amount" class="block text-sm font-medium text-gray-700 mb-1">حداقل مبلغ سفارش (تومان)</label>
                        <input type="number" 
                               name="min_order_amount" 
                               id="min_order_amount"
                               value="{{ old('min_order_amount') }}"
                               min="0"
                               step="1000"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('min_order_amount') border-red-500 @enderror">
                        @error('min_order_amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">حداقل مبلغ سفارش برای استفاده از این کد</p>
                    </div>

                    <!-- Date Range -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="starts_at" class="block text-sm font-medium text-gray-700 mb-1">تاریخ شروع</label>
                            <input type="text" 
                                   name="starts_at" 
                                   id="starts_at"
                                   value="{{ old('starts_at') }}"
                                   class="persian-date-picker w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('starts_at') border-red-500 @enderror">
                            @error('starts_at')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">اگر خالی بگذارید، کد از همین حالا فعال می‌شود</p>
                        </div>

                        <div>
                            <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-1">تاریخ انقضا</label>
                            <input type="text" 
                                   name="expires_at" 
                                   id="expires_at"
                                   value="{{ old('expires_at') }}"
                                   class="persian-date-picker w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('expires_at') border-red-500 @enderror">
                            @error('expires_at')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">اگر خالی بگذارید، کد هیچ‌وقت منقضی نمی‌شود</p>
                        </div>
                    </div>

                    <!-- Usage Limit -->
                    <div>
                        <label for="usage_limit" class="block text-sm font-medium text-gray-700 mb-1">محدودیت تعداد سالن</label>
                        <input type="number" 
                               name="usage_limit" 
                               id="usage_limit"
                               value="{{ old('usage_limit') }}"
                               min="1"
                               placeholder="مثال: 100"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('usage_limit') border-red-500 @enderror">
                        @error('usage_limit')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">حداکثر تعداد سالن‌هایی که می‌توانند از این کد استفاده کنند (هر سالن فقط یکبار)</p>
                    </div>

                    <!-- User Targeting -->
                    <div class="space-y-4">
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">هدف‌گذاری کاربران</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">نوع هدف‌گذاری</label>
                                    <div class="space-y-2">
                                        <label class="flex items-center">
                                            <input type="radio" name="user_filter_type" value="all" 
                                                   {{ old('user_filter_type', 'all') === 'all' ? 'checked' : '' }}
                                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                            <span class="mr-2 text-sm text-gray-900">همه کاربران</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" name="user_filter_type" value="filtered" 
                                                   {{ old('user_filter_type') === 'filtered' || request('filter_applied') ? 'checked' : '' }}
                                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                            <span class="mr-2 text-sm text-gray-900">کاربران فیلتر شده</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- Hidden filter fields for submission --}}
                            <div class="hidden">
                                <input type="hidden" name="filter_province_id" value="{{ request('province_id') }}">
                                <input type="hidden" name="filter_city_id" value="{{ request('city_id') }}">
                                <input type="hidden" name="filter_business_category_id" value="{{ request('business_category_id') }}">
                                <input type="hidden" name="filter_business_subcategory_id" value="{{ request('business_subcategory_id') }}">
                                <input type="hidden" name="filter_status" value="{{ request('status') }}">
                                <input type="hidden" name="filter_sms_balance_status" value="{{ request('sms_balance_status') }}">
                                <input type="hidden" name="filter_last_sms_purchase" value="{{ request('last_sms_purchase') }}">
                                <input type="hidden" name="filter_monthly_sms_consumption" value="{{ request('monthly_sms_consumption') }}">
                            </div>
                        </div>
                    </div>

                    <!-- Active Status -->
                    <div class="flex items-center">
                        <input type="checkbox" 
                               name="is_active" 
                               id="is_active"
                               value="1"
                               {{ old('is_active', '1') === '1' ? 'checked' : '' }}
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="is_active" class="mr-2 block text-sm text-gray-900">
                            کد تخفیف فعال باشد
                        </label>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
                        <a href="{{ route('admin.discount-codes.index') }}"
                           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            انصراف
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="ri-save-line text-lg ml-2"></i>
                            ایجاد کد تخفیف
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Filter Options (outside main form) -->
    <div id="filter-options" class="mt-6 bg-white shadow-sm rounded-lg" style="display: {{ request('filter_applied') ? 'block' : 'none' }};">
        <div class="p-6">
            <h4 class="text-lg font-medium text-gray-900 mb-4">فیلتر سالن‌ها</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                <div>
                    <label for="filter_province_id" class="block text-sm font-medium text-gray-700 mb-1">استان</label>
                    <select id="filter_province_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">همه استان‌ها</option>
                        @foreach($provinces as $province)
                            <option value="{{ $province->id }}" {{ request('province_id') == $province->id ? 'selected' : '' }}>
                                {{ $province->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_city_id" class="block text-sm font-medium text-gray-700 mb-1">شهر</label>
                    <select id="filter_city_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">همه شهرها</option>
                        @if(isset($cities))
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}" {{ request('city_id') == $city->id ? 'selected' : '' }}>
                                    {{ $city->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div>
                    <label for="filter_business_category_id" class="block text-sm font-medium text-gray-700 mb-1">دسته‌بندی کسب‌وکار</label>
                    <select id="filter_business_category_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">همه دسته‌ها</option>
                        @foreach($businessCategories as $category)
                            <option value="{{ $category->id }}" {{ request('business_category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_business_subcategory_id" class="block text-sm font-medium text-gray-700 mb-1">زیردسته کسب‌وکار</label>
                    <select id="filter_business_subcategory_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">همه زیردسته‌ها</option>
                        @if(isset($businessSubcategories))
                            @foreach($businessSubcategories as $subcategory)
                                <option value="{{ $subcategory->id }}" {{ request('business_subcategory_id') == $subcategory->id ? 'selected' : '' }}>
                                    {{ $subcategory->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div>
                    <label for="filter_status" class="block text-sm font-medium text-gray-700 mb-1">وضعیت سالن</label>
                    <select id="filter_status" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">همه</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>فعال</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>غیرفعال</option>
                    </select>
                </div>

                <div>
                    <label for="filter_sms_balance_status" class="block text-sm font-medium text-gray-700 mb-1">موجودی پیامک</label>
                    <select id="filter_sms_balance_status" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">همه</option>
                        <option value="less_than_50" {{ request('sms_balance_status') === 'less_than_50' ? 'selected' : '' }}>کمتر از ۵۰</option>
                        <option value="less_than_200" {{ request('sms_balance_status') === 'less_than_200' ? 'selected' : '' }}>کمتر از ۲۰۰</option>
                        <option value="zero" {{ request('sms_balance_status') === 'zero' ? 'selected' : '' }}>صفر</option>
                    </select>
                </div>

                <div>
                    <label for="filter_last_sms_purchase" class="block text-sm font-medium text-gray-700 mb-1">آخرین خرید پیامک</label>
                    <select id="filter_last_sms_purchase" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">همه</option>
                        <option value="last_month" {{ request('last_sms_purchase') === 'last_month' ? 'selected' : '' }}>یک ماه اخیر</option>
                        <option value="last_3_months" {{ request('last_sms_purchase') === 'last_3_months' ? 'selected' : '' }}>سه ماه اخیر</option>
                        <option value="last_6_months" {{ request('last_sms_purchase') === 'last_6_months' ? 'selected' : '' }}>شش ماه اخیر</option>
                        <option value="more_than_6_months" {{ request('last_sms_purchase') === 'more_than_6_months' ? 'selected' : '' }}>بیشتر از شش ماه</option>
                        <option value="never" {{ request('last_sms_purchase') === 'never' ? 'selected' : '' }}>تاکنون خرید نکرده</option>
                    </select>
                </div>

                <div>
                    <label for="filter_monthly_sms_consumption" class="block text-sm font-medium text-gray-700 mb-1">مصرف ماهانه پیامک</label>
                    <select id="filter_monthly_sms_consumption" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">همه</option>
                        <option value="high" {{ request('monthly_sms_consumption') === 'high' ? 'selected' : '' }}>زیاد (بیشتر از ۵۰۰)</option>
                        <option value="medium" {{ request('monthly_sms_consumption') === 'medium' ? 'selected' : '' }}>متوسط (۱۰۰ تا ۵۰۰)</option>
                        <option value="low" {{ request('monthly_sms_consumption') === 'low' ? 'selected' : '' }}>کم (کمتر از ۱۰۰)</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-2">
                <button type="button" id="apply-filter-btn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="ri-search-line text-lg ml-2"></i>
                    اعمال فیلتر
                </button>
                <a href="{{ route('admin.discount-codes.create') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="ri-close-line text-lg ml-2"></i>
                    پاک کردن فیلترها
                </a>
            </div>
        </div>

        <!-- Filtered Results Display -->
        @if(request('filter_applied'))
            <div class="border-t border-gray-200 bg-gray-50">
                <div class="px-6 py-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">نتایج فیلتر شده</h4>
                    <p class="text-sm text-gray-600 mb-4">تعداد سالن‌های یافت شده: {{ $filteredSalons ? $filteredSalons->count() : 0 }}</p>
                    
                    @if($filteredSalons && $filteredSalons->count() > 0)
                        <div class="max-h-96 overflow-y-auto bg-white rounded-lg border">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نام سالن</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">مالک</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">شهر</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">دسته‌بندی</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ ثبت‌نام</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($filteredSalons as $salon)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $salon->name }}</div>
                                                <div class="text-sm text-gray-500">{{ $salon->phone ?? '-' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $salon->owner->name ?? 'نامشخص' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $salon->city->name ?? '-' }}</div>
                                                <div class="text-sm text-gray-500">{{ $salon->city->province->name ?? '-' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $salon->businessCategory->name ?? '-' }}</div>
                                                <div class="text-sm text-gray-500">{{ $salon->businessSubcategories->pluck('name')->implode(', ') ?: '-' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ \Morilog\Jalali\Jalalian::forge($salon->created_at)->format('Y/m/d') }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $salon->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ $salon->is_active ? 'فعال' : 'غیرفعال' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="bg-white rounded-lg border px-6 py-8 text-center">
                            <i class="ri-user-search-line text-4xl text-gray-400 mb-4"></i>
                            <p class="text-lg font-medium text-gray-500 mb-2">هیچ سالنی یافت نشد</p>
                            <p class="text-sm text-gray-400">لطفاً فیلترهای دیگری را امتحان کنید.</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const codeInput = document.getElementById('code');
        const expiresAtInput = document.getElementById('expires_at');
        const userFilterTypeRadios = document.querySelectorAll('input[name="user_filter_type"]');
        const filterOptions = document.getElementById('filter-options');
        const provinceSelect = document.getElementById('filter_province_id');
        const citySelect = document.getElementById('filter_city_id');
        const businessCategorySelect = document.getElementById('filter_business_category_id');
        const businessSubcategorySelect = document.getElementById('filter_business_subcategory_id');
        const applyFilterBtn = document.getElementById('apply-filter-btn');

        // Generate random code
        if (codeInput && !codeInput.value) {
            codeInput.value = 'DC' + Math.random().toString(36).substr(2, 8).toUpperCase();
        }

        // Set default expiry date (30 days from now)
        if (expiresAtInput && !expiresAtInput.value) {
            const today = new Date();
            today.setDate(today.getDate() + 30);
            expiresAtInput.value = today.toISOString().split('T')[0];
        }

        // Handle user filter type changes
        userFilterTypeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'filtered') {
                    filterOptions.style.display = 'block';
                } else {
                    filterOptions.style.display = 'none';
                }
            });
        });

        // Handle apply filter button
        if (applyFilterBtn) {
            applyFilterBtn.addEventListener('click', function() {
                const params = new URLSearchParams();
                params.append('filter_applied', '1');
                
                // Collect filter values
                const filterFields = [
                    'filter_province_id',
                    'filter_city_id', 
                    'filter_business_category_id',
                    'filter_business_subcategory_id',
                    'filter_status',
                    'filter_sms_balance_status',
                    'filter_last_sms_purchase',
                    'filter_monthly_sms_consumption'
                ];
                
                filterFields.forEach(fieldId => {
                    const element = document.getElementById(fieldId);
                    if (element && element.value) {
                        // Remove 'filter_' prefix for the parameter name
                        const paramName = fieldId.replace('filter_', '');
                        params.append(paramName, element.value);
                    }
                });
                
                // Redirect with filters
                window.location.href = '{{ route("admin.discount-codes.create") }}?' + params.toString();
            });
        }

        // Handle province change
        if (provinceSelect) {
            provinceSelect.addEventListener('change', function() {
                const provinceId = this.value;
                citySelect.innerHTML = '<option value="">همه شهرها</option>';
                
                if (provinceId) {
                    fetch(`/api/general/provinces/${provinceId}/cities`)
                        .then(response => response.json())
                        .then(cities => {
                            cities.forEach(city => {
                                const option = document.createElement('option');
                                option.value = city.id;
                                option.textContent = city.name;
                                citySelect.appendChild(option);
                            });
                        })
                        .catch(error => console.error('Error loading cities:', error));
                }
            });
        }

        // Handle business category change
        if (businessCategorySelect) {
            businessCategorySelect.addEventListener('change', function() {
                const categoryId = this.value;
                businessSubcategorySelect.innerHTML = '<option value="">همه زیردسته‌ها</option>';
                
                if (categoryId) {
                    fetch(`/api/general/business-categories/${categoryId}/subcategories`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.data && Array.isArray(data.data)) {
                                data.data.forEach(subcategory => {
                                    const option = document.createElement('option');
                                    option.value = subcategory.id;
                                    option.textContent = subcategory.name;
                                    businessSubcategorySelect.appendChild(option);
                                });
                            }
                        })
                        .catch(error => console.error('Error loading subcategories:', error));
                }
            });
        }

        // Initialize Persian Date Pickers if jQuery is available
        if (typeof $ !== 'undefined') {
            $('.persian-date-picker').each(function() {
                $(this).persianDatepicker({
                    format: 'YYYY/MM/DD',
                    autoClose: true,
                    initialValueType: 'persian',
                    onSelect: function(unix) {
                        var date = new persianDate(unix).format('YYYY/MM/DD');
                        this.el.val(date);
                    }
                });
            });
        }
    });
</script>

<!-- Persian Date Picker CSS and JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.css">
<script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.js"></script>
@endpush
