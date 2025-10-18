@extends('admin.layouts.app')

@section('title', 'مدیریت کیف پول')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="p-6">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">مدیریت کیف پول</h1>
            <p class="text-gray-600">نظارت بر تراکنش‌های کیف پول کاربران</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6 border-r-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">کل موجودی</p>
                        <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_balance']) }}</p>
                        <p class="text-sm text-gray-500">ریال</p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="ri-wallet-line text-2xl text-green-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 border-r-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">کل واریزی‌ها</p>
                        <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_credits']) }}</p>
                        <p class="text-sm text-gray-500">ریال</p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="ri-add-circle-line text-2xl text-blue-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 border-r-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">کل برداشت‌ها</p>
                        <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_debits']) }}</p>
                        <p class="text-sm text-gray-500">ریال</p>
                    </div>
                    <div class="bg-red-100 p-3 rounded-full">
                        <i class="ri-subtract-line text-2xl text-red-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 border-r-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">تراکنش‌های امروز</p>
                        <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['today_transactions']) }}</p>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <i class="ri-calendar-line text-2xl text-yellow-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">جستجو</label>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="نام یا شماره تلفن کاربر" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">نوع تراکنش</label>
                    <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">همه</option>
                        <option value="referral_reward" {{ request('type') === 'referral_reward' ? 'selected' : '' }}>پاداش رفرال</option>
                        <option value="order_reward" {{ request('type') === 'order_reward' ? 'selected' : '' }}>پاداش خرید</option>
                        <option value="manual_credit" {{ request('type') === 'manual_credit' ? 'selected' : '' }}>شارژ دستی</option>
                        <option value="withdraw" {{ request('type') === 'withdraw' ? 'selected' : '' }}>برداشت</option>
                        <option value="purchase" {{ request('type') === 'purchase' ? 'selected' : '' }}>خرید</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">از تاریخ</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">تا تاریخ</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="ri-search-line ml-2"></i>جستجو
                    </button>
                </div>
            </form>
        </div>

        <!-- Transactions Table -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">کاربر</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نوع تراکنش</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">مبلغ</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">موجودی قبل</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">موجودی بعد</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">توضیحات</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($transactions as $transaction)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8">
                                        <div class="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center">
                                            <i class="ri-user-line text-gray-600 text-sm"></i>
                                        </div>
                                    </div>
                                    <div class="mr-3">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $transaction->user ? $transaction->user->name : 'کاربر حذف شده' }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $transaction->user ? $transaction->user->phone : '-' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    @if($transaction->type === 'referral_reward') bg-green-100 text-green-800
                                    @elseif($transaction->type === 'order_reward') bg-blue-100 text-blue-800
                                    @elseif($transaction->type === 'manual_credit') bg-purple-100 text-purple-800
                                    @elseif($transaction->type === 'withdraw') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ $transaction->type_display }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium {{ $transaction->amount > 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $transaction->amount > 0 ? '+' : '' }}{{ number_format($transaction->amount) }} ریال
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($transaction->balance_before) }} ریال
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($transaction->balance_after) }} ریال
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 max-w-xs truncate" title="{{ $transaction->description }}">
                                    {{ $transaction->description ?: '-' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ verta($transaction->created_at)->format('Y/m/d H:i') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="text-gray-500">
                                    <i class="ri-wallet-line text-4xl mb-4"></i>
                                    <p>هیچ تراکنشی یافت نشد</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($transactions->hasPages())
            <div class="bg-white px-6 py-4 border-t border-gray-200">
                {{ $transactions->links() }}
            </div>
            @endif
        </div>

        <!-- Recent Charts Section -->
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Daily Transactions Chart -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">تراکنش‌های روزانه (7 روز گذشته)</h3>
                <canvas id="dailyTransactionsChart"></canvas>
            </div>

            <!-- Transaction Types Distribution -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">توزیع انواع تراکنش</h3>
                <canvas id="transactionTypesChart"></canvas>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Daily Transactions Chart
const dailyCtx = document.getElementById('dailyTransactionsChart').getContext('2d');
new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($charts['daily_transactions']['labels']) !!},
        datasets: [{
            label: 'تعداد تراکنش',
            data: {!! json_encode($charts['daily_transactions']['data']) !!},
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Transaction Types Chart
const typesCtx = document.getElementById('transactionTypesChart').getContext('2d');
new Chart(typesCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($charts['transaction_types']['labels']) !!},
        datasets: [{
            data: {!! json_encode($charts['transaction_types']['data']) !!},
            backgroundColor: [
                'rgb(34, 197, 94)',
                'rgb(59, 130, 246)',
                'rgb(168, 85, 247)',
                'rgb(245, 158, 11)',
                'rgb(239, 68, 68)'
            ]
        }]
    },
    options: {
        responsive: true
    }
});
</script>
@endpush
@endsection