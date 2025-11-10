@extends('admin.layouts.app')

@section('title', 'شارژ گروهی پیامک هدیه')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-8">
    <h1 class="text-3xl font-extrabold text-gray-900 text-center font-sans">شارژ گروهی پیامک هدیه</h1>
        <a href="{{ route('admin.bulk-sms-gift.history') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <i class="ri-history-line ml-2"></i> تاریخچه هدایا
        </a>
    </div>

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6 shadow-md" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6 shadow-md" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <div class="bg-white shadow-xl rounded-lg p-6 mb-8">
        <form action="{{ route('admin.bulk-sms-gift.index') }}" method="GET" class="space-y-4">
            <!-- Mobile: Stack all filters vertically -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <div class="col-span-1">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">جستجو (نام، شماره تماس، مالک، شهر)</label>
                    <input type="text" name="search" id="search" placeholder="جستجو..." class="form-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out" value="{{ request('search') }}">
                </div>
                <div class="col-span-1">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">وضعیت</label>
                    <select name="status" id="status" class="form-select w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out">
                        <option value="">همه</option>
                        <option value="1" @if(request('status') === '1') selected @endif>فعال</option>
                        <option value="0" @if(request('status') === '0') selected @endif>غیرفعال</option>
                    </select>
                </div>
                <div class="col-span-1">
                    <label for="province_id" class="block text-sm font-medium text-gray-700 mb-1">استان</label>
                    <select name="province_id" id="province_id" class="form-select w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out">
                        <option value="">همه</option>
                        @foreach($provinces as $province)
                            <option value="{{ $province->id }}" @if(request('province_id') == $province->id) selected @endif>{{ $province->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-1">
                    <label for="city_id" class="block text-sm font-medium text-gray-700 mb-1">شهر</label>
                    <select name="city_id" id="city_id" class="form-select w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out">
                        <option value="">همه</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}" @if(request('city_id') == $city->id) selected @endif>{{ $city->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-1">
                    <label for="business_category_id" class="block text-sm font-medium text-gray-700 mb-1">دسته بندی فعالیت</label>
                    <select name="business_category_id" id="business_category_id" class="form-select w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out">
                        <option value="">همه</option>
                        @foreach($businessCategories as $category)
                            <option value="{{ $category->id }}" @if(request('business_category_id') == $category->id) selected @endif>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-1">
                    <label for="business_subcategory_id" class="block text-sm font-medium text-gray-700 mb-1">زیردسته بندی فعالیت</label>
                    <select name="business_subcategory_id" id="business_subcategory_id" class="form-select w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out">
                        <option value="">همه</option>
                        @foreach($businessSubcategories as $subcategory)
                            <option value="{{ $subcategory->id }}" @if(request('business_subcategory_id') == $subcategory->id) selected @endif>{{ $subcategory->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-1">
                    <label for="created_at_start" class="block text-sm font-medium text-gray-700 mb-1">تاریخ ثبت از</label>
                    <input type="text" name="created_at_start" id="created_at_start" placeholder="برای مثال: 1402/01/01" class="jalali-datepicker form-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out">
                </div>
                <div class="col-span-1">
                    <label for="created_at_end" class="block text-sm font-medium text-gray-700 mb-1">تاریخ ثبت تا</label>
                    <input type="text" name="created_at_end" id="created_at_end" placeholder="برای مثال: 1402/12/29" class="jalali-datepicker form-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out">
                </div>
                <div class="col-span-1">
                    <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">جنسیت مالک</label>
                    <select name="gender" id="gender" class="form-select w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out">
                        <option value="">همه</option>
                        <option value="male" @if(request('gender') === 'male') selected @endif>مرد</option>
                        <option value="female" @if(request('gender') === 'female') selected @endif>زن</option>
                        <option value="other" @if(request('gender') === 'other') selected @endif>سایر</option>
                    </select>
                </div>
                <div class="col-span-1">
                    <label for="owner_min_age" class="block text-sm font-medium text-gray-700 mb-1">حداقل سن مالک</label>
                    <input type="number" name="owner_min_age" id="owner_min_age" placeholder="برای مثال: 25" class="form-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out" value="{{ request('owner_min_age') }}" min="18" max="120">
                </div>
                <div class="col-span-1">
                    <label for="owner_max_age" class="block text-sm font-medium text-gray-700 mb-1">حداکثر سن مالک</label>
                    <input type="number" name="owner_max_age" id="owner_max_age" placeholder="برای مثال: 40" class="form-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out" value="{{ request('owner_max_age') }}" min="18" max="120">
                </div>
                <div class="col-span-1">
                    <label for="min_sms_balance" class="block text-sm font-medium text-gray-700 mb-1">حداقل اعتبار پیامک</label>
                    <input type="number" name="min_sms_balance" id="min_sms_balance" placeholder="برای مثال: 100" class="form-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out" value="{{ request('min_sms_balance') }}" min="0">
                </div>
                <div class="col-span-1">
                    <label for="max_sms_balance" class="block text-sm font-medium text-gray-700 mb-1">حداکثر اعتبار پیامک</label>
                    <input type="number" name="max_sms_balance" id="max_sms_balance" placeholder="برای مثال: 500" class="form-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out" value="{{ request('max_sms_balance') }}" min="0">
                </div>
                <div class="col-span-1">
                    <label for="last_sms_purchase_start" class="block text-sm font-medium text-gray-700 mb-1">تاریخ آخرین خرید پیامک از</label>
                    <input type="text" name="last_sms_purchase_start" id="last_sms_purchase_start" placeholder="برای مثال: 1402/01/01" class="jalali-datepicker form-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out">
                </div>
                <div class="col-span-1">
                    <label for="last_sms_purchase_end" class="block text-sm font-medium text-gray-700 mb-1">تاریخ آخرین خرید پیامک تا</label>
                    <input type="text" name="last_sms_purchase_end" id="last_sms_purchase_end" placeholder="برای مثال: 1402/12/29" class="jalali-datepicker form-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out">
                </div>
                <div class="col-span-1">
                    <label for="min_monthly_consumption" class="block text-sm font-medium text-gray-700 mb-1">حداقل مصرف ماهانه پیامک</label>
                    <input type="number" name="min_monthly_consumption" id="min_monthly_consumption" placeholder="برای مثال: 100" class="form-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out" value="{{ request('min_monthly_consumption') }}" min="0">
                </div>
                <div class="col-span-1">
                    <label for="max_monthly_consumption" class="block text-sm font-medium text-gray-700 mb-1">حداکثر مصرف ماهانه پیامک</label>
                    <input type="number" name="max_monthly_consumption" id="max_monthly_consumption" placeholder="برای مثال: 500" class="form-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out" value="{{ request('max_monthly_consumption') }}" min="0">
                </div>
            </div>
            <!-- Action Buttons -->
            <div class="flex justify-center items-center gap-4 pt-6 border-t border-gray-200 mt-6">
                <div class="flex justify-center items-center gap-4 w-full">
                    <button type="submit" class="flex items-center gap-2 px-5 py-2 border border-blue-600 text-sm font-semibold rounded-lg shadow-sm text-white bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200 transform hover:scale-105">
                        <i class="ri-search-line text-lg"></i>
                        <span>اعمال فیلتر</span>
                    </button>
                    <a href="{{ route('admin.export.bulk-sms-gift-users', request()->query()) }}" class="flex items-center gap-2 px-5 py-2 border border-green-600 text-sm font-semibold rounded-lg shadow-sm text-white bg-gradient-to-r from-green-600 to-lime-500 hover:from-green-700 hover:to-lime-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-200 transform hover:scale-105">
                        <i class="ri-file-excel-line text-lg"></i>
                        <span>خروجی اکسل</span>
                    </a>
                    <a href="{{ route('admin.bulk-sms-gift.index') }}" class="flex items-center gap-2 px-5 py-2 border border-gray-300 text-sm font-semibold rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200 transform hover:scale-105">
                        <i class="ri-close-line text-lg"></i>
                        <span>پاک کردن فیلترها</span>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white shadow-xl rounded-lg overflow-hidden mt-12">
        <form action="{{ route('admin.bulk-sms-gift.send') }}" method="POST" id="bulkGiftForm">
            @csrf
            
            <!-- Hidden inputs to preserve filters -->
            <input type="hidden" name="search" value="{{ request('search') }}">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <input type="hidden" name="province_id" value="{{ request('province_id') }}">
            <input type="hidden" name="city_id" value="{{ request('city_id') }}">
            <input type="hidden" name="business_category_id" value="{{ request('business_category_id') }}">
            <input type="hidden" name="business_subcategory_id" value="{{ request('business_subcategory_id') }}">
            <input type="hidden" name="sms_balance_status" value="{{ request('sms_balance_status') }}">
            <input type="hidden" name="min_sms_balance" value="{{ request('min_sms_balance') }}">
            <input type="hidden" name="max_sms_balance" value="{{ request('max_sms_balance') }}">
            <input type="hidden" name="last_sms_purchase" value="{{ request('last_sms_purchase') }}">
            <input type="hidden" name="monthly_sms_consumption" value="{{ request('monthly_sms_consumption') }}">
            <input type="hidden" name="gender" value="{{ request('gender') }}">
            <input type="hidden" name="min_age" value="{{ request('min_age') }}">
            <input type="hidden" name="max_age" value="{{ request('max_age') }}">
            
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">تعداد پیامک هدیه</label>
                        <input type="number" name="amount" id="amount" required class="form-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out" min="1">
                        @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-1">متن پیامک (اختیاری)</label>
                        <textarea name="message" id="message" rows="3" class="form-textarea w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out"></textarea>
                        @error('message') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex gap-3 mb-6" id="actionButtons">
                    <button type="submit" id="sendGiftButton" class="inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition ease-in-out duration-150">
                        <i class="ri-send-plane-line ml-2"></i> ارسال پیامک هدیه به سالن‌های انتخابی
                    </button>
                    <button type="button" onclick="showPackageActivationSection()" class="inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition ease-in-out duration-150">
                        <i class="ri-gift-line ml-2"></i> فعال‌سازی پکیج هدیه
                    </button>
                </div>
            </div>

            <!-- Package Activation Section (Hidden by default) -->
            <div id="packageActivationSection" class="p-6 bg-purple-50 border-t border-purple-200 hidden">
                <h3 class="text-lg font-semibold text-purple-900 mb-4">
                    <i class="ri-gift-line ml-2"></i>
                    فعال‌سازی گروهی پکیج امکانات هدیه
                </h3>
                <div class="mb-4">
                    <label for="package_id" class="block text-sm font-medium text-gray-700 mb-1">انتخاب پکیج</label>
                    <select name="package_id" id="package_id" class="form-select w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-white">
                        <option value="">انتخاب کنید</option>
                        @foreach($packages as $package)
                            <option value="{{ $package->id }}" 
                                    data-sms="{{ $package->gift_sms_count }}" 
                                    data-duration="{{ $package->duration_days }}"
                                    data-description="{{ $package->description }}">
                                {{ $package->name }} 
                                @if($package->gift_sms_count > 0)
                                    ({{ number_format($package->gift_sms_count) }} پیامک هدیه)
                                @endif
                                - {{ number_format($package->duration_days) }} روز اعتبار
                            </option>
                        @endforeach
                    </select>
                    @error('package_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div id="packageInfo" class="mb-4 p-4 bg-white rounded-lg border border-purple-200 hidden">
                    <p class="text-sm font-semibold text-gray-800 mb-2">اطلاعات پکیج انتخاب شده:</p>
                    <div id="packageDescription" class="text-sm text-gray-600"></div>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="submitPackageActivation()" class="inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition ease-in-out duration-150">
                        <i class="ri-check-line ml-2"></i> فعال‌سازی پکیج برای سالن‌های انتخابی
                    </button>
                    <button type="button" onclick="hidePackageActivationSection()" class="inline-flex items-center px-5 py-2.5 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition ease-in-out duration-150">
                        <i class="ri-close-line ml-2"></i> انصراف
                    </button>
                </div>
            </div>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="select-all-salons" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="text-xs normal-case" id="selection-info"></span>
                            </div>
                            <input type="hidden" name="select_all_filtered" id="select_all_filtered" value="0">
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            نام سالن
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            شماره تماس
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            مالک
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            شهر
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            موجودی پیامک
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            آخرین خرید پیامک
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($salons as $salon)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <input type="checkbox" name="salon_ids[]" value="{{ $salon->id }}" class="salon-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $salon->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $salon->owner->mobile ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $salon->owner->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $salon->city->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $salon->smsBalance->balance ?? 0 }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $salon->lastSmsPurchaseDate ? \Morilog\Jalali\Jalalian::fromCarbon($salon->lastSmsPurchaseDate)->format('Y/m/d') : 'تاکنون خرید نکرده' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                هیچ سالنی یافت نشد.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">
                {{ $salons->appends(request()->query())->links() }}
            </div>
        </form>
    </div>
</div>

<div id="selectionStatusBar" class="hidden fixed bottom-0 left-0 right-0 bg-blue-600 text-white shadow-lg z-50">
    <div class="container mx-auto px-4 py-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="ri-checkbox-multiple-line text-2xl"></i>
                <div>
                    <p class="font-semibold" id="selectionStatusText">در حال انتخاب...</p>
                    <p class="text-xs opacity-90" id="selectionStatusHint">برای انتخاب همه سالن‌های فیلتر شده، "انتخاب همه" را در بالای جدول بزنید</p>
                </div>
            </div>
            <button onclick="clearSelectionStatus()" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded text-sm">
                لغو انتخاب‌ها
            </button>
        </div>
    </div>
</div>

<div id="persistSelectionWarning" class="hidden fixed bottom-4 left-4 right-4 md:left-auto md:right-4 md:w-96 bg-yellow-50 border border-yellow-200 rounded-lg shadow-lg p-4 z-50">
    <div class="flex items-start">
        <i class="ri-information-line text-yellow-600 text-xl ml-2 mt-1"></i>
        <div class="flex-1">
            <p class="text-sm text-yellow-800 font-semibold mb-1">توجه!</p>
            <p class="text-xs text-yellow-700">
                برای انتخاب سالن‌ها در چند صفحه مختلف، از گزینه 
                <strong>"انتخاب همه سالن‌های فیلتر شده"</strong> 
                استفاده کنید یا ابتدا فیلترهای خود را اعمال کنید و سپس "انتخاب همه" را بزنید.
            </p>
            <button onclick="document.getElementById('persistSelectionWarning').classList.add('hidden')" class="mt-2 text-xs text-yellow-600 hover:text-yellow-800 underline">
                متوجه شدم
            </button>
        </div>
        <button onclick="document.getElementById('persistSelectionWarning').classList.add('hidden')" class="text-yellow-600 hover:text-yellow-800">
            <i class="ri-close-line text-lg"></i>
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Jalali date pickers
        $('.jalali-datepicker').each(function() {
            $(this).persianDatepicker({
                format: 'YYYY/MM/DD',
                autoClose: true,
                initialValue: false,
                onSelect: function(unix) {
                    var date = new persianDate(unix).format('YYYY/MM/DD');
                    this.el.val(date);
                }
            });
        });

        const provinceSelect = document.getElementById('province_id');
        const citySelect = document.getElementById('city_id');
        const businessCategorySelect = document.getElementById('business_category_id');
        const businessSubcategorySelect = document.getElementById('business_subcategory_id');

        const initialCityId = "{{ request('city_id') }}";
        const initialSubcategoryId = "{{ request('business_subcategory_id') }}";

        async function fetchAndPopulateCities(provinceId, selectedCityId = null) {
            citySelect.innerHTML = '<option value="">همه</option>'; // "All" option for filters
            if (!provinceId) {
                return;
            }
            try {
                const response = await fetch(`/api/general/provinces/${provinceId}/cities`);
                const cities = await response.json();
                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.id;
                    option.textContent = city.name;
                    if (selectedCityId && city.id == selectedCityId) {
                        option.selected = true;
                    }
                    citySelect.appendChild(option);
                });
            } catch (error) {
                console.error('Error fetching cities:', error);
            }
        }

        async function fetchAndPopulateSubcategories(categoryId, selectedSubcategoryId = null) {
            businessSubcategorySelect.innerHTML = '<option value="">همه</option>'; // "All" option for filters
            if (!categoryId) {
                return;
            }
            try {
                const response = await fetch(`/api/general/business-categories/${categoryId}/subcategories`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const responseData = await response.json();
                const subcategories = responseData.data; // Access the 'data' property
                if (Array.isArray(subcategories)) {
                    subcategories.forEach(subcategory => {
                        const option = document.createElement('option');
                        option.value = subcategory.id;
                        option.textContent = subcategory.name;
                        if (selectedSubcategoryId && subcategory.id == selectedSubcategoryId) {
                            option.selected = true;
                        }
                        businessSubcategorySelect.appendChild(option);
                    });
                } else {
                    console.error('Subcategories data is not an array:', subcategories);
                }
            } catch (error) {
                console.error('Error fetching subcategories:', error);
            }
        }

        provinceSelect.addEventListener('change', function () {
            fetchAndPopulateCities(this.value);
        });

        businessCategorySelect.addEventListener('change', function () {
            fetchAndPopulateSubcategories(this.value);
        });

        // Initial load for cities if a province is already selected
        if (provinceSelect.value) {
            fetchAndPopulateCities(provinceSelect.value, initialCityId);
        }

        // Initial load for subcategories if a business category is already selected
        if (businessCategorySelect.value) {
            fetchAndPopulateSubcategories(businessCategorySelect.value, initialSubcategoryId);
        }

        // Select All / Deselect All functionality
        const selectAllCheckbox = document.getElementById('select-all-salons');
        const salonCheckboxes = document.querySelectorAll('.salon-checkbox');
        const selectAllFilteredInput = document.getElementById('select_all_filtered');
        const selectionInfo = document.getElementById('selection-info');
        const totalFiltered = {{ $totalFilteredCount ?? 0 }};
        const currentPageCount = {{ $salons->count() }};

        selectAllCheckbox.addEventListener('change', function() {
            salonCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            
            if (this.checked && totalFiltered > currentPageCount) {
                // Show option to select all filtered
                selectionInfo.innerHTML = `
                    <span class="text-blue-600 cursor-pointer hover:underline" onclick="selectAllFiltered()">
                        ${currentPageCount} سالن در این صفحه انتخاب شد. 
                        <strong>همه ${totalFiltered.toLocaleString('fa-IR')} سالن فیلتر شده را انتخاب کنید؟</strong>
                    </span>
                `;
            } else {
                selectionInfo.innerHTML = '';
                selectAllFilteredInput.value = '0';
            }
        });

        salonCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (!this.checked) {
                    selectAllCheckbox.checked = false;
                    selectionInfo.innerHTML = '';
                    selectAllFilteredInput.value = '0';
                } else {
                    const allChecked = Array.from(salonCheckboxes).every(cb => cb.checked);
                    selectAllCheckbox.checked = allChecked;
                }
            });
        });

        window.selectAllFiltered = function() {
            selectAllFilteredInput.value = '1';
            selectionInfo.innerHTML = `
                <span class="text-green-600">
                    <strong>همه ${totalFiltered.toLocaleString('fa-IR')} سالن فیلتر شده انتخاب شدند.</strong>
                    <span class="text-blue-600 cursor-pointer hover:underline mr-2" onclick="clearSelection()">لغو</span>
                </span>
            `;
        };

        window.clearSelection = function() {
            selectAllFilteredInput.value = '0';
            selectAllCheckbox.checked = false;
            salonCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            selectionInfo.innerHTML = '';
            updateSelectionStatus();
        };

        window.clearSelectionStatus = function() {
            clearSelection();
            document.getElementById('selectionStatusBar').classList.add('hidden');
        };

        // Update selection status bar
        function updateSelectionStatus() {
            const statusBar = document.getElementById('selectionStatusBar');
            const statusText = document.getElementById('selectionStatusText');
            const checkedCount = Array.from(salonCheckboxes).filter(cb => cb.checked).length;
            const selectAllFiltered = selectAllFilteredInput.value === '1';

            if (selectAllFiltered) {
                statusBar.classList.remove('hidden');
                statusText.textContent = `همه ${totalFiltered.toLocaleString('fa-IR')} سالن فیلتر شده انتخاب شده‌اند`;
            } else if (checkedCount > 0) {
                statusBar.classList.remove('hidden');
                statusText.textContent = `${checkedCount} سالن در این صفحه انتخاب شده`;
            } else {
                statusBar.classList.add('hidden');
            }
        }

        // Update status on checkbox changes
        salonCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectionStatus);
        });

        selectAllCheckbox.addEventListener('change', updateSelectionStatus);

        // Show warning if user tries to navigate to another page with selections
        let hasSelections = false;
        salonCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                hasSelections = Array.from(salonCheckboxes).some(cb => cb.checked);
            });
        });

        // Intercept pagination clicks
        document.addEventListener('click', function(e) {
            const paginationLink = e.target.closest('a[href*="page="]');
            if (paginationLink && hasSelections && selectAllFilteredInput.value !== '1') {
                e.preventDefault();
                const warning = document.getElementById('persistSelectionWarning');
                warning.classList.remove('hidden');
                
                // Auto hide after 10 seconds
                setTimeout(() => {
                    warning.classList.add('hidden');
                }, 10000);
            }
        });
    });
</script>

<script>
    function showPackageActivationSection() {
        document.getElementById('packageActivationSection').classList.remove('hidden');
        document.getElementById('actionButtons').classList.add('hidden');
    }

    function hidePackageActivationSection() {
        document.getElementById('packageActivationSection').classList.add('hidden');
        document.getElementById('actionButtons').classList.remove('hidden');
        document.getElementById('package_id').value = '';
        document.getElementById('packageInfo').classList.add('hidden');
    }

    // Show package info when selected
    document.addEventListener('DOMContentLoaded', function() {
        const packageSelect = document.getElementById('package_id');
        const packageInfo = document.getElementById('packageInfo');
        const packageDescription = document.getElementById('packageDescription');

        if (packageSelect) {
            packageSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (this.value) {
                    const smsCount = selectedOption.getAttribute('data-sms');
                    const duration = selectedOption.getAttribute('data-duration');
                    const description = selectedOption.getAttribute('data-description');
                    
                    packageDescription.innerHTML = `
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <i class="ri-gift-line text-purple-600 ml-2"></i>
                                <span><strong>نام پکیج:</strong> ${selectedOption.text.split(' - ')[0]}</span>
                            </div>
                            ${description ? `<div class="flex items-start"><i class="ri-information-line text-purple-600 ml-2 mt-1"></i><span><strong>توضیحات:</strong> ${description}</span></div>` : ''}
                            <div class="flex items-center">
                                <i class="ri-message-3-line text-green-600 ml-2"></i>
                                <span><strong>پیامک هدیه:</strong> ${parseInt(smsCount).toLocaleString('fa-IR')} عدد</span>
                            </div>
                            <div class="flex items-center">
                                <i class="ri-time-line text-blue-600 ml-2"></i>
                                <span><strong>مدت اعتبار:</strong> ${parseInt(duration).toLocaleString('fa-IR')} روز (${Math.ceil(duration/30)} ماه)</span>
                            </div>
                        </div>
                    `;
                    packageInfo.classList.remove('hidden');
                } else {
                    packageInfo.classList.add('hidden');
                }
            });
        }
    });

    function submitPackageActivation() {
        const packageId = document.getElementById('package_id').value;
        if (!packageId) {
            alert('لطفاً یک پکیج انتخاب کنید');
            return;
        }

        const selectAllFiltered = document.getElementById('select_all_filtered').value === '1';
        const checkboxes = document.querySelectorAll('.salon-checkbox:checked');
        const totalFiltered = {{ $totalFilteredCount ?? 0 }};
        
        if (!selectAllFiltered && checkboxes.length === 0) {
            alert('لطفاً حداقل یک سالن را انتخاب کنید');
            return;
        }

        let confirmMessage = '';
        if (selectAllFiltered) {
            confirmMessage = `آیا از فعال‌سازی پکیج برای همه ${totalFiltered.toLocaleString('fa-IR')} سالن فیلتر شده اطمینان دارید؟`;
        } else {
            confirmMessage = `آیا از فعال‌سازی پکیج برای ${checkboxes.length} سالن انتخاب شده اطمینان دارید؟`;
        }

        if (!confirm(confirmMessage)) {
            return;
        }

        // Change form action and submit
        const form = document.getElementById('bulkGiftForm');
        form.action = "{{ route('admin.bulk-sms-gift.activate-package') }}";
        
        // Remove amount and message fields requirement for package activation
        document.getElementById('amount').removeAttribute('required');
        
        form.submit();
    }
</script>

<!-- Persian Date Picker CSS and JS -->
<link rel="stylesheet" href="{{ asset('vendor/persian-datepicker/css/persian-datepicker.min.css') }}">
<script src="{{ asset('vendor/persian-date/js/persian-date.min.js') }}"></script>
<script src="{{ asset('vendor/persian-datepicker/js/persian-datepicker.min.js') }}"></script>
@endpush
