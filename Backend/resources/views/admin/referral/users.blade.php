@extends('admin.layouts.app')

@section('title', 'مدیریت کاربران رفرال')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="p-6">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">مدیریت کاربران رفرال</h1>
            <p class="text-gray-600">مدیریت کاربران و آمار رفرال آن‌ها</p>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">جستجو</label>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="نام، تلفن یا کد رفرال" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">مرتب‌سازی</label>
                    <select name="sort" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="created_at_desc" {{ request('sort') === 'created_at_desc' ? 'selected' : '' }}>جدیدترین</option>
                        <option value="referrals_count_desc" {{ request('sort') === 'referrals_count_desc' ? 'selected' : '' }}>بیشترین دعوت</option>
                        <option value="wallet_balance_desc" {{ request('sort') === 'wallet_balance_desc' ? 'selected' : '' }}>بیشترین موجودی</option>
                        <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>نام (الف تا ی)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">فیلتر موجودی</label>
                    <select name="balance_filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">همه</option>
                        <option value="has_balance" {{ request('balance_filter') === 'has_balance' ? 'selected' : '' }}>دارای موجودی</option>
                        <option value="no_balance" {{ request('balance_filter') === 'no_balance' ? 'selected' : '' }}>بدون موجودی</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="ri-search-line ml-2"></i>جستجو
                    </button>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">کاربر</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">کد رفرال</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تعداد دعوت</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">موجودی کیف پول</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ عضویت</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($users as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                            <i class="ri-user-line text-gray-600"></i>
                                        </div>
                                    </div>
                                    <div class="mr-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $user->phone }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-mono bg-gray-100 px-2 py-1 rounded">{{ $user->referral_code }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    کل: {{ number_format($user->referrals_count) }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    موفق: {{ number_format($user->successful_referrals_count) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ number_format($user->wallet_balance / 10) }} تومان
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ verta($user->created_at)->format('Y/m/d') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2 space-x-reverse">
                                    <a href="{{ route('admin.referral.users.referrals', $user) }}" 
                                       class="bg-blue-100 text-blue-800 px-3 py-1 rounded-lg hover:bg-blue-200 transition-colors">
                                        <i class="ri-group-line ml-1"></i>دعوت‌ها
                                    </a>
                                    <a href="{{ route('admin.referral.users.wallet', $user) }}" 
                                       class="bg-green-100 text-green-800 px-3 py-1 rounded-lg hover:bg-green-200 transition-colors">
                                        <i class="ri-wallet-line ml-1"></i>کیف پول
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="text-gray-500">
                                    <i class="ri-user-line text-4xl mb-4"></i>
                                    <p>هیچ کاربری یافت نشد</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($users->hasPages())
            <div class="bg-white px-6 py-4 border-t border-gray-200">
                {{ $users->links() }}
            </div>
            @endif
        </div>

        <!-- Summary Stats -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="text-sm text-gray-600">کل کاربران</div>
                <div class="text-2xl font-bold text-gray-900">{{ number_format($summary['total_users']) }}</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="text-sm text-gray-600">کل موجودی کیف پول‌ها</div>
                <div class="text-2xl font-bold text-gray-900">{{ number_format($summary['total_wallet_balance'] / 10) }} تومان</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="text-sm text-gray-600">کل دعوت‌نامه‌ها</div>
                <div class="text-2xl font-bold text-gray-900">{{ number_format($summary['total_referrals']) }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
