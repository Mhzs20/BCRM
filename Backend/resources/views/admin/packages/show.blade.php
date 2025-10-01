@extends('admin.layouts.app')

@section('title', 'جزئیات پکیج')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            جزئیات پکیج: {{ $package->name }}
        </h2>
        <div class="flex space-x-2 space-x-reverse">
            <a href="{{ route('admin.packages.edit', $package) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-medium text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm transition-all duration-150">
                <i class="ri-edit-line ml-2"></i>
                ویرایش پکیج
            </a>
            <a href="{{ route('admin.packages.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-150">
                <i class="ri-arrow-right-line ml-2"></i>
                بازگشت
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="max-w-5xl mx-auto">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <!-- Package Header -->
            <div class="bg-gradient-to-l from-indigo-600 to-indigo-800 px-6 py-8 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-bold mb-2">{{ $package->name }}</h3>
                        <p class="text-indigo-100">{{ $package->description ?? 'بدون توضیحات' }}</p>
                    </div>
                    <div class="text-left">
                        <span class="px-4 py-2 inline-flex text-sm leading-5 font-semibold rounded-full 
                            {{ $package->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $package->is_active ? '● فعال' : '○ غیرفعال' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Package Info Cards -->
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <!-- Price Card -->
                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-6 border border-green-200">
                        <div class="flex items-center justify-between mb-3">
                            <i class="ri-money-dollar-circle-line text-3xl text-green-600"></i>
                            <span class="text-xs font-medium text-green-700 bg-green-200 px-2 py-1 rounded">قیمت</span>
                        </div>
                        <p class="text-2xl font-bold text-green-900">{{ number_format($package->price) }}</p>
                        <p class="text-sm text-green-700 mt-1">تومان</p>
                    </div>

                    <!-- Gift SMS Card -->
                    <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg p-6 border border-pink-200">
                        <div class="flex items-center justify-between mb-3">
                            <i class="ri-gift-line text-3xl text-pink-600"></i>
                            <span class="text-xs font-medium text-pink-700 bg-pink-200 px-2 py-1 rounded">پیامک هدیه</span>
                        </div>
                        <p class="text-2xl font-bold text-pink-900">{{ number_format($package->gift_sms_count) }}</p>
                        <p class="text-sm text-pink-700 mt-1">پیامک رایگان</p>
                    </div>

                    <!-- Duration Card -->
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-6 border border-blue-200">
                        <div class="flex items-center justify-between mb-3">
                            <i class="ri-calendar-line text-3xl text-blue-600"></i>
                            <span class="text-xs font-medium text-blue-700 bg-blue-200 px-2 py-1 rounded">مدت اعتبار</span>
                        </div>
                        <p class="text-2xl font-bold text-blue-900">{{ number_format($package->duration_days) }}</p>
                        <p class="text-sm text-blue-700 mt-1">روز</p>
                    </div>

                    <!-- Options Count Card -->
                    <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-lg p-6 border border-indigo-200">
                        <div class="flex items-center justify-between mb-3">
                            <i class="ri-list-check-2 text-3xl text-indigo-600"></i>
                            <span class="text-xs font-medium text-indigo-700 bg-indigo-200 px-2 py-1 rounded">امکانات</span>
                        </div>
                        <p class="text-2xl font-bold text-indigo-900">{{ $package->options->count() }}</p>
                        <p class="text-sm text-indigo-700 mt-1">امکانات فعال</p>
                    </div>
                </div>

                <!-- Package Options -->
                <div class="mb-6">
                    <div class="flex items-center mb-4">
                        <i class="ri-star-line text-2xl text-yellow-500 ml-2"></i>
                        <h3 class="text-xl font-semibold text-gray-900">امکانات و ویژگی‌های پکیج</h3>
                    </div>
                    
                    @if($package->options->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($package->options as $option)
                                <div class="bg-gradient-to-l from-gray-50 to-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow duration-150">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                                <i class="ri-checkbox-circle-line text-indigo-600 text-xl"></i>
                                            </div>
                                        </div>
                                        <div class="mr-4 flex-1">
                                            <h4 class="font-semibold text-gray-900 mb-1">{{ $option->name }}</h4>
                                            @if($option->details)
                                                <p class="text-sm text-gray-600">{{ $option->details }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12 bg-gray-50 rounded-lg border border-gray-200">
                            <i class="ri-inbox-line text-6xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">این پکیج هیچ امکاناتی ندارد.</p>
                        </div>
                    @endif
                </div>

                <!-- Additional Info -->
                <div class="border-t border-gray-200 pt-6">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500 mb-1">تاریخ ایجاد</p>
                            <p class="font-medium text-gray-900">
                                <i class="ri-calendar-line text-gray-400 ml-1"></i>
                                {{ \Hekmatinasser\Verta\Verta::instance($package->created_at)->format('Y/m/d H:i') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-500 mb-1">آخرین به‌روزرسانی</p>
                            <p class="font-medium text-gray-900">
                                <i class="ri-time-line text-gray-400 ml-1"></i>
                                {{ \Hekmatinasser\Verta\Verta::instance($package->updated_at)->format('Y/m/d H:i') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
