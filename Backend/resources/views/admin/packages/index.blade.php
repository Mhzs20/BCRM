@extends('admin.layouts.app')

@section('title', 'مدیریت پکیج‌ها')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            مدیریت پکیج‌ها
        </h2>
        <a href="{{ route('admin.packages.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-medium text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm transition-all duration-150">
            <i class="ri-add-line ml-2 text-lg"></i>
            افزودن پکیج جدید
        </a>
    </div>
@endsection

@section('content')
    <div class="max-w-7xl mx-auto">
        @if(session('success'))
            <div class="bg-green-50 border-r-4 border-green-500 text-green-700 px-4 py-3 rounded-lg relative mb-4 shadow-sm" role="alert">
                <div class="flex items-center">
                    <i class="ri-checkbox-circle-line text-xl ml-2"></i>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">
                                شناسه
                            </th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">
                                نام پکیج
                            </th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">
                                قیمت
                            </th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">
                                پیامک هدیه
                            </th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">
                                مدت اعتبار
                            </th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">
                                تعداد امکانات
                            </th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">
                                وضعیت
                            </th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">
                                عملیات
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($packages as $package)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #{{ $package->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                                        <i class="ri-inbox-line text-indigo-600 text-xl"></i>
                                        {{ $package->name }}
                                        @if($package->is_gift_only)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 border border-amber-200">
                                                <i class="ri-gift-2-line ml-1"></i>
                                                هدیه
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center text-sm text-gray-900">
                                        <i class="ri-money-dollar-circle-line text-green-600 ml-1"></i>
                                        {{ number_format($package->price) }} تومان
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center text-sm text-gray-900">
                                        <i class="ri-gift-line text-pink-600 ml-1"></i>
                                        {{ number_format($package->gift_sms_count) }} پیامک
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center text-sm text-gray-900">
                                        <i class="ri-calendar-line text-blue-600 ml-1"></i>
                                        {{ number_format($package->duration_days) }} روز
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                        <i class="ri-list-check text-sm ml-1"></i>
                                        {{ $package->options->count() }} امکانات
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $package->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $package->is_active ? '● فعال' : '○ غیرفعال' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2 space-x-reverse">
                                        <a href="{{ route('admin.packages.show', $package) }}" 
                                           class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors duration-150"
                                           title="مشاهده جزئیات">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('admin.packages.edit', $package) }}" 
                                           class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 transition-colors duration-150"
                                           title="ویرایش">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                        <form action="{{ route('admin.packages.destroy', $package) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 transition-colors duration-150" 
                                                    onclick="return confirm('آیا از حذف این پکیج اطمینان دارید؟')"
                                                    title="حذف">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <i class="ri-inbox-line text-6xl text-gray-300 mb-4"></i>
                                        <p class="text-gray-500 text-lg">هیچ پکیجی یافت نشد.</p>
                                        <a href="{{ route('admin.packages.create') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-150">
                                            <i class="ri-add-line ml-2"></i>
                                            ایجاد اولین پکیج
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($packages->hasPages())
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    {{ $packages->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
