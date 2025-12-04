@csrf
<div class="bg-gray-50 p-6 rounded-lg">
    <h3 class="text-lg font-medium text-gray-900 mb-4">اطلاعات بسته پیامک</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Package Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">عنوان پکیج</label>
            <input type="text" name="name" id="name" value="{{ old('name', $smsPackage->name ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            <p class="mt-2 text-sm text-gray-500">یک نام معنادار برای بسته انتخاب کنید (مثال: بسته برنزی).</p>
        </div>

        <!-- SMS Count -->
        <div>
            <label for="sms_count" class="block text-sm font-medium text-gray-700">تعداد پیامک</label>
            <input type="number" name="sms_count" id="sms_count" value="{{ old('sms_count', $smsPackage->sms_count ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            <p class="mt-2 text-sm text-gray-500">تعداد پیامک موجود در این بسته.</p>
        </div>

        <!-- Price -->
        <div>
            <label for="price" class="block text-sm font-medium text-gray-700">قیمت اصلی (تومان)</label>
            <input type="number" name="price" id="price" value="{{ old('price', $smsPackage->price ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required step="1">
            <p class="mt-2 text-sm text-gray-500">قیمت اصلی بسته بدون تخفیف.</p>
        </div>

        <!-- Discount Percentage -->
        <div>
            <label for="discount_percentage" class="block text-sm font-medium text-gray-700">درصد تخفیف (%)</label>
            <input type="number" name="discount_percentage" id="discount_percentage" value="{{ old('discount_percentage', $smsPackage->discount_percentage ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" step="0.01" min="0" max="100">
            <p class="mt-2 text-sm text-gray-500">اختیاری. درصد تخفیف (0 تا 100). قیمت با تخفیف به صورت خودکار محاسبه می‌شود.</p>
        </div>

        <!-- Discount Price (Read-only, calculated) -->
        <div>
            <label for="discount_price" class="block text-sm font-medium text-gray-700">قیمت با تخفیف (تومان)</label>
            <input type="number" name="discount_price" id="discount_price" value="{{ old('discount_price', $smsPackage->discount_price ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-gray-100" step="1" readonly>
            <p class="mt-2 text-sm text-gray-500">این مقدار به صورت خودکار بر اساس درصد تخفیف محاسبه می‌شود.</p>
        </div>
    </div>

    <!-- Is Active Checkbox -->
    <div class="mt-6 border-t border-gray-200 pt-6">
        <div class="relative flex items-start">
            <div class="flex items-center h-5">
                <input type="hidden" name="is_active" value="0">
                <input id="is_active" name="is_active" type="checkbox" value="1" @if(old('is_active', $smsPackage->is_active ?? false)) checked @endif class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
            </div>
            <div class="ml-3 text-sm">
                <label for="is_active" class="font-medium text-gray-700">فعال‌سازی بسته</label>
                <p class="text-gray-500">در صورت فعال بودن، این بسته برای خرید در دسترس خواهد بود.</p>
            </div>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="mt-8 flex justify-between">
    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">ذخیره</button>
    <a href="{{ route('admin.sms-packages.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md mr-2 hover:bg-gray-300">انصراف</a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const priceInput = document.getElementById('price');
    const discountPercentageInput = document.getElementById('discount_percentage');
    const discountPriceInput = document.getElementById('discount_price');

    function calculateDiscountPrice() {
        const price = parseFloat(priceInput.value) || 0;
        const discountPercentage = parseFloat(discountPercentageInput.value) || 0;

        if (price > 0 && discountPercentage > 0 && discountPercentage <= 100) {
            const discountPrice = Math.round(price - (price * discountPercentage / 100));
            discountPriceInput.value = discountPrice;
        } else {
            discountPriceInput.value = '';
        }
    }

    // محاسبه خودکار هنگام تغییر قیمت یا درصد تخفیف
    priceInput.addEventListener('input', calculateDiscountPrice);
    discountPercentageInput.addEventListener('input', calculateDiscountPrice);

    // محاسبه اولیه در صورت وجود مقادیر
    if (priceInput.value && discountPercentageInput.value) {
        calculateDiscountPrice();
    }
});
</script>
