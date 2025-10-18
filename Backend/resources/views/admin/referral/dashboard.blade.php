@extends('admin.layouts.app')

@section('title', 'داشبورد سیستم رفرال')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="p-6">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">داشبورد سیستم رفرال</h1>
            <p class="text-gray-600">گزارش کلی از عملکرد سیستم رفرال</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Referrals -->
            <div class="bg-white rounded-lg shadow-lg p-6 border-r-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">کل دعوت‌نامه‌ها</p>
                        <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_referrals']) }}</p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="ri-user-add-line text-2xl text-blue-600"></i>
                    </div>
                </div>
            </div>

            <!-- Successful Referrals -->
            <div class="bg-white rounded-lg shadow-lg p-6 border-r-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">دعوت‌های موفق</p>
                        <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['successful_referrals']) }}</p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="ri-check-line text-2xl text-green-600"></i>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mt-2">نرخ موفقیت: {{ $stats['success_rate'] }}%</p>
            </div>

            <!-- Total Rewards -->
            <div class="bg-white rounded-lg shadow-lg p-6 border-r-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">کل پاداش‌ها</p>
                        <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_rewards']) }}</p>
                        <p class="text-sm text-gray-500">ریال</p>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <i class="ri-gift-line text-2xl text-yellow-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Referrals Trend Chart -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">روند دعوت‌نامه‌ها (7 روز گذشته)</h3>
                <canvas id="referralsChart"></canvas>
            </div>

            <!-- Rewards Distribution -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">توزیع پاداش‌ها</h3>
                <canvas id="rewardsChart"></canvas>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="bg-white rounded-lg shadow-lg">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">آخرین فعالیت‌ها</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">کاربر</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نوع فعالیت</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">مبلغ</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($recent_activities as $activity)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="mr-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $activity->user ? $activity->user->name : 'کاربر حذف شده' }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $activity->user ? $activity->user->phone : '-' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($activity->type === 'referral_reward') bg-green-100 text-green-800
                                    @elseif($activity->type === 'order_reward') bg-blue-100 text-blue-800
                                    @elseif($activity->type === 'manual_credit') bg-purple-100 text-purple-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ $activity->type_display }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($activity->amount) }} ریال
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ verta($activity->created_at)->format('Y/m/d H:i') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                هیچ فعالیت اخیری یافت نشد
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Referrals Trend Chart
const referralsCtx = document.getElementById('referralsChart').getContext('2d');
new Chart(referralsCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($charts['referrals_trend']['labels']) !!},
        datasets: [{
            label: 'دعوت‌نامه‌ها',
            data: {!! json_encode($charts['referrals_trend']['data']) !!},
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

// Rewards Distribution Chart
const rewardsCtx = document.getElementById('rewardsChart').getContext('2d');
new Chart(rewardsCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($charts['rewards_distribution']['labels']) !!},
        datasets: [{
            data: {!! json_encode($charts['rewards_distribution']['data']) !!},
            backgroundColor: [
                'rgb(34, 197, 94)',
                'rgb(59, 130, 246)',
                'rgb(168, 85, 247)',
                'rgb(245, 158, 11)'
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
