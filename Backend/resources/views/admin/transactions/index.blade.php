@extends('admin.layouts.app')

@section('title','لیست تراکنش ها')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-bold mb-6">لیست تراکنش‌های پرداخت</h1>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs text-gray-500 mb-1">امروز</div>
            <div class="text-lg font-semibold">{{ number_format($stats['daily']->total_amount ?? 0) }} <span class="text-sm font-normal">تومان</span></div>
            <div class="text-xs text-gray-600">{{ $stats['daily']->count ?? 0 }} سفارش / {{ number_format($stats['daily']->total_sms ?? 0) }} SMS</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs text-gray-500 mb-1">این هفته</div>
            <div class="text-lg font-semibold">{{ number_format($stats['weekly']->total_amount ?? 0) }} <span class="text-sm font-normal">تومان</span></div>
            <div class="text-xs text-gray-600">{{ $stats['weekly']->count ?? 0 }} سفارش / {{ number_format($stats['weekly']->total_sms ?? 0) }} SMS</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs text-gray-500 mb-1">این ماه</div>
            <div class="text-lg font-semibold">{{ number_format($stats['monthly']->total_amount ?? 0) }} <span class="text-sm font-normal">تومان</span></div>
            <div class="text-xs text-gray-600">{{ $stats['monthly']->count ?? 0 }} سفارش / {{ number_format($stats['monthly']->total_sms ?? 0) }} SMS</div>
        </div>
    </div>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-7 gap-4 mb-6 bg-white p-4 rounded-lg shadow">
        <input name="salon" value="{{ $filters['salon'] ?? '' }}" placeholder="نام سالن" class="border rounded px-3 py-2 text-sm" />
        <input name="ref" value="{{ $filters['ref'] ?? '' }}" placeholder="کد مرجع/Authority" class="border rounded px-3 py-2 text-sm" />
        <select name="type" class="border rounded px-3 py-2 text-sm">
            <option value="">نوع سفارش</option>
            <option value="sms" @selected(($filters['type'] ?? '')==='sms')>پکیج پیامک</option>
            <option value="feature" @selected(($filters['type'] ?? '')==='feature')>پکیج امکانات</option>
        </select>
        <select name="status" class="border rounded px-3 py-2 text-sm">
            <option value="">وضعیت سفارش</option>
            <option value="pending" @selected(($filters['status'] ?? '')==='pending')>در انتظار</option>
            <option value="completed" @selected(($filters['status'] ?? '')==='completed')>پرداخت شده</option>
            <option value="failed" @selected(($filters['status'] ?? '')==='failed')>ناموفق</option>
        </select>
        <select name="gateway" class="border rounded px-3 py-2 text-sm">
            <option value="">درگاه</option>
            <option value="zarinpal" @selected(($filters['gateway'] ?? '')==='zarinpal')>زرین‌پال</option>
        </select>
        <select name="period" class="border rounded px-3 py-2 text-sm">
            <option value="daily" @selected(($filters['period'] ?? $period)==='daily')>روزانه</option>
            <option value="weekly" @selected(($filters['period'] ?? $period)==='weekly')>هفتگی</option>
            <option value="monthly" @selected(($filters['period'] ?? $period)==='monthly')>ماهانه</option>
        </select>
        <button class="bg-indigo-600 text-white rounded px-4 py-2 text-sm">فیلتر</button>
    </form>

    <!-- Grouped Breakdown -->
    <div class="bg-white rounded-lg shadow mb-8 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b bg-gray-50">
            <h2 class="font-semibold text-sm">گزارش تجمیعی ({{ $period==='daily' ? '۱۴ روز اخیر' : ($period==='weekly' ? '۸ هفته اخیر' : '۶ ماه اخیر') }})</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs md:text-sm">
                <thead class="bg-gray-100">
                    <tr class="text-right">
                        <th class="px-4 py-2">بازه</th>
                        <th class="px-4 py-2">تعداد سفارش</th>
                        <th class="px-4 py-2">مجموع مبلغ (تومان)</th>
                        <th class="px-4 py-2">مجموع پیامک</th>
                        <th class="px-4 py-2">میانگین مبلغ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groupData as $row)
                        <tr class="border-b last:border-0">
                            <td class="px-4 py-2 font-medium ltr">{{ $row->label }}</td>
                            <td class="px-4 py-2">{{ number_format($row->count) }}</td>
                            <td class="px-4 py-2">{{ number_format($row->total_amount) }}</td>
                            <td class="px-4 py-2">{{ number_format($row->total_sms) }}</td>
                            <td class="px-4 py-2">{{ $row->count ? number_format($row->total_amount / $row->count) : 0 }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-4 text-center text-gray-500">داده‌ای یافت نشد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100">
                <tr class="text-right">
                    <th class="px-4 py-2">سفارش</th>
                    <th class="px-4 py-2">نوع</th>
                    <th class="px-4 py-2">سالن</th>
                    <th class="px-4 py-2">شماره تلفن مالک سالن</th>
                    <th class="px-4 py-2">بسته</th>
                    <th class="px-4 py-2">تعداد پیامک</th>
                    <th class="px-4 py-2">مبلغ (تومان)</th>
                    <th class="px-4 py-2">کد تخفیف</th>
                    <th class="px-4 py-2">درگاه</th>
                    <th class="px-4 py-2">Authority / RefID</th>
                    <th class="px-4 py-2">وضعیت سفارش</th>
                    <th class="px-4 py-2">وضعیت تراکنش</th>
                    <th class="px-4 py-2">تاریخ</th>
                    <th class="px-4 py-2">عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    @php $tx = $order->transactions->first(); @endphp
                    <tr class="border-b last:border-0 hover:bg-gray-50">
                        <td class="px-4 py-2 font-medium">#{{ $order->id }}</td>
                        <td class="px-4 py-2">
                            @if($order->type === 'feature')
                                <span class="px-2 py-1 rounded text-xs bg-purple-100 text-purple-700">پکیج امکانات</span>
                            @else
                                <span class="px-2 py-1 rounded text-xs bg-blue-100 text-blue-700">پکیج پیامک</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            @if($order->salon)
                                <a href="{{ url('admin/salons/'.$order->salon->id) }}" class="text-indigo-600 hover:underline">{{ $order->salon->name }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            @if($order->salon && $order->salon->user)
                                {{ $order->salon->user->mobile ?? '-' }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            @if($order->type === 'feature')
                                {{ $order->package->name ?? '-' }}
                            @else
                                {{ $order->smsPackage->name ?? '-' }}
                            @endif
                        </td>
                        <td class="px-4 py-2">{{ $order->sms_count ? number_format($order->sms_count) : '-' }}</td>
                        <td class="px-4 py-2">{{ number_format($order->amount) }}</td>
                        <td class="px-4 py-2">{{ $order->discount_code ? ($order->discount_code.' ('.$order->discount_percentage.'%)') : '-' }}</td>
                        <td class="px-4 py-2">{{ $tx->gateway ?? '-' }}</td>
                        <td class="px-4 py-2 ltr text-xs">{{ $tx->reference_id ?? $tx->transaction_id ?? $order->payment_ref_id ?? $order->payment_authority ?? '-' }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 rounded text-xs {{ $order->status==='completed' ? 'bg-green-100 text-green-700' : ($order->status==='pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-600') }}">
                                @if($order->status === 'completed')
                                    پرداخت شده
                                @elseif($order->status === 'pending')
                                    در انتظار
                                @else
                                    ناموفق
                                @endif
                            </span>
                        </td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 rounded text-xs {{ ($tx->status ?? '')==='completed' ? 'bg-green-100 text-green-700' : (($tx->status ?? '')==='pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-600') }}">{{ $tx->status ?? '-' }}</span>
                        </td>
                        <td class="px-4 py-2">{{ verta($order->created_at)->format('Y/m/d H:i') }}</td>
                        <td class="px-4 py-2">
                            <div class="flex flex-col gap-2">
                                <!-- تغییر وضعیت سفارش -->
                                <select class="border rounded px-2 py-1 text-xs order-status-select" data-order-id="{{ $order->id }}" data-current-status="{{ $order->status }}">
                                    <option value="pending" @selected($order->status === 'pending')>در انتظار</option>
                                    <option value="completed" @selected($order->status === 'completed')>پرداخت شده</option>
                                    <option value="failed" @selected($order->status === 'failed')>ناموفق</option>
                                </select>
                                
                                @if($tx)
                                <!-- تغییر وضعیت تراکنش -->
                                <select class="border rounded px-2 py-1 text-xs transaction-status-select" data-transaction-id="{{ $tx->id }}" data-current-status="{{ $tx->status }}">
                                    <option value="pending" @selected($tx->status === 'pending')>در انتظار</option>
                                    <option value="completed" @selected($tx->status === 'completed')>تکمیل شده</option>
                                    <option value="failed" @selected($tx->status === 'failed')>ناموفق</option>
                                    <option value="expired" @selected($tx->status === 'expired')>منقضی شده</option>
                                </select>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" class="px-4 py-6 text-center text-gray-500">هیچ تراکنشی یافت نشد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $orders->links() }}</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle order status change
    document.querySelectorAll('.order-status-select').forEach(function(select) {
        select.addEventListener('change', function() {
            const orderId = this.dataset.orderId;
            const currentStatus = this.dataset.currentStatus;
            const newStatus = this.value;
            
            if (newStatus === currentStatus) return;
            
            if (confirm('آیا از تغییر وضعیت سفارش اطمینان دارید؟')) {
                updateOrderStatus(orderId, newStatus, this);
            } else {
                this.value = currentStatus; // Reset to original value
            }
        });
    });
    
    // Handle transaction status change  
    document.querySelectorAll('.transaction-status-select').forEach(function(select) {
        select.addEventListener('change', function() {
            const transactionId = this.dataset.transactionId;
            const currentStatus = this.dataset.currentStatus;
            const newStatus = this.value;
            
            if (newStatus === currentStatus) return;
            
            if (confirm('آیا از تغییر وضعیت تراکنش اطمینان دارید؟')) {
                updateTransactionStatus(transactionId, newStatus, this);
            } else {
                this.value = currentStatus; // Reset to original value
            }
        });
    });
});

function updateOrderStatus(orderId, status, selectElement) {
    fetch(`{{ url('admin/transactions/orders') }}/${orderId}/status`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ status: status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            selectElement.dataset.currentStatus = status;
            
            // Update status badge in the same row
            const row = selectElement.closest('tr');
            const statusCell = row.querySelector('td:nth-child(9) span');
            updateStatusBadge(statusCell, status, 'order');
            
            showMessage(data.message, 'success');
        } else {
            throw new Error(data.message || 'خطا در به‌روزرسانی وضعیت');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        selectElement.value = selectElement.dataset.currentStatus; // Reset
        showMessage('خطا در به‌روزرسانی وضعیت سفارش', 'error');
    });
}

function updateTransactionStatus(transactionId, status, selectElement) {
    fetch(`{{ url('admin/transactions/transactions') }}/${transactionId}/status`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ status: status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            selectElement.dataset.currentStatus = status;
            
            // Update status badge in the same row
            const row = selectElement.closest('tr');
            const statusCell = row.querySelector('td:nth-child(10) span');
            updateStatusBadge(statusCell, status, 'transaction');
            
            showMessage(data.message, 'success');
        } else {
            throw new Error(data.message || 'خطا در به‌روزرسانی وضعیت');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        selectElement.value = selectElement.dataset.currentStatus; // Reset
        showMessage('خطا در به‌روزرسانی وضعیت تراکنش', 'error');
    });
}

function updateStatusBadge(badgeElement, status, type) {
    if (!badgeElement) return; // جلوگیری از خطا اگر المنت پیدا نشد
    // Remove old classes
    badgeElement.className = 'px-2 py-1 rounded text-xs';
    
    if (type === 'order') {
        if (status === 'completed') {
            badgeElement.classList.add('bg-green-100', 'text-green-700');
            badgeElement.textContent = 'پرداخت شده';
        } else if (status === 'pending') {
            badgeElement.classList.add('bg-yellow-100', 'text-yellow-700');
            badgeElement.textContent = 'در انتظار';
        } else {
            badgeElement.classList.add('bg-red-100', 'text-red-600');
            badgeElement.textContent = 'ناموفق';
        }
    } else { // transaction
        if (status === 'completed') {
            badgeElement.classList.add('bg-green-100', 'text-green-700');
            badgeElement.textContent = 'تکمیل شده';
        } else if (status === 'pending') {
            badgeElement.classList.add('bg-yellow-100', 'text-yellow-700');
            badgeElement.textContent = 'در انتظار';
        } else {
            badgeElement.classList.add('bg-red-100', 'text-red-600');
            badgeElement.textContent = status;
        }
    }
}

function showMessage(message, type) {
    // Create toast notification
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white z-50 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.remove();
    }, 3000);
}
</script>
@endsection
