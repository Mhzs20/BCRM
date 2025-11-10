@extends('admin.layouts.app')

@section('title', 'افزودن پکیج جدید')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            افزودن پکیج جدید
        </h2>
        <a href="{{ route('admin.packages.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-150">
            <i class="ri-arrow-right-line ml-2"></i>
            بازگشت
        </a>
    </div>
@endsection

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <form action="{{ route('admin.packages.store') }}" method="POST">
                @csrf

                <div class="p-6 space-y-6">
                    <!-- Package Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="ri-edit-line text-indigo-600"></i>
                            نام پکیج *
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-150"
                               placeholder="مثال: پکیج پرو"
                               required>
                        @error('name')
                            <p class="mt-2 text-sm text-red-600"><i class="ri-error-warning-line"></i> {{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="ri-file-text-line text-indigo-600"></i>
                            توضیحات
                        </label>
                        <textarea name="description" id="description" rows="4"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-150"
                                  placeholder="توضیحات کامل درباره پکیج...">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-2 text-sm text-red-600"><i class="ri-error-warning-line"></i> {{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Price -->
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="ri-money-dollar-circle-line text-indigo-600"></i>
                                قیمت (تومان) *
                            </label>
                            <input type="number" name="price" id="price" value="{{ old('price') }}" min="0" step="1000"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-150"
                                   placeholder="25000000"
                                   required>
                            @error('price')
                                <p class="mt-2 text-sm text-red-600"><i class="ri-error-warning-line"></i> {{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Gift SMS Count -->
                        <div>
                            <label for="gift_sms_count" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="ri-gift-line text-indigo-600"></i>
                                تعداد پیامک هدیه *
                            </label>
                            <input type="number" name="gift_sms_count" id="gift_sms_count" value="{{ old('gift_sms_count', 0) }}" min="0" step="100"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-150"
                                   placeholder="1000"
                                   required>
                            @error('gift_sms_count')
                                <p class="mt-2 text-sm text-red-600"><i class="ri-error-warning-line"></i> {{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Duration Days -->
                        <div>
                            <label for="duration_days" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="ri-calendar-line text-indigo-600"></i>
                                مدت اعتبار (روز) *
                            </label>
                            <input type="number" name="duration_days" id="duration_days" value="{{ old('duration_days', 365) }}" min="1" max="3650"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-150"
                                   placeholder="365"
                                   required>
                            @error('duration_days')
                                <p class="mt-2 text-sm text-red-600"><i class="ri-error-warning-line"></i> {{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">پیش‌فرض: 365 روز (1 سال)</p>
                        </div>
                    </div>

                    <!-- Options Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            <i class="ri-list-check-2 text-indigo-600"></i>
                            انتخاب امکانات پکیج
                        </label>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 max-h-96 overflow-y-auto space-y-3">
                            @forelse($options->where('is_active', true) as $option)
                                <div class="bg-white border border-gray-200 rounded-lg p-4 hover:border-indigo-300 hover:shadow-sm transition-all duration-150">
                                    <label class="flex items-start cursor-pointer">
                                        <input type="checkbox" name="options[]" value="{{ $option->id }}" 
                                               {{ in_array($option->id, old('options', [])) ? 'checked' : '' }}
                                               class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-5 h-5">
                                        <div class="mr-3 flex-1">
                                            <span class="text-base font-medium text-gray-900">{{ $option->name }}</span>
                                            @if($option->details)
                                                <p class="text-sm text-gray-600 mt-1">{{ $option->details }}</p>
                                            @endif
                                        </div>
                                    </label>
                                </div>
                            @empty
                                <div class="text-center py-8">
                                    <i class="ri-inbox-line text-4xl text-gray-300"></i>
                                    <p class="text-sm text-gray-500 mt-2">هیچ آپشن فعالی یافت نشد.</p>
                                </div>
                            @endforelse
                        </div>
                        @error('options')
                            <p class="mt-2 text-sm text-red-600"><i class="ri-error-warning-line"></i> {{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Active Status -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-4">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-5 h-5">
                            <span class="mr-3">
                                <span class="text-base font-medium text-gray-900">فعال‌سازی پکیج</span>
                                <p class="text-sm text-gray-600">پکیج بلافاصله برای کاربران قابل مشاهده خواهد بود</p>
                            </span>
                        </label>
                        
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="is_gift_only" value="1" {{ old('is_gift_only', false) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-amber-600 shadow-sm focus:border-amber-300 focus:ring focus:ring-amber-200 focus:ring-opacity-50 w-5 h-5">
                            <span class="mr-3">
                                <span class="text-base font-medium text-gray-900">
                                    <i class="ri-gift-2-line text-amber-600"></i>
                                    پکیج ویژه هدیه
                                </span>
                                <p class="text-sm text-gray-600">این پکیج فقط از پنل ادمین قابل فعال‌سازی است و در لیست عمومی پکیج‌ها نمایش داده نمی‌شود</p>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 space-x-reverse">
                    <a href="{{ route('admin.packages.index') }}" 
                       class="inline-flex items-center px-6 py-3 bg-white border border-gray-300 rounded-lg font-medium text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-150">
                        <i class="ri-close-line ml-2"></i>
                        لغو
                    </a>
                    <button type="submit" 
                            class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-lg font-medium text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm transition-all duration-150">
                        <i class="ri-save-line ml-2"></i>
                        ذخیره پکیج
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
