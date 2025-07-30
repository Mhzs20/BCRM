@csrf
<div class="space-y-4">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700">عنوان پکیج</label>
        <input type="text" name="name" id="name" value="{{ old('name', $smsPackage->name ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
    </div>
    <div>
        <label for="sms_count" class="block text-sm font-medium text-gray-700">تعداد پیامک</label>
        <input type="number" name="sms_count" id="sms_count" value="{{ old('sms_count', $smsPackage->sms_count ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
    </div>
    <div>
        <label for="price" class="block text-sm font-medium text-gray-700">قیمت (تومان)</label>
        <input type="number" name="price" id="price" value="{{ old('price', $smsPackage->price ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
    </div>
    <div>
        <label for="purchase_link" class="block text-sm font-medium text-gray-700">لینک خرید</label>
        <input type="text" name="purchase_link" id="purchase_link" value="{{ old('purchase_link', $smsPackage->purchase_link ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
    </div>
    <div class="flex items-center">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" id="is_active" value="1" @if(old('is_active', $smsPackage->is_active ?? false)) checked @endif class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
        <label for="is_active" class="ml-2 block text-sm text-gray-900">فعال</label>
    </div>
</div>
<div class="mt-6">
    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">ذخیره</button>
    <a href="{{ route('admin.sms-packages.index') }}" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">انصراف</a>
</div>
