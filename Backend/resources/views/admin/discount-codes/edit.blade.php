@extends('admin.layouts.app')

@section('title', 'ویرایش کد تخفیف')

@section('header')
    <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            ویرایش کد تخفیف: {{ $discountCode->code }}
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

            <form action="{{ route('admin.discount-codes.update', $discountCode) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="space-y-6">
                    <!-- Discount Code -->
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 mb-1">کد تخفیف</label>
                        <input type="text" 
                               name="code" 
                               id="code"
                               value="{{ old('code', $discountCode->code) }}"
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
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('description') border-red-500 @enderror">{{ old('description', $discountCode->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Discount Type and Value -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">نوع تخفیف</label>
                            <select name="type" id="type" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('type') border-red-500 @enderror">
                                <option value="percentage" {{ old('type', $discountCode->type) === 'percentage' ? 'selected' : '' }}>درصدی</option>
                                <option value="fixed" {{ old('type', $discountCode->type) === 'fixed' ? 'selected' : '' }}>مبلغ ثابت</option>
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
                                   value="{{ old('value', $discountCode->value) }}"
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
                               value="{{ old('min_order_amount', $discountCode->min_order_amount) }}"
                               min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('min_order_amount') border-red-500 @enderror">
                        @error('min_order_amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">اگر خالی بگذارید، محدودیتی برای حداقل مبلغ سفارش وجود نخواهد داشت</p>
                    </div>

                    <!-- Maximum Discount Amount -->
                    <div>
                        <label for="max_discount_amount" class="block text-sm font-medium text-gray-700 mb-1">حداکثر مبلغ تخفیف (تومان)</label>
                        <input type="number" 
                               name="max_discount_amount" 
                               id="max_discount_amount"
                               value="{{ old('max_discount_amount', $discountCode->max_discount_amount) }}"
                               min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('max_discount_amount') border-red-500 @enderror">
                        @error('max_discount_amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">فقط برای تخفیف درصدی. اگر خالی بگذارید، محدودیتی وجود نخواهد داشت</p>
                    </div>

                    <!-- Validity Period -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="starts_at" class="block text-sm font-medium text-gray-700 mb-1">تاریخ شروع (شمسی)</label>
                            <input type="text" 
                                   name="starts_at" 
                                   id="starts_at"
                                   value="{{ old('starts_at', $discountCode->starts_at ? \Morilog\Jalali\Jalalian::forge($discountCode->starts_at)->format('Y/m/d') : '') }}"
                                   placeholder="مثال: ۱۴۰۳/۰۶/۱۵"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('starts_at') border-red-500 @enderror persian-date-picker"
                                   autocomplete="off">
                            @error('starts_at')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">اگر خالی بگذارید، کد از همین حالا قابل استفاده است</p>
                        </div>

                        <div>
                            <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-1">تاریخ انقضا (شمسی)</label>
                            <input type="text" 
                                   name="expires_at" 
                                   id="expires_at"
                                   value="{{ old('expires_at', $discountCode->expires_at ? \Morilog\Jalali\Jalalian::forge($discountCode->expires_at)->format('Y/m/d') : '') }}"
                                   placeholder="مثال: ۱۴۰۳/۱۲/۲۹"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('expires_at') border-red-500 @enderror persian-date-picker"
                                   autocomplete="off">
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
                               value="{{ old('usage_limit', $discountCode->usage_limit) }}"
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
                                                   {{ old('user_filter_type', $discountCode->user_filter_type ?? 'all') === 'all' ? 'checked' : '' }}
                                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                            <span class="mr-2 text-sm text-gray-900">همه کاربران</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" name="user_filter_type" value="filtered" 
                                                   {{ old('user_filter_type', $discountCode->user_filter_type) === 'filtered' ? 'checked' : '' }}
                                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                            <span class="mr-2 text-sm text-gray-900">کاربران فیلتر شده</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Filter Options (Hidden by default) -->
                                <div id="filter-options" class="space-y-4" style="display: {{ old('user_filter_type', $discountCode->user_filter_type) === 'filtered' ? 'block' : 'none' }};">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <!-- Province Filter -->
                                        <div>
                                            <label for="province_id" class="block text-sm font-medium text-gray-700 mb-1">استان</label>
                                            <select name="province_id" id="province_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                                <option value="">همه استان‌ها</option>
                                                @foreach($provinces as $province)
                                                    <option value="{{ $province->id }}" {{ old('province_id', $discountCode->target_users['province_id'] ?? '') == $province->id ? 'selected' : '' }}>
                                                        {{ $province->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- City Filter -->
                                        <div>
                                            <label for="city_id" class="block text-sm font-medium text-gray-700 mb-1">شهر</label>
                                            <select name="city_id" id="city_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                                <option value="">همه شهرها</option>
                                            </select>
                                        </div>

                                        <!-- Business Category Filter -->
                                        <div>
                                            <label for="business_category_id" class="block text-sm font-medium text-gray-700 mb-1">دسته‌بندی کسب‌وکار</label>
                                            <select name="business_category_id" id="business_category_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                                <option value="">همه دسته‌ها</option>
                                                @foreach($businessCategories as $category)
                                                    <option value="{{ $category->id }}" {{ old('business_category_id', $discountCode->target_users['business_category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Business Subcategory Filter -->
                                        <div>
                                            <label for="business_subcategory_id" class="block text-sm font-medium text-gray-700 mb-1">زیردسته کسب‌وکار</label>
                                            <select name="business_subcategory_id" id="business_subcategory_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                                <option value="">همه زیردسته‌ها</option>
                                            </select>
                                        </div>

                                        <!-- Status Filter -->
                                        <div>
                                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">وضعیت سالن</label>
                                            <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                                <option value="">همه</option>
                                                <option value="1" {{ old('status', $discountCode->target_users['status'] ?? '') === '1' ? 'selected' : '' }}>فعال</option>
                                                <option value="0" {{ old('status', $discountCode->target_users['status'] ?? '') === '0' ? 'selected' : '' }}>غیرفعال</option>
                                            </select>
                                        </div>

                                        <!-- SMS Balance Filter -->
                                        <div>
                                            <label for="sms_balance_status" class="block text-sm font-medium text-gray-700 mb-1">موجودی پیامک</label>
                                            <select name="sms_balance_status" id="sms_balance_status" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                                <option value="">همه</option>
                                                <option value="less_than_50" {{ old('sms_balance_status', $discountCode->target_users['sms_balance_status'] ?? '') === 'less_than_50' ? 'selected' : '' }}>کمتر از ۵۰</option>
                                                <option value="less_than_200" {{ old('sms_balance_status', $discountCode->target_users['sms_balance_status'] ?? '') === 'less_than_200' ? 'selected' : '' }}>کمتر از ۲۰۰</option>
                                                <option value="zero" {{ old('sms_balance_status', $discountCode->target_users['sms_balance_status'] ?? '') === 'zero' ? 'selected' : '' }}>صفر</option>
                                            </select>
                                        </div>

                                        <!-- Last SMS Purchase Filter -->
                                        <div>
                                            <label for="last_sms_purchase" class="block text-sm font-medium text-gray-700 mb-1">آخرین خرید پیامک</label>
                                            <select name="last_sms_purchase" id="last_sms_purchase" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                                <option value="">همه</option>
                                                <option value="last_month" {{ old('last_sms_purchase', $discountCode->target_users['last_sms_purchase'] ?? '') === 'last_month' ? 'selected' : '' }}>یک ماه اخیر</option>
                                                <option value="last_3_months" {{ old('last_sms_purchase', $discountCode->target_users['last_sms_purchase'] ?? '') === 'last_3_months' ? 'selected' : '' }}>سه ماه اخیر</option>
                                                <option value="last_6_months" {{ old('last_sms_purchase', $discountCode->target_users['last_sms_purchase'] ?? '') === 'last_6_months' ? 'selected' : '' }}>شش ماه اخیر</option>
                                                <option value="more_than_6_months" {{ old('last_sms_purchase', $discountCode->target_users['last_sms_purchase'] ?? '') === 'more_than_6_months' ? 'selected' : '' }}>بیشتر از شش ماه</option>
                                                <option value="never" {{ old('last_sms_purchase', $discountCode->target_users['last_sms_purchase'] ?? '') === 'never' ? 'selected' : '' }}>تاکنون خرید نکرده</option>
                                            </select>
                                        </div>

                                        <!-- Monthly SMS Consumption Filter -->
                                        <div>
                                            <label for="monthly_sms_consumption" class="block text-sm font-medium text-gray-700 mb-1">مصرف ماهانه پیامک</label>
                                            <select name="monthly_sms_consumption" id="monthly_sms_consumption" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                                <option value="">همه</option>
                                                <option value="high" {{ old('monthly_sms_consumption', $discountCode->target_users['monthly_sms_consumption'] ?? '') === 'high' ? 'selected' : '' }}>زیاد (بیشتر از ۵۰۰)</option>
                                                <option value="medium" {{ old('monthly_sms_consumption', $discountCode->target_users['monthly_sms_consumption'] ?? '') === 'medium' ? 'selected' : '' }}>متوسط (۱۰۰ تا ۵۰۰)</option>
                                                <option value="low" {{ old('monthly_sms_consumption', $discountCode->target_users['monthly_sms_consumption'] ?? '') === 'low' ? 'selected' : '' }}>کم (کمتر از ۱۰۰)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Filtered Users List -->
                                    <div id="filtered-users-list" class="mt-6" style="display: none;">
                                        <div class="border border-gray-200 rounded-lg">
                                            <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
                                                <h3 class="text-lg font-medium text-gray-900">کاربران هدف</h3>
                                                <p class="mt-1 text-sm text-gray-600">لیست سالن‌هایی که با فیلترهای انتخابی مطابقت دارند</p>
                                            </div>
                                            <div id="users-list-content" class="p-6">
                                                <div class="text-center py-4">
                                                    <i class="ri-loader-4-line text-2xl text-gray-400 animate-spin"></i>
                                                    <p class="text-gray-500 mt-2">در حال بارگذاری...</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Status -->
                    <div class="flex items-center">
                        <input type="checkbox" 
                               name="is_active" 
                               id="is_active"
                               value="1"
                               {{ old('is_active', $discountCode->is_active) ? 'checked' : '' }}
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
                            ویرایش کد تخفیف
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM fully loaded and parsed');
        
        const codeInput = document.getElementById('code');
        const expiresAtInput = document.getElementById('expires_at');
        const userFilterTypeRadios = document.querySelectorAll('input[name="user_filter_type"]');
        const filterOptions = document.getElementById('filter-options');
        const filteredUsersList = document.getElementById('filtered-users-list');
        const usersListContent = document.getElementById('users-list-content');
        const provinceSelect = document.getElementById('province_id');
        const citySelect = document.getElementById('city_id');
        const businessCategorySelect = document.getElementById('business_category_id');
        const businessSubcategorySelect = document.getElementById('business_subcategory_id');
        
        console.log('Found elements:', {
            userFilterTypeRadios: userFilterTypeRadios.length,
            filterOptions: !!filterOptions,
            filteredUsersList: !!filteredUsersList,
            usersListContent: !!usersListContent
        });

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
                console.log('Radio changed to:', this.value);
                if (this.value === 'filtered') {
                    filterOptions.style.display = 'block';
                    filteredUsersList.style.display = 'block';
                    console.log('Showing filter options and loading users list');
                    loadFilteredUsers();
                } else {
                    filterOptions.style.display = 'none';
                    filteredUsersList.style.display = 'none';
                    console.log('Hiding filter options and users list');
                }
            });
        });

        // Check initial state
        const checkedRadio = document.querySelector('input[name="user_filter_type"]:checked');
        console.log('Initially checked radio:', checkedRadio ? checkedRadio.value : 'none');
        
        // If "filtered" is initially selected, show the list
        if (checkedRadio && checkedRadio.value === 'filtered') {
            filterOptions.style.display = 'block';
            filteredUsersList.style.display = 'block';
            loadFilteredUsers();
        }

        // Handle filter changes to reload the list
        const filterSelects = document.querySelectorAll('#filter-options select');
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                const checkedRadio = document.querySelector('input[name="user_filter_type"]:checked');
                if (checkedRadio && checkedRadio.value === 'filtered') {
                    console.log('Filter changed, reloading users list');
                    loadFilteredUsers();
                }
            });
        });

        // Handle province change
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

        // Handle business category change
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

        function loadFilteredUsers() {
            console.log('Loading filtered users...');
            
            // Show loading state
            usersListContent.innerHTML = `
                <div class="text-center py-4">
                    <i class="ri-loader-4-line text-2xl text-gray-400 animate-spin"></i>
                    <p class="text-gray-500 mt-2">در حال بارگذاری...</p>
                </div>
            `;

            // Get form data
            const formData = new FormData();
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            
            // Add filter values
            const filterFields = ['province_id', 'city_id', 'business_category_id', 'business_subcategory_id', 'status', 'sms_balance_status', 'last_sms_purchase', 'monthly_sms_consumption'];
            filterFields.forEach(field => {
                const element = document.getElementById(field);
                if (element && element.value) {
                    formData.append(field, element.value);
                }
            });

            console.log('Sending AJAX request with filters');

            fetch('{{ route("admin.discount-codes.preview-target-users") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                console.log('Response received:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Data received:', data);
                displayUsersList(data);
            })
            .catch(error => {
                console.error('Error:', error);
                usersListContent.innerHTML = `
                    <div class="text-center py-4">
                        <i class="ri-error-warning-line text-2xl text-red-400"></i>
                        <p class="text-red-500 mt-2">خطا در بارگذاری داده‌ها</p>
                    </div>
                `;
            });
        }

        function displayUsersList(data) {
            if (data.salons && data.salons.length > 0) {
                let html = `
                    <div class="mb-4 flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            تعداد کل: <span class="font-medium text-gray-900">${data.total_count}</span> سالن
                        </div>
                    </div>
                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50">
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
                `;

                data.salons.forEach(salon => {
                    html += `
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">${salon.name}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">${salon.owner_name}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">${salon.city_name || '-'}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">${salon.category_name || '-'}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">${salon.created_at_jalali}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${salon.status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                    ${salon.status ? 'فعال' : 'غیرفعال'}
                                </span>
                            </td>
                        </tr>
                    `;
                });

                html += `
                            </tbody>
                        </table>
                    </div>
                `;

                usersListContent.innerHTML = html;
            } else {
                usersListContent.innerHTML = `
                    <div class="text-center py-8">
                        <i class="ri-user-search-line text-4xl text-gray-400"></i>
                        <p class="text-gray-500 mt-2">هیچ کاربری با فیلترهای انتخابی یافت نشد</p>
                    </div>
                `;
            }
        }

        // Load initial cities and subcategories if values are set
        const initialProvinceId = provinceSelect.value;
        const initialCategoryId = businessCategorySelect.value;
        
        if (initialProvinceId) {
            provinceSelect.dispatchEvent(new Event('change'));
        }
        
        if (initialCategoryId) {
            businessCategorySelect.dispatchEvent(new Event('change'));
        }

        // Initialize Persian Date Pickers
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
    });
</script>

<!-- Persian Date Picker CSS and JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.css">
<script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.js"></script>
@endpush
