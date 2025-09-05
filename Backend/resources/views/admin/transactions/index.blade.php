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

    <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6 bg-white p-4 rounded-lg shadow">
        <input name="salon" value="{{ $filters['salon'] ?? '' }}" placeholder="نام سالن" class="border rounded px-3 py-2 text-sm" />
        <input name="ref" value="{{ $filters['ref'] ?? '' }}" placeholder="کد مرجع/Authority" class="border rounded px-3 py-2 text-sm" />
        <select name="status" class="border rounded px-3 py-2 text-sm">
            <option value="">وضعیت سفارش</option>
            <option value="pending" @selected(($filters['status'] ?? '')==='pending')>در انتظار</option>
            <option value="paid" @selected(($filters['status'] ?? '')==='paid')>پرداخت شده</option>
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
                    <th class="px-4 py-2">سالن</th>
                    <th class="px-4 py-2">بسته</th>
                    <th class="px-4 py-2">تعداد پیامک</th>
                    <th class="px-4 py-2">مبلغ (تومان)</th>
                    <th class="px-4 py-2">کد تخفیف</th>
                    <th class="px-4 py-2">درگاه</th>
                    <th class="px-4 py-2">Authority / RefID</th>
                    <th class="px-4 py-2">وضعیت سفارش</th>
                    <th class="px-4 py-2">وضعیت تراکنش</th>
                    <th class="px-4 py-2">تاریخ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    @php $tx = $order->transactions->first(); @endphp
                    <tr class="border-b last:border-0 hover:bg-gray-50">
                        <td class="px-4 py-2 font-medium">#{{ $order->id }}</td>
                        <td class="px-4 py-2">{{ $order->salon->name ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $order->smsPackage->name ?? '-' }}</td>
                        <td class="px-4 py-2">{{ number_format($order->sms_count) }}</td>
                        <td class="px-4 py-2">{{ number_format($order->amount) }}</td>
                        <td class="px-4 py-2">{{ $order->discount_code ? ($order->discount_code.' ('.$order->discount_percentage.'%)') : '-' }}</td>
                        <td class="px-4 py-2">{{ $tx->gateway ?? '-' }}</td>
                        <td class="px-4 py-2 ltr text-xs">{{ $tx->reference_id ?? $tx->transaction_id ?? '-' }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 rounded text-xs {{ $order->status==='paid' ? 'bg-green-100 text-green-700' : ($order->status==='pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-600') }}">{{ $order->status }}</span>
                        </td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 rounded text-xs {{ ($tx->status ?? '')==='completed' ? 'bg-green-100 text-green-700' : (($tx->status ?? '')==='pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-600') }}">{{ $tx->status ?? '-' }}</span>
                        </td>
                        <td class="px-4 py-2">{{ verta($order->created_at)->format('Y/m/d H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-4 py-6 text-center text-gray-500">هیچ تراکنشی یافت نشد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $orders->links() }}</div>
</div>
@endsection
