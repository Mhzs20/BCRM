@extends('admin.layouts.app')

@section('title', 'دعوت‌نامه‌های ' . $user->name)

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="p-6">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center mb-4">
                <a href="{{ route('admin.referral.users') }}" 
                   class="text-blue-600 hover:text-blue-800 ml-4">
                    <i class="ri-arrow-right-line text-xl"></i>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">دعوت‌نامه‌های {{ $user->name }}</h1>
                    <p class="text-gray-600">{{ $user->phone }} | کد رفرال: {{ $user->referral_code }}</p>
                </div>
            </div>
        </div>

        <!-- User Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i class="ri-user-add-line text-blue-600 text-xl"></i>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm text-gray-600">کل دعوت‌نامه‌ها</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_referrals']) }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <i class="ri-check-line text-green-600 text-xl"></i>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm text-gray-600">دعوت‌های موفق</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['successful_referrals']) }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <i class="ri-time-line text-yellow-600 text-xl"></i>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm text-gray-600">در انتظار</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['pending_referrals']) }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <i class="ri-money-dollar-circle-line text-purple-600 text-xl"></i>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm text-gray-600">کل پاداش‌ها</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_rewards']) }} ریال</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">جستجو</label>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="نام یا شماره دعوت‌شده" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">همه</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>در انتظار</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>تکمیل شده</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>لغو شده</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">مرتب‌سازی</label>
                    <select name="sort" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="created_at_desc" {{ request('sort') === 'created_at_desc' ? 'selected' : '' }}>جدیدترین</option>
                        <option value="created_at_asc" {{ request('sort') === 'created_at_asc' ? 'selected' : '' }}>قدیمی‌ترین</option>
                        <option value="reward_desc" {{ request('sort') === 'reward_desc' ? 'selected' : '' }}>بیشترین پاداش</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="ri-search-line ml-2"></i>جستجو
                    </button>
                </div>
            </form>
        </div>

        <!-- Referrals Table -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">دعوت‌شده</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">پاداش</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ دعوت</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ تکمیل</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تعداد خریدها</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($referrals as $referral)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($referral->referred)
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8">
                                        <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                                            <i class="ri-user-line text-green-600 text-sm"></i>
                                        </div>
                                    </div>
                                    <div class="mr-3">
                                        <div class="text-sm font-medium text-gray-900">{{ $referral->referred->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $referral->referred->phone }}</div>
                                    </div>
                                </div>
                                @else
                                <div class="text-sm text-gray-500">
                                    <div>{{ $referral->referred_phone }}</div>
                                    <div class="text-xs text-red-500">هنوز ثبت‌نام نکرده</div>
                                </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    @if($referral->status === 'pending') bg-yellow-100 text-yellow-800
                                    @elseif($referral->status === 'completed') bg-green-100 text-green-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ $referral->status_display }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($referral->reward_amount)
                                    <span class="font-medium text-green-600">{{ number_format($referral->reward_amount) }} ریال</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ verta($referral->created_at)->format('Y/m/d H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($referral->completed_at)
                                    {{ verta($referral->completed_at)->format('Y/m/d H:i') }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($referral->referred)
                                    <span class="text-blue-600 font-medium">{{ $referral->referred->orders()->count() }}</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="text-gray-500">
                                    <i class="ri-group-line text-4xl mb-4"></i>
                                    <p>هیچ دعوت‌نامه‌ای یافت نشد</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($referrals->hasPages())
            <div class="bg-white px-6 py-4 border-t border-gray-200">
                {{ $referrals->links() }}
            </div>
            @endif
        </div>

        <!-- Monthly Performance Chart -->
        @if($stats['total_referrals'] > 0)
        <div class="mt-8 bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">عملکرد ماهانه</h3>
            <canvas id="monthlyPerformanceChart"></canvas>
        </div>
        @endif
    </div>
</div>

@if($stats['total_referrals'] > 0)
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const monthlyCtx = document.getElementById('monthlyPerformanceChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'bar',
    data: {
        labels: {!! json_encode($chart['labels']) !!},
        datasets: [{
            label: 'تعداد دعوت‌نامه',
            data: {!! json_encode($chart['referrals']) !!},
            backgroundColor: 'rgba(59, 130, 246, 0.8)',
            borderColor: 'rgb(59, 130, 246)',
            borderWidth: 1
        }, {
            label: 'دعوت‌های موفق',
            data: {!! json_encode($chart['successful']) !!},
            backgroundColor: 'rgba(34, 197, 94, 0.8)',
            borderColor: 'rgb(34, 197, 94)',
            borderWidth: 1
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
</script>
@endpush
@endif
@endsection