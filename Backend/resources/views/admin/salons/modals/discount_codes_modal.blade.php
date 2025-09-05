{{-- Discount Codes Modal --}}
<div id="discountCodesModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">کدهای تخفیف فعال برای سالن: {{ $salon->name }}</h3>
                <button type="button" onclick="closeModal('discountCodesModal')" class="text-gray-400 hover:text-gray-600">
                    <i class="ri-close-line text-2xl"></i>
                </button>
            </div>

            {{-- Loading State --}}
            <div id="discountCodesLoading" class="hidden text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                <p class="mt-2 text-gray-600">در حال بارگذاری کدهای تخفیف...</p>
            </div>

            {{-- Content --}}
            <div id="discountCodesContent" class="hidden">
                <div class="mb-4 p-4 bg-indigo-50 rounded-lg">
                    <p class="text-sm text-indigo-800">
                        <i class="ri-information-line ml-1"></i>
                        تعداد کل کدهای تخفیف فعال برای این سالن: <span id="totalDiscountCodes" class="font-bold">0</span>
                    </p>
                </div>

                {{-- Summary Statistics --}}
                <div id="discountCodesSummary" class="mb-6">
                    <!-- Summary will be loaded here via JavaScript -->
                </div>

                <div id="discountCodesList" class="space-y-4 max-h-96 overflow-y-auto">
                    <!-- Discount codes will be loaded here via JavaScript -->
                </div>

                <div class="mt-6 flex justify-between items-center">
                    <a href="{{ route('admin.discount-codes.index') }}" class="bg-indigo-500 hover:bg-indigo-600 text-white font-bold py-2 px-4 rounded">
                        <i class="ri-settings-line ml-1"></i>
                        مدیریت کدهای تخفیف
                    </a>
                    <button type="button" onclick="closeModal('discountCodesModal')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        بستن
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
