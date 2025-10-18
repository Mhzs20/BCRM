@extends('admin.layouts.app')

@section('title', 'کیف پول ' . $user->name)

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
                    <h1 class="text-3xl font-bold text-gray-900">کیف پول {{ $user->name }}</h1>
                    <p class="text-gray-600">{{ $user->phone }} | موجودی فعلی: <span class="font-bold text-green-600">{{ number_format($user->wallet_balance / 10) }} تومان</span></p>
                </div>
            </div>
        </div>

        <!-- Wallet Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6 border-r-4 border-green-500">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <i class="ri-wallet-line text-green-600 text-xl"></i>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm text-gray-600">موجودی فعلی</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($user->wallet_balance) }}</p>
                        <p class="text-xs text-gray-500">ریال</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6 border-r-4 border-blue-500">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i class="ri-add-circle-line text-blue-600 text-xl"></i>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm text-gray-600">کل واریزی‌ها</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_credits']) }}</p>
                        <p class="text-xs text-gray-500">ریال</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6 border-r-4 border-red-500">
                <div class="flex items-center">
                    <div class="p-2 bg-red-100 rounded-lg">
                        <i class="ri-subtract-line text-red-600 text-xl"></i>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm text-gray-600">کل برداشت‌ها</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_debits']) }}</p>
                        <p class="text-xs text-gray-500">ریال</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6 border-r-4 border-yellow-500">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <i class="ri-exchange-line text-yellow-600 text-xl"></i>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm text-gray-600">کل تراکنش‌ها</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_transactions']) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">عملیات سریع</h3>
            <div class="flex space-x-4 space-x-reverse">
                <button onclick="showManualCreditModal()" 
                        class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                    <i class="ri-add-line ml-2"></i>افزایش موجودی
                </button>
                <button onclick="showManualDebitModal()" 
                        class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                    <i class="ri-subtract-line ml-2"></i>کاهش موجودی
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">نوع تراکنش</label>
                    <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">همه</option>
                        <option value="referral_reward" {{ request('type') === 'referral_reward' ? 'selected' : '' }}>پاداش رفرال</option>
                        <option value="order_reward" {{ request('type') === 'order_reward' ? 'selected' : '' }}>پاداش خرید</option>
                        <option value="manual_credit" {{ request('type') === 'manual_credit' ? 'selected' : '' }}>شارژ دستی</option>
                        <option value="manual_debit" {{ request('type') === 'manual_debit' ? 'selected' : '' }}>کسر دستی</option>
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
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">مرتب‌سازی</label>
                    <select name="sort" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="created_at_desc" {{ request('sort') === 'created_at_desc' ? 'selected' : '' }}>جدیدترین</option>
                        <option value="created_at_asc" {{ request('sort') === 'created_at_asc' ? 'selected' : '' }}>قدیمی‌ترین</option>
                        <option value="amount_desc" {{ request('sort') === 'amount_desc' ? 'selected' : '' }}>بیشترین مبلغ</option>
                        <option value="amount_asc" {{ request('sort') === 'amount_asc' ? 'selected' : '' }}>کمترین مبلغ</option>
                    </select>
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
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    @if($transaction->type === 'referral_reward') bg-green-100 text-green-800
                                    @elseif($transaction->type === 'order_reward') bg-blue-100 text-blue-800
                                    @elseif($transaction->type === 'manual_credit') bg-purple-100 text-purple-800
                                    @elseif($transaction->type === 'manual_debit') bg-orange-100 text-orange-800
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
                                <div class="text-sm text-gray-900 max-w-xs" title="{{ $transaction->description }}">
                                    {{ $transaction->description ?: '-' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ verta($transaction->created_at)->format('Y/m/d H:i') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
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

        <!-- Balance History Chart -->
        @if($stats['total_transactions'] > 0)
        <div class="mt-8 bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">روند موجودی کیف پول</h3>
            <canvas id="balanceHistoryChart"></canvas>
        </div>
        @endif
    </div>
</div>

<!-- Manual Credit Modal -->
<div id="manualCreditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">افزایش موجودی دستی</h3>
            <form id="manualCreditForm">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">مبلغ (ریال)</label>
                    <input type="number" name="amount" min="1000" step="1000" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">توضیحات</label>
                    <textarea name="description" rows="3" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                              placeholder="دلیل افزایش موجودی"></textarea>
                </div>
                <div class="flex justify-end space-x-3 space-x-reverse">
                    <button type="button" onclick="closeModal('manualCreditModal')" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                        انصراف
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        افزایش موجودی
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Manual Debit Modal -->
<div id="manualDebitModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">کاهش موجودی دستی</h3>
            <p class="text-sm text-gray-600 mb-4">موجودی فعلی: <span class="font-bold">{{ number_format($user->wallet_balance / 10) }} تومان</span></p>
            <form id="manualDebitForm">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">مبلغ (ریال)</label>
                    <input type="number" name="amount" min="1000" max="{{ $user->wallet_balance }}" step="1000" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">توضیحات</label>
                    <textarea name="description" rows="3" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                              placeholder="دلیل کاهش موجودی"></textarea>
                </div>
                <div class="flex justify-end space-x-3 space-x-reverse">
                    <button type="button" onclick="closeModal('manualDebitModal')" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                        انصراف
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        کاهش موجودی
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
@if($stats['total_transactions'] > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const balanceCtx = document.getElementById('balanceHistoryChart').getContext('2d');
new Chart(balanceCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($chart['labels']) !!},
        datasets: [{
            label: 'موجودی کیف پول',
            data: {!! json_encode($chart['balances']) !!},
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return new Intl.NumberFormat('fa-IR').format(value) + ' ریال';
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'موجودی: ' + new Intl.NumberFormat('fa-IR').format(context.parsed.y) + ' ریال';
                    }
                }
            }
        }
    }
});
</script>
@endif

<script>
function showManualCreditModal() {
    document.getElementById('manualCreditModal').classList.remove('hidden');
}

function showManualDebitModal() {
    document.getElementById('manualDebitModal').classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

// Handle manual credit form
document.getElementById('manualCreditForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('user_id', {{ $user->id }});
    
    fetch('{{ route("admin.referral.wallet.manual-credit") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'خطا در افزایش موجودی');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('خطا در ارسال درخواست');
    });
});

// Handle manual debit form
document.getElementById('manualDebitForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('user_id', {{ $user->id }});
    
    fetch('{{ route("admin.referral.wallet.manual-debit") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'خطا در کاهش موجودی');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('خطا در ارسال درخواست');
    });
});
</script>
@endpush
@endsection