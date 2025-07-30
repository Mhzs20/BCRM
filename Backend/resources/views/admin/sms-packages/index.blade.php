@extends('admin.layouts.app')

@section('title', 'مدیریت پکیج‌های SMS')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            مدیریت پکیج‌های SMS
        </h2>
        <a href="{{ route('admin.sms-packages.create') }}" class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600">ایجاد پکیج جدید</a>
    </div>
@endsection

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عنوان</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تعداد پیامک</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">قیمت (تومان)</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">عملیات</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($smsPackages as $package)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $package->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ number_format($package->sms_count) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ number_format($package->price) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($package->is_active)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">فعال</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">غیرفعال</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('admin.sms-packages.edit', $package) }}" class="text-indigo-600 hover:text-indigo-900">ویرایش</a>
                                        <form action="{{ route('admin.sms-packages.destroy', $package) }}" method="POST" class="inline-block ml-2" onsubmit="return confirm('آیا از حذف این پکیج مطمئن هستید؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">حذف</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
