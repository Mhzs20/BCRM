@extends('admin.layouts.app')

@section('title', 'تایید پیامک‌های دستی')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            تایید پیامک‌های دستی
        </h2>
    </div>
@endsection

@section('content')
    <div class="py-4 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 sm:p-6 bg-white border-b border-gray-200">
                    <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-4">لیست پیامک‌های در انتظار تایید</h3>

                    @if (session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 text-sm" role="alert">
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 text-sm" role="alert">
                            <span class="block sm:inline">{{ session('error') }}</span>
                        </div>
                    @endif

                    <!-- Mobile Card View -->
                    <div class="block lg:hidden space-y-4">
                        @forelse ($pendingSmsBatches as $batch)
                            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                                <!-- Header -->
                                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $batch->salon->name ?? 'N/A' }}</p>
                                            <p class="text-xs text-gray-500 mt-1">{{ $batch->user->name ?? 'N/A' }}</p>
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $batch->sms_parts ?? 1 }} پارت
                                        </span>
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="px-4 py-3 space-y-3">
                                    <!-- Mobile Number -->
                                    <div class="flex items-start">
                                        <span class="text-xs font-medium text-gray-500 w-24 flex-shrink-0">شماره موبایل:</span>
                                        @if($batch->user && $batch->user->mobile)
                                            <a href="{{ route('admin.referral.users.wallet', $batch->user->id) }}" class="text-sm text-blue-600 hover:text-blue-800 underline">
                                                {{ $batch->user->mobile }}
                                            </a>
                                        @else
                                            <span class="text-sm text-gray-500">N/A</span>
                                        @endif
                                    </div>

                                    <!-- Recipients Type -->
                                    <div class="flex items-start">
                                        <span class="text-xs font-medium text-gray-500 w-24 flex-shrink-0">نوع گیرندگان:</span>
                                        <span class="text-sm text-gray-900">
                                            @php
                                                $recipientsType = '';
                                                switch ($batch->recipients_type) {
                                                    case 'all_customers':
                                                        $recipientsType = 'همه مشتریان';
                                                        break;
                                                    case 'selected_customers':
                                                        $recipientsType = 'مشتریان انتخاب شده';
                                                        break;
                                                    case 'phone_contacts':
                                                        $recipientsType = 'شماره‌های تماس';
                                                        break;
                                                    default:
                                                        $recipientsType = $batch->recipients_type;
                                                        break;
                                                }
                                            @endphp
                                            {{ $recipientsType }}
                                        </span>
                                    </div>

                                    <!-- Recipients Count -->
                                    <div class="flex items-start">
                                        <span class="text-xs font-medium text-gray-500 w-24 flex-shrink-0">تعداد گیرنده:</span>
                                        <span class="text-sm text-gray-900 font-semibold">{{ $batch->recipients_count }}</span>
                                    </div>

                                    <!-- Deducted Charge -->
                                    <div class="flex items-start">
                                        <span class="text-xs font-medium text-gray-500 w-24 flex-shrink-0">شارژ کسر شده:</span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-orange-100 text-orange-800">
                                            {{ $batch->total_sms_parts_in_batch ?? ($batch->sms_parts * $batch->recipients_count) }} پارت
                                        </span>
                                    </div>

                                    <!-- Message Content -->
                                    <div class="flex items-start">
                                        <span class="text-xs font-medium text-gray-500 w-24 flex-shrink-0">شارژ کسر شده:</span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-orange-100 text-orange-800">
                                            {{ $batch->total_sms_parts_in_batch ?? ($batch->sms_parts * $batch->recipients_count) }} پارت
                                        </span>
                                    </div>

                                    <!-- Message Content -->
                                    <div>
                                        <span class="text-xs font-medium text-gray-500">متن پیام:</span>
                                        <div class="mt-2 space-y-2">
                                            <div>
                                                <span class="text-xs font-bold text-gray-700">متن اصلی:</span>
                                                <p class="text-xs text-gray-800 bg-gray-100 rounded-md p-2 mt-1 whitespace-pre-wrap break-words">{{ $batch->original_content ?? $batch->content }}</p>
                                            </div>
                                            @if(!empty($batch->edited_content) && $batch->edited_content !== $batch->original_content)
                                            <div>
                                                <span class="text-xs font-bold text-blue-700">متن ویرایش‌شده:</span>
                                                <p class="text-xs text-blue-800 bg-blue-50 rounded-md p-2 mt-1 whitespace-pre-wrap break-words">{{ $batch->edited_content }}</p>
                                            </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Date -->
                                    <div class="flex items-start">
                                        <span class="text-xs font-medium text-gray-500 w-24 flex-shrink-0">تاریخ درخواست:</span>
                                        <span class="text-xs text-gray-900">{{ \Morilog\Jalali\Jalalian::fromCarbon($batch->created_at)->format('Y/m/d H:i') }}</span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="bg-gray-50 px-4 py-3 space-y-2">
                                    <button onclick='openEditModal(@json($batch))' class="w-full inline-flex justify-center items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        ویرایش پیام
                                    </button>
                                    <form id="approveForm_mobile_{{ $batch->batch_id }}" action="{{ route('admin.manual_sms.approve', $batch->batch_id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="edited_message_content" id="approve_message_content_mobile_{{ $batch->batch_id }}" value="{{ $batch->display_content }}">
                                        <button type="submit" class="w-full inline-flex justify-center items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                            تایید و ارسال
                                        </button>
                                    </form>
                                    <button onclick="openRejectModal('{{ $batch->batch_id }}')" class="w-full inline-flex justify-center items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        رد
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8 text-gray-500 text-sm">
                                هیچ پیامک در انتظار تاییدی وجود ندارد.
                            </div>
                        @endforelse
                    </div>

                    <!-- Desktop Table View -->
                    <div class="hidden lg:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        سالن
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        فرستنده
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        شماره موبایل
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        متن پیام
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        تعداد پیامک
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        شارژ کسر شده
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        تعداد گیرنده
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        تاریخ
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        اقدامات
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($pendingSmsBatches as $batch)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            <div class="font-medium">{{ $batch->salon->name ?? 'N/A' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            <div class="font-medium">{{ $batch->user->name ?? 'N/A' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if($batch->user && $batch->user->mobile)
                                                <a href="{{ route('admin.referral.users.wallet', $batch->user->id) }}" class="text-blue-600 hover:text-blue-800 font-medium underline" title="مشاهده پروفایل کاربر">
                                                    {{ $batch->user->mobile }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">N/A</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <div class="max-w-xs">
                                                @if(!empty($batch->edited_content) && $batch->edited_content !== $batch->original_content)
                                                    <div class="text-xs text-gray-500 mb-1">ویرایش شده:</div>
                                                    <p class="text-sm text-gray-900 bg-blue-50 rounded p-2 line-clamp-3">{{ $batch->edited_content }}</p>
                                                    <details class="mt-1">
                                                        <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">متن اصلی</summary>
                                                        <p class="text-xs text-gray-600 bg-gray-100 rounded p-2 mt-1">{{ $batch->original_content ?? $batch->content }}</p>
                                                    </details>
                                                @else
                                                    <p class="text-sm text-gray-900 line-clamp-3">{{ $batch->original_content ?? $batch->content }}</p>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-center">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                                {{ $batch->sms_parts ?? 1 }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-center">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-800">
                                                {{ $batch->total_sms_parts_in_batch ?? ($batch->sms_parts * $batch->recipients_count) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-center font-semibold">
                                            {{ $batch->recipients_count }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            <div class="text-xs">{{ \Morilog\Jalali\Jalalian::fromCarbon($batch->created_at)->format('Y/m/d') }}</div>
                                            <div class="text-xs text-gray-400">{{ \Morilog\Jalali\Jalalian::fromCarbon($batch->created_at)->format('H:i') }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex flex-col gap-1.5">
                                                <button onclick='openEditModal(@json($batch))' class="inline-flex items-center justify-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                    ویرایش
                                                </button>
                                                <form id="approveForm_{{ $batch->batch_id }}" action="{{ route('admin.manual_sms.approve', $batch->batch_id) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="edited_message_content" id="approve_message_content_{{ $batch->batch_id }}" value="{{ $batch->display_content }}">
                                                    <button type="submit" class="w-full inline-flex items-center justify-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                        تایید
                                                    </button>
                                                </form>
                                                <button onclick="openRejectModal('{{ $batch->batch_id }}')" class="inline-flex items-center justify-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                                                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                    رد
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-6 py-8 text-sm text-gray-500 text-center">
                                            <div class="flex flex-col items-center">
                                                <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                                </svg>
                                                <p class="text-gray-600 font-medium">هیچ پیامک در انتظار تاییدی وجود ندارد.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $pendingSmsBatches->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Message Modal -->
    <div id="editMessageModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full mx-4 sm:mx-0">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start w-full">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="ri-pencil-line text-xl sm:text-2xl text-blue-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:mr-4 sm:text-right w-full">
                            <h3 class="text-base sm:text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                ویرایش پیامک
                            </h3>
                            <div class="mt-2">
                                <p class="text-xs sm:text-sm text-gray-500">
                                    متن پیامک را ویرایش کنید.
                                </p>
                                <textarea id="edit_message_content_textarea" rows="6" class="mt-2 shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full text-sm border-gray-400 rounded-md bg-gray-50 resize-none overflow-y-auto"></textarea>
                                <div class="mt-2 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                                    <div class="text-xs sm:text-sm text-gray-600">
                                        <span>تعداد کاراکتر: </span>
                                        <span id="char_count" class="font-semibold">0</span>
                                    </div>
                                    <div class="text-xs sm:text-sm text-blue-700 font-semibold">
                                        <span>تعداد پیامک (پارت): </span>
                                        <span id="sms_parts_count" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">1</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-col-reverse sm:flex-row sm:flex-row-reverse w-full justify-between gap-2">
                    <button type="button" id="saveEditedContentButton" class="w-full sm:w-auto inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-sm sm:text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        ذخیره ویرایش
                    </button>
                    <button type="button" onclick="closeEditModal()" class="w-full sm:w-auto inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm sm:text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        انصراف
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block w-full align-bottom bg-white rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg mx-4 sm:mx-0">
                <form id="rejectForm" method="POST">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 w-full sm:pb-4">
                        <div class="sm:flex sm:items-start w-full">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="ri-close-circle-line text-xl sm:text-2xl text-red-600"></i>
                            </div>
                            <div class="mt-3 w-full text-center sm:mt-0 sm:mr-4 sm:text-right w-full">
                                <h3 class="text-base sm:text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    رد کردن درخواست پیامک
                                </h3>
                                <div class="mt-2">
                                    <p class="text-xs sm:text-sm text-gray-500">
                                        لطفا دلیل رد کردن این درخواست را وارد کنید. این دلیل به کاربر نمایش داده خواهد شد.
                                    </p>
                                    <textarea name="rejection_reason" id="rejection_reason" rows="3" class="mt-2 w-full shadow-sm focus:ring-red-500 focus:border-red-500 block w-full text-sm border-gray-300 rounded-md" required></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-col-reverse sm:flex-row sm:flex-row-reverse w-full justify-between gap-2">
                        <button type="submit" class="w-full sm:w-auto inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-sm sm:text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            رد کردن
                        </button>
                        <button type="button" onclick="closeRejectModal()" class="w-full sm:w-auto inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm sm:text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            انصراف
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- حذف مودال مشاهده پیام -->

    <!-- Cost Change Warning Modal -->
    <div id="costChangeWarningModal" class="fixed z-20 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full mx-4 sm:mx-0">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start w-full">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:mr-4 sm:text-right w-full">
                            <h3 class="text-base sm:text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                هشدار تغییر هزینه
                            </h3>
                            <div class="mt-2">
                                <div class="bg-yellow-50 border-r-4 border-yellow-400 p-4 rounded">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <div class="mr-3 text-sm text-yellow-700">
                                            <p class="font-medium mb-2">تعداد پیامک تغییر کرده است!</p>
                                            <div class="space-y-2">
                                                <div class="flex items-center justify-between bg-white rounded px-3 py-2">
                                                    <span class="text-gray-600">تعداد پیامک اولیه:</span>
                                                    <span id="original_parts" class="font-bold text-gray-900 px-2.5 py-0.5 rounded-full bg-gray-100">-</span>
                                                </div>
                                                <div class="flex items-center justify-between bg-white rounded px-3 py-2">
                                                    <span class="text-gray-600">تعداد پیامک جدید:</span>
                                                    <span id="new_parts" class="font-bold px-2.5 py-0.5 rounded-full">-</span>
                                                </div>
                                                <div id="cost_difference" class="flex items-center justify-between bg-white rounded px-3 py-2 border-t-2 border-yellow-200">
                                                    <span class="text-gray-600 font-semibold">تغییر هزینه:</span>
                                                    <span id="cost_diff_value" class="font-bold">-</span>
                                                </div>
                                                <div class="bg-blue-50 rounded px-3 py-2 mt-2">
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-gray-600 text-xs">تعداد گیرنده:</span>
                                                        <span id="recipients_count" class="font-bold text-gray-900">-</span>
                                                    </div>
                                                    <div class="flex items-center justify-between mt-1">
                                                        <span class="text-gray-600 text-xs">هزینه کل اولیه:</span>
                                                        <span id="total_original_cost" class="font-bold text-gray-900">-</span>
                                                    </div>
                                                    <div class="flex items-center justify-between mt-1">
                                                        <span class="text-gray-600 text-xs">هزینه کل جدید:</span>
                                                        <span id="total_new_cost" class="font-bold text-blue-700">-</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="mt-3 text-xs">
                                                با تایید این تغییر، هزینه جدید از کاربر کسر خواهد شد.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-col-reverse sm:flex-row sm:flex-row-reverse w-full justify-between gap-2">
                    <button type="button" id="confirmCostChangeButton" class="w-full sm:w-auto inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-sm sm:text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                        تایید و ذخیره تغییرات
                    </button>
                    <button type="button" onclick="closeCostChangeWarningModal()" class="w-full sm:w-auto inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm sm:text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        انصراف
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentEditingBatchId = null;
        let originalBatchData = null;

        // Calculate SMS parts based on message length
        function calculateSmsParts(message) {
            if (!message || message.length === 0) {
                return 1;
            }
            
            const length = message.length;
            
            // Persian/Unicode SMS: 70 chars for single, 67 chars per part for multiple
            // Standard ASCII SMS: 160 chars for single, 153 chars per part for multiple
            const isPersian = /[\u0600-\u06FF]/.test(message);
            
            if (isPersian) {
                if (length <= 70) {
                    return 1;
                } else {
                    return Math.ceil(length / 67);
                }
            } else {
                if (length <= 160) {
                    return 1;
                } else {
                    return Math.ceil(length / 153);
                }
            }
        }

        // Update character count and SMS parts
        function updateSmsStats(textarea) {
            const message = textarea.value;
            const charCount = message.length;
            const smsParts = calculateSmsParts(message);
            
            document.getElementById('char_count').textContent = charCount;
            document.getElementById('sms_parts_count').textContent = smsParts + ' پارت';
        }

        function openViewModal(batch) {
            const modal = document.getElementById('viewMessageModal');
            const originalContentEl = document.getElementById('originalContent');
            const editedContentEl = document.getElementById('editedContent');
            const editedContentContainer = document.getElementById('editedContentContainer');

            originalContentEl.innerText = batch.original_content || batch.content;

            if (batch.edited_content && batch.original_content !== batch.edited_content) {
                editedContentEl.innerText = batch.edited_content;
                editedContentContainer.classList.remove('hidden');
            } else {
                editedContentContainer.classList.add('hidden');
            }

            modal.classList.remove('hidden');
        }

        function closeViewModal() {
            const modal = document.getElementById('viewMessageModal');
            modal.classList.add('hidden');
        }

        function openRejectModal(batchId) {
            const modal = document.getElementById('rejectModal');
            const form = document.getElementById('rejectForm');
            form.action = `/admin/manual-sms-approval/${batchId}/reject`;
            modal.classList.remove('hidden');
        }

        function closeRejectModal() {
            const modal = document.getElementById('rejectModal');
            modal.classList.add('hidden');
        }

        function openCostChangeWarningModal(originalParts, newParts, recipientsCount) {
            const modal = document.getElementById('costChangeWarningModal');
            const originalPartsEl = document.getElementById('original_parts');
            const newPartsEl = document.getElementById('new_parts');
            const costDiffEl = document.getElementById('cost_diff_value');
            const recipientsEl = document.getElementById('recipients_count');
            const totalOriginalEl = document.getElementById('total_original_cost');
            const totalNewEl = document.getElementById('total_new_cost');
            
            const totalOriginalCost = originalParts * recipientsCount;
            const totalNewCost = newParts * recipientsCount;
            const diff = newParts - originalParts;
            
            originalPartsEl.textContent = originalParts + ' پارت';
            newPartsEl.textContent = newParts + ' پارت';
            recipientsEl.textContent = recipientsCount + ' نفر';
            totalOriginalEl.textContent = totalOriginalCost + ' پارت';
            totalNewEl.textContent = totalNewCost + ' پارت';
            
            // Color code the difference
            if (diff > 0) {
                newPartsEl.className = 'font-bold px-2.5 py-0.5 rounded-full bg-red-100 text-red-800';
                costDiffEl.textContent = '+' + diff + ' پارت (افزایش)';
                costDiffEl.className = 'font-bold text-red-700';
            } else if (diff < 0) {
                newPartsEl.className = 'font-bold px-2.5 py-0.5 rounded-full bg-green-100 text-green-800';
                costDiffEl.textContent = diff + ' پارت (کاهش)';
                costDiffEl.className = 'font-bold text-green-700';
            } else {
                newPartsEl.className = 'font-bold px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-800';
                costDiffEl.textContent = 'بدون تغییر';
                costDiffEl.className = 'font-bold text-gray-700';
            }
            
            modal.classList.remove('hidden');
        }

        function closeCostChangeWarningModal() {
            const modal = document.getElementById('costChangeWarningModal');
            modal.classList.add('hidden');
        }

        function openEditModal(batch) {
            currentEditingBatchId = batch.batch_id;
            originalBatchData = batch; // Store original data for comparison
            const modal = document.getElementById('editMessageModal');
            const textarea = document.getElementById('edit_message_content_textarea');
            textarea.value = batch.display_content;
            
            // Update SMS stats on open
            updateSmsStats(textarea);
            
            modal.classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editMessageModal').classList.add('hidden');
            currentEditingBatchId = null;
        }

        // Add event listener for textarea to update stats in real-time
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.getElementById('edit_message_content_textarea');
            textarea.addEventListener('input', function() {
                updateSmsStats(this);
            });
        });

        async function saveEditedContent(skipWarning = false) {
            if (!currentEditingBatchId || !originalBatchData) return;

            const textarea = document.getElementById('edit_message_content_textarea');
            const editedContent = textarea.value;
            
            // Calculate new SMS parts
            const newParts = calculateSmsParts(editedContent);
            const originalParts = originalBatchData.sms_parts || 1;
            const recipientsCount = originalBatchData.recipients_count || 1;
            
            // Check if parts changed and show warning if not already confirmed
            if (!skipWarning && newParts !== originalParts) {
                openCostChangeWarningModal(originalParts, newParts, recipientsCount);
                return; // Don't proceed with save yet
            }

            try {
                const response = await fetch(`/admin/manual-sms-approval/${currentEditingBatchId}/update-content`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ edited_message_content: editedContent })
                });

                const data = await response.json();

                if (response.ok) {
                    alert(data.message);
                    // Update the hidden input for both desktop and mobile approve forms
                    const desktopInput = document.getElementById(`approve_message_content_${currentEditingBatchId}`);
                    const mobileInput = document.getElementById(`approve_message_content_mobile_${currentEditingBatchId}`);
                    if (desktopInput) desktopInput.value = editedContent;
                    if (mobileInput) mobileInput.value = editedContent;
                    closeEditModal();
                    closeCostChangeWarningModal();
                    
                    // Reload page to show updated parts
                    location.reload();
                } else {
                    alert('خطا در ذخیره ویرایش: ' + (data.message || 'خطای ناشناخته'));
                }
            } catch (error) {
                console.error('Error saving edited content:', error);
                alert('خطا در ارتباط با سرور برای ذخیره ویرایش.');
            }
        }

        document.getElementById('saveEditedContentButton').addEventListener('click', () => saveEditedContent(false));
        document.getElementById('confirmCostChangeButton').addEventListener('click', () => saveEditedContent(true));
    </script>
@endsection
