@csrf
<div class="space-y-5">
    <div>
        <label class="block text-sm font-medium mb-1" for="title">عنوان</label>
        <input id="title" name="title" type="text" value="{{ old('title', $smsTemplate->title ?? '') }}" required class="w-full border-gray-300 rounded" />
    </div>
    <div>
        <label class="block text-sm font-medium mb-1" for="category_id">دسته</label>
        <select id="category_id" name="category_id" class="w-full border-gray-300 rounded">
            <option value="">-- بدون دسته --</option>
            @foreach(($categories ?? \App\Models\SmsTemplateCategory::whereNull('salon_id')->orderBy('name')->get()) as $c)
                <option value="{{ $c->id }}" {{ (string)old('category_id', $smsTemplate->category_id ?? '') === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium mb-1" for="template">متن قالب</label>
        <textarea id="template" name="template" rows="6" required class="w-full border-gray-300 rounded">{{ old('template', $smsTemplate->template ?? '') }}</textarea>
        <p class="mt-2 text-xs text-gray-500 leading-5">
            Placeholder های پرکاربرد:
            <code class="bg-gray-100 px-1 rounded text-[10px] font-mono">@{{customer_name}}</code>
            <code class="bg-gray-100 px-1 rounded text-[10px] font-mono">@{{salon_name}}</code>
            <code class="bg-gray-100 px-1 rounded text-[10px] font-mono">@{{appointment_date}}</code>
            <code class="bg-gray-100 px-1 rounded text-[10px] font-mono">@{{appointment_time}}</code>
            <code class="bg-gray-100 px-1 rounded text-[10px] font-mono">@{{details_url}}</code>
            <code class="bg-gray-100 px-1 rounded text-[10px] font-mono">@{{survey_url}}</code>
            <br>
            فرم کوتاه <code class="bg-gray-100 px-1 rounded text-[10px] font-mono">{customer_name}</code> هم معادل است.
            برای نمایش لینک حتماً placeholder مربوطه را قرار دهید؛ در غیر این صورت لینک انتهای پیام ضمیمه می‌شود.
        </p>
    </div>
    <div class="flex items-center space-x-2 space-x-reverse">
        <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $smsTemplate->is_active ?? true) ? 'checked' : '' }} class="rounded border-gray-300" />
        <label for="is_active" class="text-sm">فعال باشد</label>
    </div>
    <div class="flex justify-end space-x-2 space-x-reverse">
        <a href="{{ route('admin.sms-templates.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded">انصراف</a>
        <button class="px-4 py-2 bg-indigo-600 text-white rounded">ذخیره</button>
    </div>
</div>
