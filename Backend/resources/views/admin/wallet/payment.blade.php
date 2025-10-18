@extends('admin.layouts.app')

@section('title', 'پرداخت شارژ کیف پول')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="p-6">
        <div class="max-w-md mx-auto mt-20">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-6 text-center">پرداخت شارژ کیف پول</h1>
                
                <div class="mb-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h2 class="font-semibold text-blue-900 mb-2">جزئیات شارژ</h2>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">کاربر:</span>
                                <span class="font-medium">{{ $order->user->name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">موبایل:</span>
                                <span class="font-medium">{{ $order->user->mobile }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">مبلغ شارژ:</span>
                                <span class="font-bold text-green-600">{{ number_format($order->amount) }} ریال</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">کد سفارش:</span>
                                <span class="font-medium">#{{ $order->id }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="mb-6">
                    <h3 class="font-semibold text-gray-900 mb-3">روش پرداخت</h3>
                    <div class="space-y-3">
                        <!-- ZarinPal Option -->
                        <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="payment_method" value="zarinpal" checked class="form-radio text-blue-600">
                            <div class="mr-3">
                                <div class="font-medium">درگاه زرین‌پال</div>
                                <div class="text-sm text-gray-500">پرداخت آنلاین با کلیه کارت‌های بانکی</div>
                            </div>
                            <div class="mr-auto">
                                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==" 
                                     alt="ZarinPal" class="w-8 h-8 bg-green-500 rounded">
                            </div>
                        </label>
                        
                        <!-- Demo Payment Option (for testing) -->
                        <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="payment_method" value="demo" class="form-radio text-blue-600">
                            <div class="mr-3">
                                <div class="font-medium">پرداخت آزمایشی</div>
                                <div class="text-sm text-gray-500">برای تست سیستم (بدون پرداخت واقعی)</div>
                            </div>
                            <div class="mr-auto">
                                <div class="w-8 h-8 bg-orange-500 rounded flex items-center justify-center">
                                    <span class="text-white text-xs font-bold">TEST</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Action Buttons -->
                <form action="{{ route('admin.wallet.charge.process', $order->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="payment_method" id="selectedPaymentMethod" value="zarinpal">
                    
                    <div class="flex space-x-3 space-x-reverse">
                        <a href="{{ route('admin.wallet.charge') }}" 
                           class="flex-1 bg-gray-300 text-gray-700 py-3 px-4 rounded-lg text-center hover:bg-gray-400 transition-colors">
                            انصراف
                        </a>
                        <button type="submit" 
                                class="flex-1 bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 transition-colors">
                            پرداخت
                        </button>
                    </div>
                </form>

                <!-- Security Notice -->
                <div class="mt-6 text-center">
                    <p class="text-xs text-gray-500">
                        🔒 تمامی اطلاعات شما با پروتکل‌های امنیتی محافظت می‌شود
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Update selected payment method
document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('selectedPaymentMethod').value = this.value;
    });
});

// Show loading on form submit
document.querySelector('form').addEventListener('submit', function() {
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = 'در حال پردازش...';
});
</script>
@endsection