@extends('admin.layouts.app')

@section('title', 'تایید کمپین‌های پیامکی')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            تایید کمپین‌های پیامکی
        </h2>
    </div>
@endsection

@section('content')
    <div class="py-4 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 sm:p-6 bg-white border-b border-gray-200">
                    <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-4">لیست کمپین‌های در انتظار تایید</h3>

                    @if (session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('error') }}</span>
                        </div>
                    @endif

                    <!-- Mobile Card View -->
                    <div class="block lg:hidden space-y-4">
                        @forelse ($pendingCampaigns as $campaign)
                            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ $campaign->salon->name ?? 'نامشخص' }}</p>
                                        <p class="text-xs text-gray-500 mt-1">{{ $campaign->user->name ?? 'نامشخص' }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800" title="تعداد مشتریان">
                                            {{ $campaign->customer_count }}
                                        </span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-orange-100 text-orange-800" title="پارت‌های کسر شده">
                                            {{ $campaign->total_cost }} پارت
                                        </span>
                                    </div>
                                </div>
                                <div class="px-4 py-3 space-y-3">
                                    <div>
                                        <span class="text-xs font-medium text-gray-500">متن پیام:</span>
                                        <p class="text-xs text-gray-900 bg-gray-50 rounded p-2 mt-2 break-words">{{ $campaign->message ?: ($campaign->smsTemplate->template ?? 'بدون متن') }}</p>
                                    </div>
                                    <div class="flex items-start">
                                        <span class="text-xs font-medium text-gray-500 w-24 flex-shrink-0">تاریخ درخواست:</span>
                                        <span class="text-xs text-gray-900">{{ \Morilog\Jalali\Jalalian::fromCarbon($campaign->created_at)->format('Y/m/d H:i') }}</span>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-4 py-3 space-y-2">
                                    <button onclick='openEditModal(@json($campaign))' class="w-full inline-flex justify-center items-center px-3 py-2 text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        ویرایش پیام
                                    </button>
                                    <button onclick="approveCampaign({{ $campaign->id }})" class="w-full inline-flex justify-center items-center px-3 py-2 text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                        تایید و ارسال
                                    </button>
                                    <button onclick="openRejectModal({{ $campaign->id }})" class="w-full inline-flex justify-center items-center px-3 py-2 text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        رد
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8 text-gray-500 text-sm">هیچ کمپین در انتظار تاییدی وجود ندارد.</div>
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
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        تعداد مشتریان
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        تعداد پیامک
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        شارژ کسر شده
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        تاریخ درخواست
                                    </th>
                                    <th scope="col" class="relative px-4 py-3 text-center">
                                        <span class="sr-only">اقدامات</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($pendingCampaigns as $campaign)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            {{ $campaign->salon->name ?? 'نامشخص' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            {{ $campaign->user->name ?? 'نامشخص' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                                            @if($campaign->user && $campaign->user->mobile)
                                                <a href="{{ route('admin.referral.users.wallet', $campaign->user->id) }}" class="text-blue-600 hover:text-blue-800 font-medium underline" title="مشاهده پروفایل کاربر">
                                                    {{ $campaign->user->mobile }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">N/A</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            <div class="space-y-2">
                                                <div>
                                                    <span class="text-xs font-bold text-gray-700">متن اصلی:</span>
                                                    {{-- show stored message first, fallback to linked template text if available --}}
                                                    <p class="text-xs text-gray-800 bg-gray-100 rounded-md p-2 mt-1 whitespace-pre-wrap max-w-xs">{{ $campaign->message ?: ($campaign->smsTemplate->template ?? 'بدون متن') }}</p>
                                                </div>
                                                   {{-- Campaign table does not currently have explicit edited_message/original_message columns.
                                                       If an edited_message field exists in the future, this will show it; otherwise skip. --}}
                                                   @if(!empty($campaign->edited_message) && $campaign->edited_message !== ($campaign->original_message ?? ''))
                                                <div>
                                                    <span class="text-xs font-bold text-blue-700">متن ویرایش‌شده:</span>
                                                    <p class="text-xs text-blue-800 bg-blue-50 rounded-md p-2 mt-1 whitespace-pre-wrap">{{ $campaign->edited_message }}</p>
                                                </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-gray-900">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">{{ $campaign->customer_count }}</span>
                                        </td>
                                        @php
                                            $msg = $campaign->message ?: ($campaign->smsTemplate->template ?? '');
                                            $length = mb_strlen($msg);
                                            $isPersian = preg_match('/[\x{0600}-\x{06FF}]/u', $msg) === 1;
                                            if ($isPersian) {
                                                $parts = $length <= 70 ? 1 : (int)ceil($length / 67);
                                            } else {
                                                $parts = $length <= 160 ? 1 : (int)ceil($length / 153);
                                            }
                                        @endphp
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-gray-900">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">{{ $parts }}</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-gray-900">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-800" title="پارت‌های کسر شده">{{ $campaign->total_cost }}</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            {{ \Morilog\Jalali\Jalalian::fromCarbon($campaign->created_at)->format('Y/m/d H:i') }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium">
                                            <div class="flex flex-col gap-1.5">
                                                <button onclick='openEditModal(@json($campaign))' class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                    ویرایش
                                                </button>
                                                <button onclick="approveCampaign({{ $campaign->id }})" class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                    تایید و ارسال
                                                </button>
                                                <button onclick="openRejectModal({{ $campaign->id }})" class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                    رد
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                            هیچ کمپین در انتظار تاییدی وجود ندارد.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $pendingCampaigns->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- حذف مودال مشاهده پیام -->

    <!-- Edit Modal -->
    <div id="editModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeEditModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="editForm">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="ri-edit-line text-2xl text-blue-600"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:mr-4 sm:text-right w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    ویرایش متن پیام
                                </h3>
                                <div class="mt-2">
                                    <textarea id="editMessageContent" rows="6" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 mt-1 block w-full sm:text-sm border-gray-300 rounded-md resize-none overflow-y-auto" placeholder="متن پیام خود را وارد کنید..."></textarea>
                                    <div class="mt-2 flex items-center justify-between">
                                        <div class="text-sm text-gray-600">
                                            <span>تعداد کاراکتر: </span>
                                            <span id="campaign_char_count" class="font-semibold">0</span>
                                        </div>
                                        <div class="text-sm text-blue-700 font-semibold">
                                            <span>تعداد پیامک: </span>
                                            <span id="campaign_sms_parts_count" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">1 پارت</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            ذخیره تغییرات
                        </button>
                        <button type="button" onclick="closeEditModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            انصراف
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeRejectModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="rejectForm">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="ri-close-circle-line text-2xl text-red-600"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:mr-4 sm:text-right w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    رد کمپین پیامکی
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        لطفاً دلیل رد این کمپین را وارد کنید:
                                    </p>
                                    <textarea id="rejectReason" rows="4" class="shadow-sm focus:ring-red-500 focus:border-red-500 mt-2 block w-full sm:text-sm border-gray-300 rounded-md" placeholder="دلیل رد..." required></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            رد کمپین
                        </button>
                        <button type="button" onclick="closeRejectModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            انصراف
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
                                                    <span id="campaign_original_parts" class="font-bold text-gray-900 px-2.5 py-0.5 rounded-full bg-gray-100">-</span>
                                                </div>
                                                <div class="flex items-center justify-between bg-white rounded px-3 py-2">
                                                    <span class="text-gray-600">تعداد پیامک جدید:</span>
                                                    <span id="campaign_new_parts" class="font-bold px-2.5 py-0.5 rounded-full">-</span>
                                                </div>
                                                <div id="campaign_cost_difference" class="flex items-center justify-between bg-white rounded px-3 py-2 border-t-2 border-yellow-200">
                                                    <span class="text-gray-600 font-semibold">تغییر هزینه:</span>
                                                    <span id="campaign_cost_diff_value" class="font-bold">-</span>
                                                </div>
                                                <div class="bg-blue-50 rounded px-3 py-2 mt-2">
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-gray-600 text-xs">تعداد گیرنده:</span>
                                                        <span id="campaign_recipients_count" class="font-bold text-gray-900">-</span>
                                                    </div>
                                                    <div class="flex items-center justify-between mt-1">
                                                        <span class="text-gray-600 text-xs">هزینه کل اولیه:</span>
                                                        <span id="campaign_total_original_cost" class="font-bold text-gray-900">-</span>
                                                    </div>
                                                    <div class="flex items-center justify-between mt-1">
                                                        <span class="text-gray-600 text-xs">هزینه کل جدید:</span>
                                                        <span id="campaign_total_new_cost" class="font-bold text-blue-700">-</span>
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
                    <button type="button" id="confirmCampaignCostChangeButton" class="w-full sm:w-auto inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-sm sm:text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
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
    let currentEditingCampaignId = null;
    let currentRejectingCampaignId = null;
    let originalCampaignData = null;

    // Calculate SMS parts based on message length
    function calculateSmsParts(message) {
        if (!message || message.length === 0) {
            return 1;
        }
        
        const length = message.length;
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

    // Update character count and SMS parts for campaign edit
    function updateCampaignSmsStats(textarea) {
        const message = textarea.value;
        const charCount = message.length;
        const smsParts = calculateSmsParts(message);
        
        document.getElementById('campaign_char_count').textContent = charCount;
        document.getElementById('campaign_sms_parts_count').textContent = smsParts + ' پارت';
    }

    function openCostChangeWarningModal(originalParts, newParts, recipientsCount) {
        const modal = document.getElementById('costChangeWarningModal');
        const originalPartsEl = document.getElementById('campaign_original_parts');
        const newPartsEl = document.getElementById('campaign_new_parts');
        const costDiffEl = document.getElementById('campaign_cost_diff_value');
        const recipientsEl = document.getElementById('campaign_recipients_count');
        const totalOriginalEl = document.getElementById('campaign_total_original_cost');
        const totalNewEl = document.getElementById('campaign_total_new_cost');
        
        if (!originalPartsEl || !newPartsEl || !costDiffEl || !recipientsEl || !totalOriginalEl || !totalNewEl) {
            console.error('Warning modal elements not found');
            return;
        }
        
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

    function openViewModal(campaign) {
        document.getElementById('viewSalonName').innerText = campaign.salon?.name || 'نامشخص';
        document.getElementById('viewUserName').innerText = campaign.user?.name || 'نامشخص';
        document.getElementById('viewCustomerCount').innerText = campaign.customer_count;
        document.getElementById('viewTotalCost').innerText = campaign.total_cost;
        document.getElementById('viewMessageContent').innerText = campaign.message;

        // Display filters in a readable format
        let filtersText = '';
        if (campaign.filters) {
            try {
                const filters = typeof campaign.filters === 'string' ? JSON.parse(campaign.filters) : campaign.filters;
                
                // Age filter
                if (filters.min_age || filters.max_age) {
                    filtersText += `سن: ${filters.min_age || 0} تا ${filters.max_age || 'نامحدود'}\n`;
                }
                
                // Professions - prioritize enhanced data
                if (filters.professions && Array.isArray(filters.professions) && filters.professions.length > 0) {
                    filtersText += `مشاغل: ${filters.professions.map(p => p.name).join(', ')}\n`;
                } else if (filters.profession_id && Array.isArray(filters.profession_id) && filters.profession_id.length > 0) {
                    filtersText += `شناسه مشاغل: ${filters.profession_id.join(', ')}\n`;
                }
                
                // Customer groups - prioritize enhanced data
                if (filters.customer_groups && Array.isArray(filters.customer_groups) && filters.customer_groups.length > 0) {
                    filtersText += `گروه‌های مشتری: ${filters.customer_groups.map(g => g.name).join(', ')}\n`;
                } else if (filters.customer_group_id && Array.isArray(filters.customer_group_id) && filters.customer_group_id.length > 0) {
                    filtersText += `شناسه گروه‌های مشتری: ${filters.customer_group_id.join(', ')}\n`;
                }
                
                // How introduced - prioritize enhanced data
                if (filters.how_introduceds && Array.isArray(filters.how_introduceds) && filters.how_introduceds.length > 0) {
                    filtersText += `نحوه آشنایی: ${filters.how_introduceds.map(h => h.name).join(', ')}\n`;
                } else if (filters.how_introduced_id && Array.isArray(filters.how_introduced_id) && filters.how_introduced_id.length > 0) {
                    filtersText += `شناسه نحوه آشنایی: ${filters.how_introduced_id.join(', ')}\n`;
                }
                
                // Payment amount filter
                if (filters.min_payment || filters.max_payment) {
                    const minPayment = filters.min_payment ? filters.min_payment.toLocaleString() : '0';
                    const maxPayment = filters.max_payment ? filters.max_payment.toLocaleString() : 'نامحدود';
                    filtersText += `مبلغ پرداخت: ${minPayment} تا ${maxPayment} تومان\n`;
                }
                
                // Appointments count filter
                if (filters.min_appointments || filters.max_appointments) {
                    filtersText += `تعداد قرارملاقات: ${filters.min_appointments || 0} تا ${filters.max_appointments || 'نامحدود'}\n`;
                }
                
                // Phone numbers filter (if exists)
                if (filters.phone_numbers && Array.isArray(filters.phone_numbers) && filters.phone_numbers.length > 0) {
                    filtersText += `شماره‌های تلفن: ${filters.phone_numbers.join(', ')}\n`;
                }
            } catch (e) {
                console.error('خطا در پردازش فیلترها:', e);
                filtersText = 'خطا در نمایش فیلترها';
            }
        }
        
        document.getElementById('viewFilters').innerText = filtersText || 'فیلتری اعمال نشده';
        document.getElementById('viewMessageModal').classList.remove('hidden');
    }

    function closeViewModal() {
        document.getElementById('viewMessageModal').classList.add('hidden');
    }

    function openEditModal(campaign) {
        currentEditingCampaignId = campaign.id;
        originalCampaignData = campaign;
        const textarea = document.getElementById('editMessageContent');
        textarea.value = campaign.message;
        
        // Update SMS stats on open
        updateCampaignSmsStats(textarea);
        
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        currentEditingCampaignId = null;
    }

    function openRejectModal(campaignId) {
        currentRejectingCampaignId = campaignId;
        document.getElementById('rejectReason').value = '';
        document.getElementById('rejectModal').classList.remove('hidden');
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
        currentRejectingCampaignId = null;
    }

    function approveCampaign(campaignId) {
        if (confirm('آیا از تایید و ارسال این کمپین اطمینان دارید؟')) {
            fetch(`/admin/sms-campaign-approval/${campaignId}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('خطا: ' + (data.message || 'خطای نامشخص'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('خطا در تایید کمپین');
            });
        }
    }

    // Edit form submission
    async function submitCampaignEdit(skipWarning = false) {
        if (!currentEditingCampaignId || !originalCampaignData) return;
        
        const message = document.getElementById('editMessageContent').value;
        
        // Calculate new SMS parts
        const newParts = calculateSmsParts(message);
        const originalParts = calculateSmsParts(originalCampaignData.message);
        const recipientsCount = originalCampaignData.customer_count || 1;
        
        // Check if parts changed and show warning if not already confirmed
        if (!skipWarning && newParts !== originalParts) {
            openCostChangeWarningModal(originalParts, newParts, recipientsCount);
            return; // Don't proceed with save yet
        }
        
        try {
            const response = await fetch(`/admin/sms-campaign-approval/${currentEditingCampaignId}/update-content`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
                },
                body: JSON.stringify({ edited_message: message })
            });
            
            const data = await response.json();
            
            if (data.success) {
                closeEditModal();
                closeCostChangeWarningModal();
                location.reload();
            } else {
                alert('خطا: ' + (data.message || 'خطای نامشخص'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('خطا در ویرایش پیام');
        }
    }

    document.getElementById('editForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitCampaignEdit(false);
    });
    
    document.getElementById('confirmCampaignCostChangeButton').addEventListener('click', () => submitCampaignEdit(true));
    
    // Add event listener for textarea to update stats in real-time
    document.addEventListener('DOMContentLoaded', function() {
        const textarea = document.getElementById('editMessageContent');
        textarea.addEventListener('input', function() {
            updateCampaignSmsStats(this);
        });
    });

    // Reject form submission
    document.getElementById('rejectForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const reason = document.getElementById('rejectReason').value;
        
        fetch(`/admin/sms-campaign-approval/${currentRejectingCampaignId}/reject`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
            },
            body: JSON.stringify({ rejection_reason: reason })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeRejectModal();
                location.reload();
            } else {
                alert('خطا: ' + (data.message || 'خطای نامشخص'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('خطا در رد کمپین');
        });
    });
</script>
@endsection
