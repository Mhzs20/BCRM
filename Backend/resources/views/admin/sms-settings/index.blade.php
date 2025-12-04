@extends('admin.layouts.app')

@section('title', 'تنظیمات پیامک')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            تنظیمات پیامک
        </h2>
    </div>
@endsection

@section('content')
    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-8 border-b border-gray-200">
                    <h3 class="text-2xl font-bold text-gray-800 mb-6">تنظیمات هزینه و کاراکتر پیامک</h3>

                    @if (session('success'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md" role="alert">
                            <p class="font-bold">موفقیت</p>
                            <p>{{ session('success') }}</p>
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert">
                            <p class="font-bold">خطا</p>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li class="list-disc ml-5">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.sms_settings.update') }}" class="space-y-6">
                        @csrf
                        
                        <div>
                            <label for="sms_sender_number" class="block text-md font-medium text-gray-700">شماره ارسال پیامک (Sender Number)</label>
                            <div class="mt-2">
                                <input type="text" name="sms_sender_number" id="sms_sender_number"
                                       class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                       value="{{ old('sms_sender_number', $smsSenderNumber->value ?? '09982001323') }}" required
                                       placeholder="مثال: 09982001323">
                            </div>
                            <p class="mt-1 text-sm text-gray-500">شماره ارسال کننده پیامک‌ها که از طرف کاوه‌نگار دریافت کرده‌اید</p>
                        </div>

                        <div>
                            <label for="sms_cost_per_part" class="block text-md font-medium text-gray-700">هزینه هر پارت پیامک (تومان)</label>
                            <div class="mt-2">
                                <input type="number" step="0.01" name="sms_cost_per_part" id="sms_cost_per_part"
                                       class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                       value="{{ old('sms_cost_per_part', $smsCostPerPart->value ?? '') }}" required>
                            </div>
                        </div>

                        <div>
                            <label for="sms_purchase_price_per_part" class="block text-md font-medium text-gray-700">قیمت خرید هر پارت پیامک (تومان)</label>
                            <div class="mt-2">
                                <input type="number" step="0.01" name="sms_purchase_price_per_part" id="sms_purchase_price_per_part"
                                       class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                       value="{{ old('sms_purchase_price_per_part', $smsPurchasePricePerPart->value ?? '') }}" required>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="sms_part_char_limit_fa" class="block text-md font-medium text-gray-700">کاراکتر هر پارت (فارسی)</label>
                                <div class="mt-2">
                                    <input type="number" name="sms_part_char_limit_fa" id="sms_part_char_limit_fa"
                                           class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                           value="{{ old('sms_part_char_limit_fa', $smsCharacterLimitFa->value ?? '70') }}" required>
                                </div>
                            </div>

                            <div>
                                <label for="sms_part_char_limit_en" class="block text-md font-medium text-gray-700">کاراکتر هر پارت (انگلیسی)</label>
                                <div class="mt-2">
                                    <input type="number" name="sms_part_char_limit_en" id="sms_part_char_limit_en"
                                           class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                           value="{{ old('sms_part_char_limit_en', $smsCharacterLimitEn->value ?? '160') }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button type="submit" class="inline-flex justify-center py-3 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                ذخیره تنظیمات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
