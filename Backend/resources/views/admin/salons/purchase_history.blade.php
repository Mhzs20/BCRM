@extends('admin.layouts.app')

@section('title', 'تاریخچه کامل تراکنش‌های سالن: ' . $salon->name)

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-extrabold text-gray-900 mb-2 text-center font-sans">تاریخچه کامل تراکنش‌های مالی و پیامکی</h1>
    <p class="text-center text-gray-600 mb-8">سالن: <strong>{{ $salon->name }}</strong></p>

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

    <!-- Navigation Tabs -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 space-x-reverse" aria-label="Tabs">
                <button onclick="showTab('orders')" id="tab-orders" class="tab-button border-blue-500 text-blue-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    <i class="ri-shopping-cart-line ml-2"></i>
                    سفارشات و خریدها
                </button>
                <button onclick="showTab('gifts')" id="tab-gifts" class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    <i class="ri-gift-line ml-2"></i>
                    هدایای ادمین
                </button>
                <button onclick="showTab('wallet')" id="tab-wallet" class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    <i class="ri-wallet-3-line ml-2"></i>
                    تراکنش‌های کیف پول
                </button>
                <button onclick="showTab('sms')" id="tab-sms" class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    <i class="ri-message-3-line ml-2"></i>
                    تراکنش‌های پیامک
                </button>
                <button onclick="showTab('payments')" id="tab-payments" class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    <i class="ri-bank-card-line ml-2"></i>
                    تراکنش‌های درگاه
                </button>
            </nav>
        </div>
    </div>

    <!-- Orders Tab -->
    <div id="content-orders" class="tab-content">
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div class="bg-blue-50 px-6 py-4 border-b border-blue-200">
                <h2 class="text-xl font-bold text-blue-900">
                    <i class="ri-shopping-cart-line ml-2"></i>
                    سفارشات و خریدهای سالن
                </h2>
                <p class="text-sm text-blue-700 mt-1">تمام سفارشات خرید پکیج پیامک، پکیج امکانات و سایر موارد</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">شناسه</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نوع سفارش</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">جزئیات</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">مبلغ</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تخفیف</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($orders as $order)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #{{ $order->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($order->type === 'sms_package' || $order->sms_package_id)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <i class="ri-message-3-line ml-1"></i>
                                            پکیج پیامک
                                        </span>
                                    @elseif($order->type === 'package' || $order->package_id)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            <i class="ri-gift-line ml-1"></i>
                                            پکیج امکانات
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ $order->type ?? 'سایر' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    @if($order->smsPackage)
                                        {{ $order->smsPackage->name }} - {{ number_format($order->sms_count) }} پیامک
                                    @elseif($order->package)
                                        {{ $order->package->name }}
                                    @else
                                        {{ $order->item_title ?? $order->description ?? '-' }}
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                    {{ number_format($order->amount) }} تومان
                                    @if($order->original_amount && $order->original_amount != $order->amount)
                                        <div class="text-xs text-gray-400 line-through">{{ number_format($order->original_amount) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($order->discount_amount > 0)
                                        <span class="text-green-600 font-medium">{{ number_format($order->discount_amount) }} تومان</span>
                                        @if($order->discount_code)
                                            <div class="text-xs text-gray-400">کد: {{ $order->discount_code }}</div>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($order->status === 'paid')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            پرداخت شده
                                        </span>
                                    @elseif($order->status === 'pending')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            در انتظار
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            {{ $order->status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ verta($order->created_at)->format('Y/m/d H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                                    <i class="ri-inbox-line text-4xl text-gray-300 mb-2"></i>
                                    <p>هیچ سفارشی یافت نشد.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 bg-gray-50">
                {{ $orders->links('pagination::tailwind') }}
            </div>
        </div>
    </div>

    <!-- Admin Gifts Tab -->
    <div id="content-gifts" class="tab-content hidden">
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div class="bg-gradient-to-r from-pink-50 to-purple-50 px-6 py-4 border-b border-pink-200">
                <h2 class="text-xl font-bold text-pink-900">
                    <i class="ri-gift-line ml-2"></i>
                    هدایای دریافتی از ادمین
                </h2>
                <p class="text-sm text-pink-700 mt-1">تمام پیامک‌ها و پکیج‌های رایگان فعال شده توسط ادمین</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">شناسه</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نوع هدیه</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">جزئیات</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">مقدار</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">ثبت کننده</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">توضیحات</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($adminGiftsPaginated as $gift)
                            <tr class="hover:bg-pink-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #{{ $gift->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($gift->gift_type === 'sms_gift')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            <i class="ri-message-3-line ml-1"></i>
                                            پیامک هدیه
                                        </span>
                                    @elseif($gift->gift_type === 'package_gift')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-pink-100 text-pink-800">
                                            <i class="ri-vip-crown-line ml-1"></i>
                                            پکیج رایگان
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    @if($gift->gift_type === 'sms_gift')
                                        <span class="font-medium">هدیه پیامک از ادمین</span>
                                    @elseif($gift->gift_type === 'package_gift' && $gift->package)
                                        <span class="font-medium">{{ $gift->package->name }}</span>
                                        @if($gift->package->gift_sms_count > 0)
                                            <div class="text-xs text-purple-600 mt-1">
                                                <i class="ri-gift-2-line ml-1"></i>
                                                شامل {{ number_format($gift->package->gift_sms_count) }} پیامک هدیه
                                            </div>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($gift->gift_type === 'sms_gift')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-purple-100 text-purple-800">
                                            <i class="ri-message-line ml-1"></i>
                                            {{ number_format($gift->amount) }} پیامک
                                        </span>
                                    @elseif($gift->gift_type === 'package_gift')
                                        @php
                                            $duration = $gift->expires_at ? \Carbon\Carbon::parse($gift->expires_at)->diffInDays($gift->purchased_at) : 0;
                                        @endphp
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-pink-100 text-pink-800">
                                            <i class="ri-calendar-line ml-1"></i>
                                            {{ $duration }} روز
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($gift->gift_type === 'sms_gift')
                                        @if($gift->status === 'delivered' || $gift->status === 'completed')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                فعال شده
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                {{ $gift->status }}
                                            </span>
                                        @endif
                                    @elseif($gift->gift_type === 'package_gift')
                                        @if($gift->status === 'active')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                <i class="ri-checkbox-circle-line ml-1"></i>
                                                فعال
                                            </span>
                                        @elseif($gift->status === 'expired')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                <i class="ri-close-circle-line ml-1"></i>
                                                منقضی شده
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                {{ $gift->status }}
                                            </span>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    @if($gift->gift_type === 'sms_gift' && $gift->approver)
                                        <div class="flex items-center">
                                            <i class="ri-admin-line text-indigo-600 ml-1"></i>
                                            <span class="font-medium">{{ $gift->approver->name }}</span>
                                        </div>
                                        @if($gift->approver->email)
                                            <div class="text-xs text-gray-400">{{ $gift->approver->email }}</div>
                                        @endif
                                    @elseif($gift->gift_type === 'package_gift' && $gift->order && $gift->order->user)
                                        <div class="flex items-center">
                                            <i class="ri-admin-line text-indigo-600 ml-1"></i>
                                            <span class="font-medium">{{ $gift->order->user->name }}</span>
                                        </div>
                                        @if($gift->order->user->email)
                                            <div class="text-xs text-gray-400">{{ $gift->order->user->email }}</div>
                                        @endif
                                    @else
                                        <span class="text-gray-400 text-xs">سیستم</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs">
                                    @if($gift->gift_type === 'sms_gift')
                                        <div class="truncate" title="{{ $gift->description }}">{{ $gift->description ?? '-' }}</div>
                                        @if($gift->content)
                                            <div class="text-xs text-gray-400 mt-1 truncate" title="{{ $gift->content }}">پیام: {{ $gift->content }}</div>
                                        @endif
                                    @elseif($gift->gift_type === 'package_gift')
                                        <div>فعال‌سازی رایگان توسط ادمین</div>
                                        @if($gift->expires_at)
                                            <div class="text-xs text-gray-400 mt-1">
                                                انقضا: {{ verta($gift->expires_at)->format('Y/m/d') }}
                                            </div>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ verta($gift->created_at)->format('Y/m/d H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">
                                    <i class="ri-gift-line text-4xl text-gray-300 mb-2"></i>
                                    <p>هیچ هدیه‌ای از ادمین دریافت نشده است.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 bg-gray-50">
                {{ $adminGiftsPaginated->links('pagination::tailwind') }}
            </div>
        </div>
    </div>

    <!-- Wallet Transactions Tab -->
    <div id="content-wallet" class="tab-content hidden">
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div class="bg-green-50 px-6 py-4 border-b border-green-200">
                <h2 class="text-xl font-bold text-green-900">
                    <i class="ri-wallet-3-line ml-2"></i>
                    تراکنش‌های کیف پول
                </h2>
                <p class="text-sm text-green-700 mt-1">تمام واریزها و برداشت‌های کیف پول سالن</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">شناسه</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نوع</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">مبلغ</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">موجودی قبل</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">موجودی بعد</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">توضیحات</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($walletTransactions as $wallet)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #{{ $wallet->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $wallet->isCredit() ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        @if($wallet->isCredit())
                                            <i class="ri-arrow-down-circle-line ml-1"></i>
                                        @else
                                            <i class="ri-arrow-up-circle-line ml-1"></i>
                                        @endif
                                        {{ $wallet->type_display }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold {{ $wallet->isCredit() ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $wallet->isCredit() ? '+' : '' }}{{ number_format($wallet->amount) }} تومان
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ number_format($wallet->balance_before) }} تومان
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ number_format($wallet->balance_after) }} تومان
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($wallet->status === 'completed')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            تکمیل شده
                                        </span>
                                    @elseif($wallet->status === 'pending')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            در انتظار
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            {{ $wallet->status_display }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                    {{ $wallet->description ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ verta($wallet->created_at)->format('Y/m/d H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">
                                    <i class="ri-inbox-line text-4xl text-gray-300 mb-2"></i>
                                    <p>هیچ تراکنش کیف پولی یافت نشد.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 bg-gray-50">
                {{ $walletTransactions->links('pagination::tailwind') }}
            </div>
        </div>
    </div>

    <!-- SMS Transactions Tab -->
    <div id="content-sms" class="tab-content hidden">
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div class="bg-purple-50 px-6 py-4 border-b border-purple-200">
                <h2 class="text-xl font-bold text-purple-900">
                    <i class="ri-message-3-line ml-2"></i>
                    تراکنش‌های پیامک
                </h2>
                <p class="text-sm text-purple-700 mt-1">هدایا، مصرف پیامک، و تمام تغییرات موجودی پیامکی</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">شناسه</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نوع</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تعداد پیامک</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">گیرنده</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">توضیحات</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($smsTransactions as $sms)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #{{ $sms->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($sms->type === 'gift')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            <i class="ri-gift-line ml-1"></i>
                                            هدیه
                                        </span>
                                    @elseif($sms->type === 'deduction')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <i class="ri-subtract-line ml-1"></i>
                                            برداشت
                                        </span>
                                    @elseif($sms->type === 'purchase')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <i class="ri-shopping-cart-line ml-1"></i>
                                            خرید
                                        </span>
                                    @elseif($sms->type === 'usage')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                            <i class="ri-send-plane-line ml-1"></i>
                                            مصرف
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ $sms->sms_type ?? $sms->type ?? '-' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-purple-600">
                                    @php
                                        // Calculate SMS parts if not stored
                                        $smsCount = $sms->sms_count ?? $sms->sms_parts ?? $sms->amount;
                                        
                                        // If still 0 or null, calculate from content length
                                        if (!$smsCount || $smsCount == 0) {
                                            $content = $sms->content ?? $sms->original_content ?? $sms->edited_content ?? '';
                                            $contentLength = mb_strlen($content, 'UTF-8');
                                            
                                            // Persian SMS: 70 chars for 1 part, 67 chars for multi-part
                                            if ($contentLength <= 70) {
                                                $smsCount = $contentLength > 0 ? 1 : 0;
                                            } else {
                                                $smsCount = ceil($contentLength / 67);
                                            }
                                        }
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $smsCount > 0 ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-600' }}">
                                        {{ number_format($smsCount) }} پیامک
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                    {{ $sms->receptor ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($sms->status === 'delivered')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            ارسال شده
                                        </span>
                                    @elseif($sms->status === 'pending')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            در انتظار
                                        </span>
                                    @elseif($sms->status === 'failed')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            ناموفق
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            {{ $sms->status ?? '-' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 max-w-md">
                                    <div class="whitespace-pre-wrap break-words">{{ $sms->description ?? $sms->content ?? $sms->original_content ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ verta($sms->created_at)->format('Y/m/d H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                                    <i class="ri-inbox-line text-4xl text-gray-300 mb-2"></i>
                                    <p>هیچ تراکنش پیامکی یافت نشد.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 bg-gray-50">
                {{ $smsTransactions->links('pagination::tailwind') }}
            </div>
        </div>
    </div>

    <!-- Payment Gateway Transactions Tab -->
    <div id="content-payments" class="tab-content hidden">
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div class="bg-indigo-50 px-6 py-4 border-b border-indigo-200">
                <h2 class="text-xl font-bold text-indigo-900">
                    <i class="ri-bank-card-line ml-2"></i>
                    تراکنش‌های درگاه پرداخت
                </h2>
                <p class="text-sm text-indigo-700 mt-1">تمام تراکنش‌های انجام شده از طریق درگاه‌های بانکی</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">شناسه تراکنش</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">درگاه</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">سفارش مرتبط</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">مبلغ</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">کد پیگیری</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($paymentTransactions as $payment)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 font-mono">
                                    {{ $payment->transaction_id ?? $payment->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                        @if($payment->gateway === 'zarinpal')
                                            <i class="ri-bank-card-2-line ml-1"></i>
                                            زرین‌پال
                                        @elseif($payment->gateway === 'idpay')
                                            <i class="ri-secure-payment-line ml-1"></i>
                                            آیدی‌پی
                                        @elseif($payment->gateway === 'wallet')
                                            <i class="ri-wallet-3-line ml-1"></i>
                                            کیف پول
                                        @else
                                            {{ $payment->gateway ?? 'نامشخص' }}
                                        @endif
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    @if($payment->order)
                                        <a href="#" class="text-blue-600 hover:text-blue-800">
                                            سفارش #{{ $payment->order->id }}
                                        </a>
                                        <div class="text-xs text-gray-400">
                                            {{ $payment->order->smsPackage->name ?? $payment->order->package->name ?? $payment->order->item_title ?? '-' }}
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                    {{ number_format($payment->amount) }} تومان
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                    {{ $payment->reference_id ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($payment->status === 'completed' || $payment->status === 'success')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            موفق
                                        </span>
                                    @elseif($payment->status === 'pending')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            در انتظار
                                        </span>
                                    @elseif($payment->status === 'failed')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            ناموفق
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            {{ $payment->status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ verta($payment->created_at)->format('Y/m/d H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                                    <i class="ri-inbox-line text-4xl text-gray-300 mb-2"></i>
                                    <p>هیچ تراکنش درگاهی یافت نشد.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 bg-gray-50">
                {{ $paymentTransactions->links('pagination::tailwind') }}
            </div>
        </div>
    </div>

    <div class="mt-8 text-center">
        <a href="{{ route('admin.salons.show', $salon->id) }}" class="inline-flex items-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i class="ri-arrow-right-line ml-2"></i>
            بازگشت به پروفایل سالن
        </a>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active state from all tabs
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('border-blue-500', 'text-blue-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab content
    document.getElementById('content-' + tabName).classList.remove('hidden');
    
    // Add active state to selected tab
    const activeTab = document.getElementById('tab-' + tabName);
    activeTab.classList.remove('border-transparent', 'text-gray-500');
    activeTab.classList.add('border-blue-500', 'text-blue-600');
    
    // Save active tab to localStorage
    localStorage.setItem('activeTransactionTab', tabName);
    
    // Update URL hash without scrolling
    history.replaceState(null, null, '#' + tabName);
}

// Initialize tab on page load
document.addEventListener('DOMContentLoaded', function() {
    // Check URL hash first
    let activeTab = window.location.hash.replace('#', '');
    
    // If no hash, check localStorage
    if (!activeTab) {
        activeTab = localStorage.getItem('activeTransactionTab');
    }
    
    // If still no tab, check if there's a pagination parameter
    const urlParams = new URLSearchParams(window.location.search);
    if (!activeTab) {
        if (urlParams.has('orders_page')) {
            activeTab = 'orders';
        } else if (urlParams.has('gifts_page')) {
            activeTab = 'gifts';
        } else if (urlParams.has('wallet_page')) {
            activeTab = 'wallet';
        } else if (urlParams.has('sms_page')) {
            activeTab = 'sms';
        } else if (urlParams.has('payment_page')) {
            activeTab = 'payments';
        }
    }
    
    // Default to orders if nothing found
    if (!activeTab || !['orders', 'gifts', 'wallet', 'sms', 'payments'].includes(activeTab)) {
        activeTab = 'orders';
    }
    
    showTab(activeTab);
});
</script>

<style>
.tab-button {
    transition: all 0.2s ease-in-out;
}
.tab-button:hover {
    border-color: #cbd5e0 !important;
}
</style>
@endsection
