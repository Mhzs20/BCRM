@extends('admin.layouts.app')

@section('title', 'سوابق خرید بسته پیامک سالن: ' . $salon->name)

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-extrabold text-gray-900 mb-8 text-center">سوابق خرید بسته پیامک سالن: {{ $salon->name }}</h1>

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

    <div class="bg-white shadow-xl rounded-lg overflow-hidden mt-8">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        شماره تراکنش
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        بسته پیامک
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        مبلغ
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        وضعیت
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        تاریخ خرید
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($purchaseTransactions as $transaction)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $transaction->transaction_id ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $transaction->smsPackage->name ?? 'بسته نامشخص' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ number_format($transaction->amount) }} ریال
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $transaction->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $transaction->status === 'completed' ? 'موفق' : 'ناموفق' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ verta($transaction->created_at)->format('Y/m/d H:i') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                            هیچ سابقه خریدی یافت نشد.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">
            {{ $purchaseTransactions->links() }}
        </div>
    </div>

    <div class="mt-6 text-center">
        <a href="{{ route('admin.salons.show', $salon->id) }}" class="btn-secondary">
            <i class="ri-arrow-right-line ml-2"></i> بازگشت به پروفایل سالن
        </a>
    </div>
</div>
@endsection
