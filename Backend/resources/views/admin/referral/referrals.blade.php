@extends('admin.layouts.app')

@section('title', 'مدیریت دعوت‌نامه‌ها')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="p-6">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">مدیریت دعوت‌نامه‌ها</h1>
            <p class="text-gray-600">نظارت بر تمام دعوت‌نامه‌های ایجاد شده</p>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">جستجو</label>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="نام دعوت‌کننده یا دعوت‌شده" 
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

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i class="ri-user-add-line text-blue-600 text-xl"></i>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm text-gray-600">کل دعوت‌نامه‌ها</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total']) }}</p>
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
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['pending']) }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <i class="ri-check-line text-green-600 text-xl"></i>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm text-gray-600">تکمیل شده</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['completed']) }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-gray-100 rounded-lg">
                        <i class="ri-percent-line text-gray-600 text-xl"></i>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm text-gray-600">نرخ موفقیت</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['success_rate'], 1) }}%</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Referrals Table -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">دعوت‌کننده</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">دعوت‌شده</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">پاداش</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ دعوت</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ تکمیل</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($referrals as $referral)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8">
                                        <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                            <i class="ri-user-line text-blue-600 text-sm"></i>
                                        </div>
                                    </div>
                                    <div class="mr-3">
                                        <div class="text-sm font-medium text-gray-900">{{ $referral->referrer->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $referral->referrer->phone }}</div>
                                    </div>
                                </div>
                            </td>
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
                                    <div class="text-xs">هنوز ثبت‌نام نکرده</div>
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
                                    {{ number_format($referral->reward_amount) }} ریال
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
    </div>
</div>
@endsection