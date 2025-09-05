@extends('admin.layouts.app')

@section('title', 'مدیریت کدهای تخفیف')

@section('header')
    <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            مدیریت کدهای تخفیف
        </h2>
        <a href="{{ route('admin.discount-codes.create') }}"
           class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
            <i class="ri-add-line text-lg ml-2"></i>
            ایجاد کد تخفیف جدید
        </a>
    </div>
@endsection

@section('content')
    <div class="bg-white shadow-sm rounded-lg">
        <!-- Success Message -->
        @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <!-- Error Message -->
        @if(session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <!-- Stats Cards -->
        <div class="p-6 border-b border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $discountCodes->count() }}</div>
                    <div class="text-sm text-blue-600">کل کدها</div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-green-600">{{ $discountCodes->where('is_active', true)->count() }}</div>
                    <div class="text-sm text-green-600">فعال</div>
                </div>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-red-600">{{ $discountCodes->where('expires_at', '<', now())->where('is_active', true)->count() }}</div>
                    <div class="text-sm text-red-600">منقضی شده</div>
                </div>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-yellow-600">{{ $discountCodes->where('is_active', false)->count() }}</div>
                    <div class="text-sm text-yellow-600">غیرفعال</div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">شناسه</th>
                        <th scope="col" class="px-6 py-3">کد تخفیف</th>
                        <th scope="col" class="px-6 py-3">مقدار تخفیف</th>
                        <th scope="col" class="px-6 py-3">نوع هدف‌گذاری</th>
                        <th scope="col" class="px-6 py-3">وضعیت</th>
                        <th scope="col" class="px-6 py-3">تاریخ انقضا</th>
                        <th scope="col" class="px-6 py-3">استفاده سالن‌ها</th>
                        <th scope="col" class="px-6 py-3">تاریخ ایجاد</th>
                        <th scope="col" class="px-6 py-3">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($discountCodes as $discountCode)
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $discountCode->id }}</td>
                            <td class="px-6 py-4">
                                <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                    {{ $discountCode->code }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($discountCode->type === 'percentage')
                                    <span class="text-indigo-600 font-semibold">{{ intval($discountCode->value) }}%</span>
                                @else
                                    <span class="text-green-600 font-semibold">{{ number_format($discountCode->value) }} تومان</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($discountCode->user_filter_type === 'all')
                                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">همه کاربران</span>
                                @else
                                    <span class="bg-purple-100 text-purple-800 text-xs font-medium px-2.5 py-0.5 rounded-full" 
                                          title="{{ $discountCode->target_users ? 'فیلترهای اعمال شده' : 'فیلتر شده' }}">
                                        فیلتر شده
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($discountCode->is_active)
                                    @if($discountCode->expires_at && $discountCode->expires_at < now())
                                        <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full">منقضی شده</span>
                                    @else
                                        <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">فعال</span>
                                    @endif
                                @else
                                    <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full">غیرفعال</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($discountCode->expires_at)
                                    <span class="text-sm text-gray-600">
                                        {{ \Morilog\Jalali\Jalalian::forge($discountCode->expires_at)->format('Y/m/d') }}
                                    </span>
                                @else
                                    <span class="text-gray-400">بدون انقضا</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-1">
                                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full" title="تعداد سالن‌های استفاده کننده">
                                        {{ $discountCode->salon_usages_count ?? 0 }}
                                    </span>
                                    @if($discountCode->usage_limit)
                                        <span class="text-gray-400 text-xs">/ {{ $discountCode->usage_limit }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ \Morilog\Jalali\Jalalian::forge($discountCode->created_at)->format('Y/m/d') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2 space-x-reverse">
                                    @if($discountCode->user_filter_type === 'filtered' && $discountCode->target_users)
                                        <a href="{{ route('admin.discount-codes.target-users', $discountCode) }}"
                                           class="text-blue-600 hover:text-blue-900 transition-colors duration-200"
                                           title="مشاهده کاربران هدف">
                                            <i class="ri-group-line text-lg"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route('admin.discount-codes.edit', $discountCode) }}"
                                       class="text-indigo-600 hover:text-indigo-900 transition-colors duration-200">
                                        <i class="ri-edit-line text-lg"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.discount-codes.destroy', $discountCode) }}"
                                          onsubmit="return confirm('آیا از حذف این کد تخفیف اطمینان دارید؟')"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 transition-colors duration-200">
                                            <i class="ri-delete-bin-line text-lg"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-4 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center py-8">
                                    <i class="ri-coupon-line text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-lg font-medium text-gray-500 mb-2">هیچ کد تخفیفی یافت نشد</p>
                                    <p class="text-sm text-gray-400 mb-4">برای شروع، کد تخفیف جدیدی ایجاد کنید</p>
                                    <a href="{{ route('admin.discount-codes.create') }}"
                                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">
                                        <i class="ri-add-line text-lg ml-2"></i>
                                        ایجاد کد تخفیف
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
@endpush
