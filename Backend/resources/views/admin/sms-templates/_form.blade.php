@csrf
<div class="space-y-4">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700">عنوان قالب</label>
        <input type="text" name="name" id="name" value="{{ old('name', $smsTemplate->name ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
    </div>
    <div>
        <label for="content" class="block text-sm font-medium text-gray-700">محتوای قالب</label>
        <textarea name="content" id="content" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>{{ old('content', $smsTemplate->content ?? '') }}</textarea>
        <p class="mt-2 text-sm text-gray-500">متغیرهای قابل استفاده: {customer_name}, {salon_name}, {appointment_date}, {appointment_time}</p>
    </div>
</div>
    <div class="mt-6 flex justify-end">
        <a href="{{ route('admin.sms-templates.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md mr-2 hover:bg-gray-300">انصراف</a>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">ذخیره</button>
    </div>
</div>
