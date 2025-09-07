@extends('admin.layouts.app')

@section('title', 'کاربران هدف کد تخفیف')

@section('header')
    <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            کاربران هدف کد تخفیف: {{ $discountCode->code }}
        </h2>
        <a href="{{ route('admin.discount-codes.edit', $discountCode) }}"
           class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500">
            <i class="ri-arrow-right-line text-lg ml-2"></i>
            بازگشت به ویرایش
        </a>
    </div>
@endsection

@section('content')
    <div class="bg-white shadow-sm rounded-lg">
        <!-- Discount Code Info -->
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $discountCode->code }}</div>
                    <div class="text-sm text-blue-600">کد تخفیف</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">
                        @if($discountCode->type === 'percentage')
                            {{ intval($discountCode->value) }}%
                        @else
                            {{ number_format($discountCode->value) }} تومان
                        @endif
                    </div>
                    <div class="text-sm text-green-600">مقدار تخفیف</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">
                        @if($discountCode->user_filter_type === 'all')
                            همه کاربران
                        @else
                            کاربران فیلتر شده
                        @endif
                    </div>
                    <div class="text-sm text-purple-600">نوع هدف‌گذاری</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-600">{{ $salons->total() }}</div>
                    <div class="text-sm text-orange-600">کاربران هدف</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-red-600">{{ $usedSalons->count() }}</div>
                    <div class="text-sm text-red-600">سالن‌های استفاده کننده</div>
                </div>
            </div>
        </div>

        <!-- Applied Filters -->
        @if(!empty($formattedFilters))
            <div class="p-6 border-b border-gray-200 bg-yellow-50">
                <h3 class="text-lg font-medium text-yellow-900 mb-3">فیلترهای اعمال شده</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($formattedFilters as $key => $value)
                        <div class="bg-yellow-100 text-yellow-800 text-sm font-medium px-3 py-2 rounded-lg">
                            <span class="font-semibold">{{ $key }}:</span> {{ $value }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Salons that have used this discount code -->
        @if($usedSalons->count() > 0)
            <div class="p-6 border-b border-gray-200 bg-red-50">
                <h3 class="text-lg font-medium text-red-900 mb-4">سالن‌های استفاده کننده از این کد تخفیف</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-right text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-red-100">
                            <tr>
                                <th scope="col" class="px-4 py-2">نام سالن</th>
                                <th scope="col" class="px-4 py-2">مالک</th>
                                <th scope="col" class="px-4 py-2">شهر</th>
                                <th scope="col" class="px-4 py-2">تاریخ استفاده</th>
                                <th scope="col" class="px-4 py-2">شماره سفارش</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($usedSalons as $usage)
                                <tr class="bg-white border-b hover:bg-red-50">
                                    <td class="px-4 py-2 font-medium text-gray-900">{{ $usage->salon->name }}</td>
                                    <td class="px-4 py-2">{{ $usage->salon->owner->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-2">{{ $usage->salon->city->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-2">
                                        {{ \Morilog\Jalali\Jalalian::forge($usage->used_at)->format('Y/m/d H:i') }}
                                    </td>
                                    <td class="px-4 py-2">
                                        @if($usage->order_id)
                                            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded">
                                                #{{ $usage->order_id }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Search Form -->
        <div class="p-6 border-b border-gray-200">
            <form action="{{ route('admin.discount-codes.target-users', $discountCode) }}" method="GET" class="flex items-end gap-4">
                <div class="flex-1">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">جستجو در کاربران هدف</label>
                    <input type="text" 
                           name="search" 
                           id="search" 
                           placeholder="جستجو در نام سالن، نام مالک، شماره موبایل، شهر..."
                           value="{{ request('search') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="ri-search-line text-lg ml-2"></i>
                    جستجو
                </button>
                @if(request('search'))
                    <a href="{{ route('admin.discount-codes.target-users', $discountCode) }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="ri-close-line text-lg ml-2"></i>
                        پاک کردن
                    </a>
                @endif
            </form>
        </div>

        <!-- Salons Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">شناسه</th>
                        <th scope="col" class="px-6 py-3">نام سالن</th>
                        <th scope="col" class="px-6 py-3">مالک</th>
                        <th scope="col" class="px-6 py-3">شماره موبایل</th>
                        <th scope="col" class="px-6 py-3">شهر/استان</th>
                        <th scope="col" class="px-6 py-3">دسته‌بندی</th>
                        <th scope="col" class="px-6 py-3">موجودی پیامک</th>
                        <th scope="col" class="px-6 py-3">وضعیت</th>
                        <th scope="col" class="px-6 py-3">تاریخ عضویت</th>
                        <th scope="col" class="px-6 py-3">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($salons as $salon)
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $salon->id }}</td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $salon->name }}</div>
                                @if($salon->description)
                                    <div class="text-sm text-gray-500">{{ Str::limit($salon->description, 30) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $salon->owner->name ?? 'نامشخص' }}</div>
                                <div class="text-sm text-gray-500">مالک سالن</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $salon->owner->mobile ?? 'نامشخص' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $salon->city->name ?? 'نامشخص' }}</div>
                                <div class="text-sm text-gray-500">{{ $salon->city->province->name ?? 'نامشخص' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $salon->businessCategory->name ?? 'نامشخص' }}</div>
                                @if($salon->businessSubcategory)
                                    <div class="text-sm text-gray-500">{{ $salon->businessSubcategory->name }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if(($salon->smsBalance->balance ?? 0) > 200) bg-green-100 text-green-800
                                    @elseif(($salon->smsBalance->balance ?? 0) > 50) bg-yellow-100 text-yellow-800
                                    @else bg-red-100 text-red-800 @endif">
                                    {{ $salon->smsBalance->balance ?? 0 }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($salon->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <span class="w-2 h-2 mr-1 bg-green-400 rounded-full"></span>
                                        فعال
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <span class="w-2 h-2 mr-1 bg-red-400 rounded-full"></span>
                                        غیرفعال
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <div>{{ \Morilog\Jalali\Jalalian::forge($salon->created_at)->format('Y/m/d') }}</div>
                                <div class="text-xs text-gray-500">{{ \Morilog\Jalali\Jalalian::forge($salon->created_at)->format('H:i') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2 space-x-reverse">
                                    <a href="{{ route('admin.salons.show', $salon) }}" 
                                       class="text-indigo-600 hover:text-indigo-900 transition-colors duration-200"
                                       title="مشاهده جزئیات">
                                        <i class="ri-eye-line text-lg"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-4 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center py-8">
                                    <i class="ri-user-search-line text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-lg font-medium text-gray-500 mb-2">
                                        @if(request('search'))
                                            نتیجه‌ای برای جستجوی "{{ request('search') }}" یافت نشد
                                        @else
                                            هیچ کاربر هدفی یافت نشد
                                        @endif
                                    </p>
                                    <p class="text-sm text-gray-400">
                                        @if($discountCode->user_filter_type === 'all')
                                            این کد تخفیف برای همه کاربران قابل استفاده است
                                        @else
                                            فیلترهای تعریف شده هیچ کاربری را شامل نمی‌شود
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($salons->hasPages())
            <div class="p-4 border-t border-gray-200">
                {{ $salons->links() }}
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
@endpush
