@extends('admin.layouts.app')

@section('content')
<div class="max-w-xl mx-auto mt-8">
    <h2 class="mb-6 text-2xl font-bold text-indigo-700 text-center">ویرایش تنظیمات کارت</h2>
    <form method="POST" action="{{ route('card-setting.update', $cardSetting->id) }}" class="bg-white shadow-lg rounded-lg p-6 space-y-6">
        @csrf
        @method('PUT')
        <div class="flex items-center justify-between">
            <label for="is_active" class="font-medium text-gray-700">وضعیت نمایش کارت</label>
            <label class="inline-block relative w-12 h-7 cursor-pointer">
                <input type="checkbox" id="is_active" name="is_active" value="1" class="sr-only peer" {{ ($cardSetting->is_active) ? 'checked' : '' }}>
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
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700 transition font-bold">ذخیره تغییرات</button>
        </div>
    </form>
</div>
@endsection
