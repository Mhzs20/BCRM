@extends('admin.layouts.app')

@section('title', 'مدیریت قالب‌های SMS')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            مدیریت قالب‌های SMS
        </h2>
        <a href="{{ route('admin.sms-templates.create') }}" class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600">ایجاد قالب جدید</a>
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
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">محتوا</th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">عملیات</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($smsTemplates as $template)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $template->name }}</td>
                                    <td class="px-6 py-4">{{ $template->content }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('admin.sms-templates.edit', $template) }}" class="text-indigo-600 hover:text-indigo-900">ویرایش</a>
                                        <form action="{{ route('admin.sms-templates.destroy', $template) }}" method="POST" class="inline-block ml-2" onsubmit="return confirm('آیا از حذف این قالب مطمئن هستید؟');">
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
