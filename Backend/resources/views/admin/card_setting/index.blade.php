@extends('admin.layouts.app')

@section('content')
<div class="max-w-xl mx-auto mt-8">
    <h2 class="mb-6 text-2xl font-bold text-indigo-700 text-center">تنظیمات کارت</h2>
    @if(session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800 border border-green-300 text-center">{{ session('success') }}</div>
    @endif
    <form method="POST" action="{{ route('card-setting.store') }}" class="bg-white shadow-lg rounded-lg p-6 space-y-6">
        @csrf
        <div class="flex items-center justify-between">
            <label for="is_active" class="font-medium text-gray-700">وضعیت نمایش کارت</label>
            <label class="inline-block relative w-12 h-7 cursor-pointer">
                <input type="checkbox" id="is_active" name="is_active" value="1" class="sr-only peer" {{ (isset($cardSetting) && $cardSetting->is_active) ? 'checked' : '' }}>
                <div class="w-12 h-7 bg-gray-300 rounded-full transition-all peer-checked:bg-indigo-600"></div>
                <span class="absolute left-1 top-1 w-5 h-5 rounded-full bg-white shadow transition-all duration-300 peer-checked:translate-x-5"></span>
            </label>
        </div>
        <div>
            <label for="card_number" class="block mb-2 text-sm font-medium text-gray-700">شماره کارت</label>
            <input type="text" class="w-full px-4 py-2 rounded border border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 bg-white text-gray-900" id="card_number" name="card_number" value="{{ $cardSetting->card_number ?? '' }}" placeholder="مثال: 6037-9912-1234-5678">
        </div>
        <div>
            <label for="card_holder_name" class="block mb-2 text-sm font-medium text-gray-700">نام صاحب کارت</label>
            <input type="text" class="w-full px-4 py-2 rounded border border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 bg-white text-gray-900" id="card_holder_name" name="card_holder_name" value="{{ $cardSetting->card_holder_name ?? '' }}" placeholder="مثال: علی رضایی">
        </div>
        <div>
            <label for="description" class="block mb-2 text-sm font-medium text-gray-700">توضیحات</label>
            <textarea class="w-full px-4 py-2 rounded border border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 bg-white text-gray-900" id="description" name="description" rows="3" placeholder="توضیحات کارت ...">{{ $cardSetting->description ?? '' }}</textarea>
        </div>
        <div class="flex justify-center">
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700 transition font-bold">ثبت کارت جدید</button>
        </div>
    </form>

    <div class="mt-10">
        <h3 class="mb-4 text-lg font-semibold text-gray-800 text-center">لیست کارت‌ها</h3>
        <table class="w-full bg-white rounded shadow text-center">
            <thead>
                <tr class="bg-gray-100">
                    <th class="py-2">شماره کارت</th>
                    <th class="py-2">نام صاحب کارت</th>
                    <th class="py-2">وضعیت</th>
                    <th class="py-2">عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cardSettings as $card)
                <tr class="border-b">
                    <td class="py-2">{{ $card->card_number }}</td>
                    <td class="py-2">{{ $card->card_holder_name }}</td>
                    <td class="py-2">
                        @if($card->is_active)
                            <span class="px-2 py-1 rounded bg-green-100 text-green-700 text-xs">فعال</span>
                        @else
                            <span class="px-2 py-1 rounded bg-red-100 text-red-700 text-xs">غیرفعال</span>
                        @endif
                    </td>
                    <td class="py-2 flex justify-center gap-2">
                        <a href="{{ route('card-setting.edit', $card->id) }}" class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-xs">ویرایش</a>
                        <form method="POST" action="{{ route('card-setting.destroy', $card->id) }}" onsubmit="return confirm('آیا مطمئن هستید؟');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-xs">حذف</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="py-4 text-gray-400">هیچ کارتی ثبت نشده است.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </form>
</div>
@endsection
