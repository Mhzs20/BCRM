@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-4 sm:px-8">
    <div class="py-8">
        <div>
            <h2 class="text-2xl font-semibold leading-tight">گزارش ارسال پیامک‌های دستی</h2>
        </div>
        <div class="my-2 flex sm:flex-row flex-col">
            {{-- Add any filters if needed in the future --}}
        </div>
        <div class="-mx-4 sm:-mx-8 px-4 sm:px-8 py-4 overflow-x-auto">
            <div class="inline-block min-w-full shadow rounded-lg overflow-hidden">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                سالن
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                محتوای پیام (اصلی / ارسال شده)
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                تعداد گیرندگان
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                وضعیت تایید
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                وضعیت ارسال
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">
                                تاریخ درخواست
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($smsBatches as $batch)
                            <tr>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <p class="text-gray-900 whitespace-no-wrap">{{ $batch->salon_name }}</p>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <button onclick='openViewModal(@json($batch))' class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        <i class="ri-eye-line ml-1"></i>
                                        مشاهده
                                    </button>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <p class="text-gray-900 whitespace-no-wrap">{{ $batch->recipients_count }}</p>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    @if ($batch->approval_status == 'pending')
                                        <span class="relative inline-block px-3 py-1 font-semibold text-yellow-900 leading-tight">
                                            <span aria-hidden class="absolute inset-0 bg-yellow-200 opacity-50 rounded-full"></span>
                                            <span class="relative">در انتظار تایید</span>
                                        </span>
                                    @elseif ($batch->approval_status == 'approved')
                                        <span class="relative inline-block px-3 py-1 font-semibold text-green-900 leading-tight">
                                            <span aria-hidden class="absolute inset-0 bg-green-200 opacity-50 rounded-full"></span>
                                            <span class="relative">تایید شده</span>
                                        </span>
                                        @if($batch->approved_by)
                                            <p class="text-xs text-gray-500 mt-1">توسط: {{ $batch->approved_by->name }}</p>
                                            <p class="text-xs text-gray-500">{{ \Morilog\Jalali\Jalalian::fromCarbon($batch->approved_at)->format('Y/m/d H:i') }}</p>
                                        @endif
                                    @elseif ($batch->approval_status == 'rejected')
                                        <span class="relative inline-block px-3 py-1 font-semibold text-red-900 leading-tight">
                                            <span aria-hidden="true" class="absolute inset-0 bg-red-200 opacity-50 rounded-full"></span>
                                            <span class="relative">رد شده</span>
                                        </span>
                                        @if($batch->approved_by)
                                            <p class="text-xs text-gray-500 mt-1">توسط: {{ $batch->approved_by->name }}</p>
                                            <p class="text-xs text-gray-500">{{ \Morilog\Jalali\Jalalian::fromCarbon($batch->approved_at)->format('Y/m/d H:i') }}</p>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    @if ($batch->approval_status == 'approved')
                                        @if($batch->successful_sends > 0 || $batch->failed_sends > 0)
                                            <span class="relative inline-block px-3 py-1 font-semibold text-green-900 leading-tight">
                                                <span aria-hidden class="absolute inset-0 bg-green-200 opacity-50 rounded-full"></span>
                                                <span class="relative">ارسال شده</span>
                                            </span>
                                            <div class="text-xs text-gray-500 mt-1">
                                                <p class="text-green-600">موفق: {{ $batch->successful_sends }}</p>
                                                @if($batch->failed_sends > 0)
                                                    <p class="text-red-600">ناموفق: {{ $batch->failed_sends }}</p>
                                                @endif
                                                @if($batch->pending_sends > 0)
                                                    <p class="text-yellow-600">در صف: {{ $batch->pending_sends }}</p>
                                                @endif
                                            </div>
                                        @elseif($batch->pending_sends > 0)
                                            <span class="relative inline-block px-3 py-1 font-semibold text-blue-900 leading-tight">
                                                <span aria-hidden class="absolute inset-0 bg-blue-200 opacity-50 rounded-full"></span>
                                                <span class="relative">در انتظار ارسال</span>
                                            </span>
                                        @else
                                            <span class="relative inline-block px-3 py-1 font-semibold text-gray-900 leading-tight">
                                                <span aria-hidden class="absolute inset-0 bg-gray-200 opacity-50 rounded-full"></span>
                                                <span class="relative">نامشخص</span>
                                            </span>
                                        @endif
                                    @elseif ($batch->approval_status == 'rejected')
                                        <span class="relative inline-block px-3 py-1 font-semibold text-red-900 leading-tight">
                                            <span aria-hidden="true" class="absolute inset-0 bg-red-200 opacity-50 rounded-full"></span>
                                            <span class="relative">رد شده</span>
                                        </span>
                                    @else
                                        <span class="relative inline-block px-3 py-1 font-semibold text-yellow-900 leading-tight">
                                            <span aria-hidden class="absolute inset-0 bg-yellow-200 opacity-50 rounded-full"></span>
                                            <span class="relative">در انتظار تایید</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <p class="text-gray-900 whitespace-no-wrap">
                                        {{ \Morilog\Jalali\Jalalian::fromCarbon($batch->created_at)->format('Y/m/d H:i') }}
                                    </p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">
                                    هیچ گزارشی برای نمایش وجود ندارد.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-5 py-5 bg-white border-t flex flex-col xs:flex-row items-center xs:justify-between">
                    {{ $smsBatches->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="viewMessageModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeViewModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                    <i class="ri-file-text-line text-2xl text-indigo-600"></i>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:mr-4 sm:text-right w-full">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        مشاهده متن پیام
                    </h3>
                </div>
            </div>
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="w-full">
                        <div class="space-y-4">
                            <div id="editedContentContainer" class="hidden">
                                <p class="text-sm font-bold text-gray-700">متن ارسال شده:</p>
                                <p id="editedContent" class="text-sm text-gray-600 mt-1 p-3 bg-gray-100 rounded-md whitespace-pre-wrap"></p>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-700">متن اصلی:</p>
                                <p id="originalContent" class="text-sm text-gray-600 mt-1 p-3 bg-gray-100 rounded-md whitespace-pre-wrap"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button onclick="closeViewModal()" type="button" class="btn-secondary w-full sm:w-auto">
                    بستن
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
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
</script>
@endpush
