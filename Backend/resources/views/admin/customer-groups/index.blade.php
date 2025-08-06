@extends('admin.layouts.app')

@section('content')
    <div class="container mx-auto px-4 sm:px-8">
        <div class="py-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-bold text-gray-800 leading-tight">گروه‌های مشتریان</h2>
                <a href="{{ route('admin.customer-groups.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <i class="ri-add-line mr-2"></i> افزودن گروه مشتری جدید
                </a>
            </div>

            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="p-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full leading-normal">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="px-5 py-3 border-b-2 border-gray-200 text-right text-sm font-semibold text-gray-600 uppercase tracking-wider">
                                        نام
                                    </th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 text-right text-sm font-semibold text-gray-600 uppercase tracking-wider">
                                        تاریخ ایجاد
                                    </th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 text-right text-sm font-semibold text-gray-600 uppercase tracking-wider">
                                        عملیات
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($customerGroups as $customerGroup)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                            <p class="text-gray-900 whitespace-no-wrap">{{ $customerGroup->name }}</p>
                                        </td>
                                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                            <p class="text-gray-900 whitespace-no-wrap">{{ $customerGroup->created_at->format('Y/m/d H:i') }}</p>
                                        </td>
                                        <td class="px-5 py-5 border-b border-gray-200 bg-blue text-sm">
                                            <a href="{{ route('admin.customer-groups.edit', $customerGroup->id) }}" class="inline-flex items-center px-3 py-1 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                                                <i class="ri-edit-line mr-1"></i> ویرایش
                                            </a>
                                            <form action="{{ route('admin.customer-groups.destroy', $customerGroup->id) }}" method="POST" class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center px-3 py-1 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-900 focus:outline-none focus:border-red-900 focus:ring ring-red-300 disabled:opacity-25 transition ease-in-out duration-150" onclick="return confirm('آیا از حذف این گروه مشتری مطمئن هستید؟')">
                                                    <i class="ri-delete-bin-line mr-1"></i> حذف
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center text-gray-600">
                                            هیچ گروه مشتری یافت نشد.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="px-5 py-5 bg-white border-t flex flex-col xs:flex-row items-center xs:justify-between">
                    {{-- Pagination will go here if needed, assuming $customerGroups is paginated --}}
                    {{-- {{ $customerGroups->links() }} --}}
                </div>
            </div>
        </div>
    </div>
@endsection
