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
    <div class="max-w-6xl mx-auto">
        <div class="bg-white shadow-lg rounded-xl overflow-hidden">
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-8 py-6">
                <div class="flex items-center">
                    <div class="bg-white/10 rounded-lg p-3 ml-4">
                        <i class="ri-coupon-line text-2xl text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">ایجاد کد تخفیف جدید</h3>
                        <p class="text-indigo-100 text-sm">کد تخفیف خود را با تنظیمات دلخواه ایجاد کنید</p>
                    </div>
                </div>
            </div>

            <div class="p-8">
                <!-- Display Validation Errors -->
                @if($errors->any())
                    <div class="mb-8 bg-red-50 border-l-4 border-red-400 p-4 rounded-lg">
                        <div class="flex items-start">
                            <i class="ri-error-warning-line text-red-400 text-xl ml-3 mt-0.5"></i>
                            <div>
                                <h4 class="text-red-800 font-medium mb-2">خطاهای زیر رخ داده است:</h4>
                                <ul class="text-red-700 text-sm space-y-1">
                                    @foreach($errors->all() as $error)
                                        <li class="flex items-center">
                                            <i class="ri-arrow-left-s-line text-xs ml-1"></i>
                                            {{ $error }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

            <form action="{{ route('admin.discount-codes.store') }}" method="POST">
                @csrf
                
                <div class="space-y-8">
                    <!-- Basic Information Section -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-blue-100 rounded-lg p-2 ml-3">
                                <i class="ri-information-line text-blue-600 text-lg"></i>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900">اطلاعات پایه</h4>
                        </div>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Discount Code -->
                            <div>
                                <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="ri-price-tag-3-line text-gray-400 ml-1"></i>
                                    کد تخفیف
                                </label>
                                <div class="relative">
                                    <input type="text" 
                                           name="code" 
                                           id="code"
                                           value="{{ old('code', request('form_code')) }}"
                                           required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 @error('code') border-red-500 @enderror">
                                    <button type="button" onclick="generateCode()" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-indigo-600 transition-colors">
                                        <i class="ri-refresh-line"></i>
                                    </button>
                                </div>
                                @error('code')
                                    <p class="mt-2 text-sm text-red-600 flex items-center">
                                        <i class="ri-error-warning-line ml-1"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                                <p class="mt-2 text-sm text-gray-500">کد منحصربه‌فرد برای تخفیف</p>
                            </div>

                            <!-- Description -->
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="ri-file-text-line text-gray-400 ml-1"></i>
                                    توضیحات
                                </label>
                                <textarea name="description" 
                                          id="description"
                                          rows="4"
                                          placeholder="توضیحات کوتاهی در مورد این کد تخفیف..."
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 @error('description') border-red-500 @enderror">{{ old('description', request('form_description')) }}</textarea>
                                @error('description')
                                    <p class="mt-2 text-sm text-red-600 flex items-center">
                                        <i class="ri-error-warning-line ml-1"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Discount Settings Section -->
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border border-green-100">
                        <div class="flex items-center mb-6">
                            <div class="bg-green-100 rounded-lg p-2 ml-3">
                                <i class="ri-percent-line text-green-600 text-lg"></i>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900">تنظیمات تخفیف</h4>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="ri-settings-3-line text-gray-400 ml-1"></i>
                                    نوع تخفیف
                                </label>
                                <select name="type" id="type" required class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 @error('type') border-red-500 @enderror">
                                    <option value="percentage" {{ old('type', request('form_type')) === 'percentage' ? 'selected' : '' }}>
                                        <i class="ri-percent-line"></i> درصدی
                                    </option>
                                    <option value="fixed" {{ old('type', request('form_type')) === 'fixed' ? 'selected' : '' }}>
                                        <i class="ri-money-dollar-circle-line"></i> مبلغ ثابت (تومان)
                                    </option>
                                </select>
                                @error('type')
                                    <p class="mt-2 text-sm text-red-600 flex items-center">
                                        <i class="ri-error-warning-line ml-1"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div>
                                <label for="value" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="ri-money-dollar-box-line text-gray-400 ml-1"></i>
                                    مقدار تخفیف
                                </label>
                                <div class="relative">
                                    <input type="number" 
                                           name="value" 
                                           id="value"
                                           value="{{ old('value', request('form_value')) }}"
                                           required
                                           min="0"
                                           step="0.01"
                                           placeholder="0"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 @error('value') border-red-500 @enderror">
                                    <span id="value-unit" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm">%</span>
                                </div>
                                @error('value')
                                    <p class="mt-2 text-sm text-red-600 flex items-center">
                                        <i class="ri-error-warning-line ml-1"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                                <p class="mt-2 text-sm text-gray-500">برای درصدی: عدد بین 0 تا 100، برای مبلغ ثابت: مبلغ به تومان</p>
                            </div>
                        </div>

                        <!-- Minimum Order Amount -->
                        <div class="mt-6">
                            <label for="min_order_amount" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="ri-shopping-cart-line text-gray-400 ml-1"></i>
                                حداقل مبلغ سفارش (تومان)
                            </label>
                            <input type="number" 
                                   name="min_order_amount" 
                                   id="min_order_amount"
                                   value="{{ old('min_order_amount', request('form_min_order_amount')) }}"
                                   min="0"
                                   step="1000"
                                   placeholder="مثال: 50000"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 @error('min_order_amount') border-red-500 @enderror">
                            @error('min_order_amount')
                                <p class="mt-2 text-sm text-red-600 flex items-center">
                                    <i class="ri-error-warning-line ml-1"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                            <p class="mt-2 text-sm text-gray-500">حداقل مبلغ سفارش برای استفاده از این کد</p>
                        </div>
                    </div>

                    <!-- Date and Limits Section -->
                    <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-6 border border-purple-100">
                        <div class="flex items-center mb-6">
                            <div class="bg-purple-100 rounded-lg p-2 ml-3">
                                <i class="ri-calendar-line text-purple-600 text-lg"></i>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900">تاریخ و محدودیت‌ها</h4>
                        </div>
                        
                        <!-- Date Range -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="starts_at" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="ri-play-circle-line text-gray-400 ml-1"></i>
                                    تاریخ شروع
                                </label>
                                <input type="text" 
                                       name="starts_at" 
                                       id="starts_at"
                                       value="{{ old('starts_at', request('form_starts_at')) }}"
                                       placeholder="انتخاب تاریخ..."
                                       class="persian-date-picker w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 @error('starts_at') border-red-500 @enderror">
                                @error('starts_at')
                                    <p class="mt-2 text-sm text-red-600 flex items-center">
                                        <i class="ri-error-warning-line ml-1"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                                <p class="mt-2 text-sm text-gray-500">اگر خالی بگذارید، کد از همین حالا فعال می‌شود</p>
                            </div>

                            <div>
                                <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="ri-stop-circle-line text-gray-400 ml-1"></i>
                                    تاریخ انقضا
                                </label>
                                <input type="text" 
                                       name="expires_at" 
                                       id="expires_at"
                                       value="{{ old('expires_at', request('form_expires_at')) }}"
                                       placeholder="انتخاب تاریخ..."
                                       class="persian-date-picker w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 @error('expires_at') border-red-500 @enderror">
                                @error('expires_at')
                                    <p class="mt-2 text-sm text-red-600 flex items-center">
                                        <i class="ri-error-warning-line ml-1"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                                <p class="mt-2 text-sm text-gray-500">اگر خالی بگذارید، کد هیچ‌وقت منقضی نمی‌شود</p>
                            </div>
                        </div>

                        <!-- Usage Limit -->
                        <div>
                            <label for="usage_limit" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="ri-user-line text-gray-400 ml-1"></i>
                                محدودیت تعداد سالن
                            </label>
                            <input type="number" 
                                   name="usage_limit" 
                                   id="usage_limit"
                                   value="{{ old('usage_limit', request('form_usage_limit')) }}"
                                   min="1"
                                   placeholder="مثال: 100"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 @error('usage_limit') border-red-500 @enderror">
                            @error('usage_limit')
                                <p class="mt-2 text-sm text-red-600 flex items-center">
                                    <i class="ri-error-warning-line ml-1"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                            <p class="mt-2 text-sm text-gray-500">حداکثر تعداد سالن‌هایی که می‌توانند از این کد استفاده کنند (هر سالن فقط یکبار)</p>
                        </div>
                    </div>

                    <!-- User Targeting Section -->
                    <div class="bg-gradient-to-br from-orange-50 to-yellow-50 rounded-xl p-6 border border-orange-100">
                        <div class="flex items-center mb-6">
                            <div class="bg-orange-100 rounded-lg p-2 ml-3">
                                <i class="ri-user-search-line text-orange-600 text-lg"></i>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900">هدف‌گذاری کاربران</h4>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <label class="relative cursor-pointer radio-option">
                                    <input type="radio" name="user_filter_type" value="all" 
                                           {{ old('user_filter_type', request('form_user_filter_type', 'all')) === 'all' ? 'checked' : '' }}
                                           class="sr-only radio-input">
                                    <div class="radio-card flex items-center p-4 border-2 border-gray-200 rounded-lg transition-all duration-200 hover:border-gray-300">
                                        <div class="radio-circle flex items-center justify-center w-6 h-6 border-2 border-gray-300 rounded-full mr-3 transition-all duration-200">
                                            <div class="radio-dot w-3 h-3 bg-indigo-500 rounded-full transform scale-0 transition-transform duration-200"></div>
                                        </div>
                                        <div>
                                            <div class="flex items-center">
                                                <i class="ri-group-line text-lg text-gray-500 ml-2"></i>
                                                <span class="text-sm font-medium text-gray-900">همه کاربران</span>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1">کد تخفیف برای همه سالن‌ها قابل استفاده باشد</p>
                                        </div>
                                    </div>
                                </label>
                                
                                <label class="relative cursor-pointer radio-option">
                                    <input type="radio" name="user_filter_type" value="filtered" 
                                           {{ old('user_filter_type', request('form_user_filter_type')) === 'filtered' || request('filter_applied') ? 'checked' : '' }}
                                           class="sr-only radio-input">
                                    <div class="radio-card flex items-center p-4 border-2 border-gray-200 rounded-lg transition-all duration-200 hover:border-gray-300">
                                        <div class="radio-circle flex items-center justify-center w-6 h-6 border-2 border-gray-300 rounded-full mr-3 transition-all duration-200">
                                            <div class="radio-dot w-3 h-3 bg-indigo-500 rounded-full transform scale-0 transition-transform duration-200"></div>
                                        </div>
                                        <div>
                                            <div class="flex items-center">
                                                <i class="ri-filter-3-line text-lg text-gray-500 ml-2"></i>
                                                <span class="text-sm font-medium text-gray-900">کاربران فیلتر شده</span>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1">کد تخفیف فقط برای سالن‌های خاص</p>
                                        </div>
                                    </div>
                                </label>
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

                    <!-- Final Settings -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-green-100 rounded-lg p-2 ml-3">
                                <i class="ri-toggle-line text-green-600 text-lg"></i>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900">تنظیمات نهایی</h4>
                        </div>
                    </div>

                    <!-- Active Status - Outside the box -->
                    <div class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
                        <div class="flex items-center">
                            <div class="bg-green-100 rounded-lg p-2 ml-3">
                                <i class="ri-toggle-line text-green-600 text-lg"></i>
                            </div>
                            <div>
                                <h5 class="text-sm font-semibold text-gray-900">وضعیت فعال‌سازی</h5>
                                <p class="text-xs text-gray-500">آیا کد تخفیف بلافاصله فعال شود؟</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" 
                                   name="is_active" 
                                   id="is_active"
                                   value="1"
                                   {{ old('is_active', request('form_is_active', '1')) === '1' ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:-translate-x-5 peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:right-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            <span class="mr-3 text-sm font-medium text-gray-700">فعال</span>
                        </label>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex items-center justify-between pt-8 border-t border-gray-200">
                        <a href="{{ route('admin.discount-codes.index') }}"
                           class="inline-flex items-center px-6 py-3 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                            <i class="ri-arrow-right-line text-lg ml-2"></i>
                            انصراف
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center px-8 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200 transform hover:scale-105">
                            <i class="ri-save-line text-lg ml-2"></i>
                            ایجاد کد تخفیف
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    </div>

    <!-- Filter Options (outside main form) -->
    <div id="filter-options" class="mt-8 bg-white shadow-lg rounded-xl overflow-hidden" style="display: {{ request('filter_applied') ? 'block' : 'none' }};">
        <!-- Filter Header -->
        <div class="bg-gradient-to-r from-blue-500 to-cyan-600 px-8 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="bg-white/10 rounded-lg p-3 ml-4">
                        <i class="ri-filter-3-line text-2xl text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">فیلتر سالن‌ها</h3>
                        <p class="text-blue-100 text-sm">سالن‌های مورد نظر خود را انتخاب کنید</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <div>
                    <label for="filter_province_id" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="ri-map-pin-line text-gray-400 ml-1"></i>
                        استان
                    </label>
                    <select id="filter_province_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200">
                        <option value="">همه استان‌ها</option>
                        @foreach($provinces as $province)
                            <option value="{{ $province->id }}" {{ request('province_id') == $province->id ? 'selected' : '' }}>
                                {{ $province->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_city_id" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="ri-building-line text-gray-400 ml-1"></i>
                        شهر
                    </label>
                    <select id="filter_city_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200">
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
                    <label for="filter_business_category_id" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="ri-store-line text-gray-400 ml-1"></i>
                        دسته‌بندی کسب‌وکار
                    </label>
                    <select id="filter_business_category_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200">
                        <option value="">همه دسته‌ها</option>
                        @foreach($businessCategories as $category)
                            <option value="{{ $category->id }}" {{ request('business_category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_business_subcategory_id" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="ri-list-check-line text-gray-400 ml-1"></i>
                        زیردسته کسب‌وکار
                    </label>
                    <select id="filter_business_subcategory_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200">
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
                    <label for="filter_status" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="ri-toggle-line text-gray-400 ml-1"></i>
                        وضعیت سالن
                    </label>
                    <select id="filter_status" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200">
                        <option value="">همه</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>فعال</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>غیرفعال</option>
                    </select>
                </div>

                <div>
                    <label for="filter_sms_balance_status" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="ri-message-line text-gray-400 ml-1"></i>
                        موجودی پیامک
                    </label>
                    <select id="filter_sms_balance_status" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200">
                        <option value="">همه</option>
                        <option value="less_than_50" {{ request('sms_balance_status') === 'less_than_50' ? 'selected' : '' }}>کمتر از ۵۰</option>
                        <option value="less_than_200" {{ request('sms_balance_status') === 'less_than_200' ? 'selected' : '' }}>کمتر از ۲۰۰</option>
                        <option value="zero" {{ request('sms_balance_status') === 'zero' ? 'selected' : '' }}>صفر</option>
                    </select>
                </div>

                <div>
                    <label for="filter_last_sms_purchase" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="ri-shopping-cart-line text-gray-400 ml-1"></i>
                        آخرین خرید پیامک
                    </label>
                    <select id="filter_last_sms_purchase" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200">
                        <option value="">همه</option>
                        <option value="last_month" {{ request('last_sms_purchase') === 'last_month' ? 'selected' : '' }}>یک ماه اخیر</option>
                        <option value="last_3_months" {{ request('last_sms_purchase') === 'last_3_months' ? 'selected' : '' }}>سه ماه اخیر</option>
                        <option value="last_6_months" {{ request('last_sms_purchase') === 'last_6_months' ? 'selected' : '' }}>شش ماه اخیر</option>
                        <option value="more_than_6_months" {{ request('last_sms_purchase') === 'more_than_6_months' ? 'selected' : '' }}>بیشتر از شش ماه</option>
                        <option value="never" {{ request('last_sms_purchase') === 'never' ? 'selected' : '' }}>تاکنون خرید نکرده</option>
                    </select>
                </div>

                <div>
                    <label for="filter_monthly_sms_consumption" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="ri-bar-chart-line text-gray-400 ml-1"></i>
                        مصرف ماهانه پیامک
                    </label>
                    <select id="filter_monthly_sms_consumption" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200">
                        <option value="">همه</option>
                        <option value="high" {{ request('monthly_sms_consumption') === 'high' ? 'selected' : '' }}>زیاد (بیشتر از ۵۰۰)</option>
                        <option value="medium" {{ request('monthly_sms_consumption') === 'medium' ? 'selected' : '' }}>متوسط (۱۰۰ تا ۵۰۰)</option>
                        <option value="low" {{ request('monthly_sms_consumption') === 'low' ? 'selected' : '' }}>کم (کمتر از ۱۰۰)</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-4">
                <button type="button" id="apply-filter-btn" class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200 transform hover:scale-105">
                    <i class="ri-search-line text-lg ml-2"></i>
                    اعمال فیلتر
                </button>
                <a href="{{ route('admin.discount-codes.create') }}" class="inline-flex items-center px-6 py-3 border border-gray-300 text-sm font-medium rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
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

        // Generate random code function
        function generateCode() {
            const codeInput = document.getElementById('code');
            const newCode = 'DC' + Math.random().toString(36).substr(2, 8).toUpperCase();
            codeInput.value = newCode;
            
            // Add animation effect
            codeInput.classList.add('animate-pulse');
            setTimeout(() => {
                codeInput.classList.remove('animate-pulse');
            }, 500);
        }

        // Generate random code if empty
        if (codeInput && !codeInput.value) {
            generateCode();
        }

        // Update value unit display based on type
        const typeSelect = document.getElementById('type');
        const valueUnit = document.getElementById('value-unit');
        
        function updateValueUnit() {
            if (typeSelect.value === 'percentage') {
                valueUnit.textContent = '%';
                valueUnit.classList.remove('hidden');
            } else {
                valueUnit.textContent = 'تومان';
                valueUnit.classList.remove('hidden');
            }
        }
        
        if (typeSelect && valueUnit) {
            typeSelect.addEventListener('change', updateValueUnit);
            updateValueUnit(); // Initial call
        }

        // Handle radio buttons visual state
        function updateRadioButtons() {
            const radioOptions = document.querySelectorAll('.radio-option');
            
            radioOptions.forEach(option => {
                const input = option.querySelector('.radio-input');
                const card = option.querySelector('.radio-card');
                const circle = option.querySelector('.radio-circle');
                const dot = option.querySelector('.radio-dot');
                
                if (input.checked) {
                    card.classList.add('border-indigo-500', 'bg-indigo-50');
                    card.classList.remove('border-gray-200');
                    circle.classList.add('border-indigo-500');
                    circle.classList.remove('border-gray-300');
                    dot.classList.add('scale-100');
                    dot.classList.remove('scale-0');
                } else {
                    card.classList.remove('border-indigo-500', 'bg-indigo-50');
                    card.classList.add('border-gray-200');
                    circle.classList.remove('border-indigo-500');
                    circle.classList.add('border-gray-300');
                    dot.classList.remove('scale-100');
                    dot.classList.add('scale-0');
                }
            });
        }
        
        // Initialize radio buttons
        updateRadioButtons();
        
        // Add event listeners to radio buttons
        document.querySelectorAll('.radio-input').forEach(input => {
            input.addEventListener('change', updateRadioButtons);
        });

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
                
                // Save current form values to preserve them after redirect
                const formData = new FormData(document.querySelector('form'));
                for (let [key, value] of formData.entries()) {
                    if (key !== '_token' && value) {
                        params.append('form_' + key, value);
                    }
                }
                
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
