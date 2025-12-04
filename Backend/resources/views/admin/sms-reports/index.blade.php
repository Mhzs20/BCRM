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
        <!-- Mobile Card View -->
        <div class="block lg:hidden space-y-4 mt-4">
            @forelse ($smsBatches as $batch)
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $batch->salon_name }}</p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-orange-100 text-orange-800" title="مجموع پارت‌های کسر شده">
                            {{ ($batch->recipients_count ?? 0) }}
                        </span>
                    </div>
                    <div class="px-4 py-3 space-y-3">
                        <div class="flex items-start">
                            <span class="text-xs font-medium text-gray-500 w-24 flex-shrink-0">وضعیت تایید:</span>
                            <span class="text-xs text-gray-900">{{ $batch->approval_status }}</span>
                        </div>
                        <div>
                            <span class="text-xs font-medium text-gray-500">متن پیام:</span>
                            <div class="mt-2 space-y-2">
                                <div>
                                    <span class="text-xs font-bold text-gray-700">متن اصلی:</span>
                                    <p class="text-xs text-gray-800 bg-gray-100 rounded-md p-2 mt-1 whitespace-pre-wrap break-words">{{ $batch->original_content ?? $batch->content }}</p>
                                </div>
                                @if(!empty($batch->edited_content) && $batch->edited_content !== $batch->original_content)
                                <div>
                                    <span class="text-xs font-bold text-blue-700">متن ارسال شده:</span>
                                    <p class="text-xs text-blue-800 bg-blue-50 rounded-md p-2 mt-1 whitespace-pre-wrap break-words">{{ $batch->edited_content }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-start">
                            <span class="text-xs font-medium text-gray-500 w-24 flex-shrink-0">تاریخ:</span>
                            <span class="text-xs text-gray-900">{{ \Morilog\Jalali\Jalalian::fromCarbon($batch->created_at)->format('Y/m/d H:i') }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-gray-500 text-sm">هیچ گزارشی برای نمایش وجود ندارد.</div>
            @endforelse
        </div>

        <!-- Desktop Table View -->
        <div class="hidden lg:block -mx-4 sm:-mx-8 px-4 sm:px-8 py-4 overflow-x-auto">
            <div class="inline-block min-w-full shadow rounded-lg overflow-hidden">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 border-b-2 border-gray-200 bg-gray-100 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                سالن
                            </th>
                            <th class="px-4 py-3 border-b-2 border-gray-200 bg-gray-100 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                محتوای پیام (اصلی / ارسال شده)
                            </th>
                            <th class="px-4 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                تعداد گیرندگان
                            </th>
                            <th class="px-4 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                شارژ کسر شده
                            </th>
                            <th class="px-4 py-3 border-b-2 border-gray-200 bg-gray-100 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                وضعیت تایید / ارسال
                            </th>
                            <th class="px-4 py-3 border-b-2 border-gray-200 bg-gray-100 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">
                                تاریخ درخواست
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($smsBatches as $batch)
                            <tr>
                                <td class="px-4 py-3 border-b border-gray-200 bg-white text-sm">
                                    <p class="text-gray-900 whitespace-no-wrap">{{ $batch->salon_name }}</p>
                                </td>
                                <td class="px-4 py-3 border-b border-gray-200 bg-white text-sm">
                                    <div class="space-y-2">
                                        <div>
                                            <span class="text-xs font-bold text-gray-700">متن اصلی:</span>
                                            <p class="text-xs text-gray-800 bg-gray-100 rounded-md p-2 mt-1 whitespace-pre-wrap max-w-xs">{{ $batch->original_content ?? $batch->content }}</p>
                                        </div>
                                        @if(!empty($batch->edited_content) && $batch->edited_content !== $batch->original_content)
                                        <div>
                                            <span class="text-xs font-bold text-blue-700">متن ارسال شده:</span>
                                            <p class="text-xs text-blue-800 bg-blue-50 rounded-md p-2 mt-1 whitespace-pre-wrap max-w-xs">{{ $batch->edited_content }}</p>
                                        </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 border-b border-gray-200 bg-white text-sm text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">{{ $batch->recipients_count }}</span>
                                </td>
                                <td class="px-4 py-3 border-b border-gray-200 bg-white text-sm text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-800">{{ ($batch->recipients_count ?? 0) }}</span>
                                </td>
                                <td class="px-4 py-3 border-b border-gray-200 bg-white text-sm">
                                    <div class="space-y-3">
                                        <!-- وضعیت تایید -->
                                        <div>
                                            <span class="text-xs font-bold text-gray-700">وضعیت تایید:</span>
                                            <div class="mt-1">
                                                @if ($batch->approval_status == 'pending')
                                                    <span class="relative inline-block px-2 py-1 font-semibold text-yellow-900 leading-tight text-xs">
                                                        <span aria-hidden class="absolute inset-0 bg-yellow-200 opacity-50 rounded-full"></span>
                                                        <span class="relative">در انتظار تایید</span>
                                                    </span>
                                                @elseif ($batch->approval_status == 'approved')
                                                    <span class="relative inline-block px-2 py-1 font-semibold text-green-900 leading-tight text-xs">
                                                        <span aria-hidden class="absolute inset-0 bg-green-200 opacity-50 rounded-full"></span>
                                                        <span class="relative">تایید شده</span>
                                                    </span>
                                                    @if($batch->approved_by && isset($batch->approved_by->name))
                                                        <p class="text-xs text-gray-500 mt-1">توسط: {{ $batch->approved_by->name }}</p>
                                                        <p class="text-xs text-gray-500">{{ \Morilog\Jalali\Jalalian::fromCarbon($batch->approved_at)->format('Y/m/d H:i') }}</p>
                                                    @endif
                                                @elseif ($batch->approval_status == 'rejected')
                                                    <span class="relative inline-block px-2 py-1 font-semibold text-red-900 leading-tight text-xs">
                                                        <span aria-hidden="true" class="absolute inset-0 bg-red-200 opacity-50 rounded-full"></span>
                                                        <span class="relative">رد شده</span>
                                                    </span>
                                                    @if($batch->approved_by && isset($batch->approved_by->name))
                                                        <p class="text-xs text-gray-500 mt-1">توسط: {{ $batch->approved_by->name }}</p>
                                                        <p class="text-xs text-gray-500">{{ \Morilog\Jalali\Jalalian::fromCarbon($batch->approved_at)->format('Y/m/d H:i') }}</p>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>

                                        <!-- وضعیت ارسال -->
                                        <div>
                                            <span class="text-xs font-bold text-gray-700">وضعیت ارسال:</span>
                                            <div class="mt-1">
                                                @if ($batch->approval_status == 'approved')
                                                    @if($batch->successful_sends > 0 || $batch->failed_sends > 0)
                                                        <span class="relative inline-block px-2 py-1 font-semibold text-green-900 leading-tight text-xs">
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
                                                        <span class="relative inline-block px-2 py-1 font-semibold text-blue-900 leading-tight text-xs">
                                                            <span aria-hidden class="absolute inset-0 bg-blue-200 opacity-50 rounded-full"></span>
                                                            <span class="relative">در انتظار ارسال</span>
                                                        </span>
                                                    @else
                                                        <span class="relative inline-block px-2 py-1 font-semibold text-gray-900 leading-tight text-xs">
                                                            <span aria-hidden class="absolute inset-0 bg-gray-200 opacity-50 rounded-full"></span>
                                                            <span class="relative">نامشخص</span>
                                                        </span>
                                                    @endif
                                                @elseif ($batch->approval_status == 'rejected')
                                                    <span class="relative inline-block px-2 py-1 font-semibold text-red-900 leading-tight text-xs">
                                                        <span aria-hidden="true" class="absolute inset-0 bg-red-200 opacity-50 rounded-full"></span>
                                                        <span class="relative">رد شده</span>
                                                    </span>
                                                @else
                                                    <span class="relative inline-block px-2 py-1 font-semibold text-yellow-900 leading-tight text-xs">
                                                        <span aria-hidden class="absolute inset-0 bg-yellow-200 opacity-50 rounded-full"></span>
                                                        <span class="relative">در انتظار تایید</span>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 border-b border-gray-200 bg-white text-sm">
                                    <p class="text-gray-900 whitespace-no-wrap">
                                        {{ \Morilog\Jalali\Jalalian::fromCarbon($batch->created_at)->format('Y/m/d H:i') }}
                                    </p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">
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

<!-- حذف مودال مشاهده پیام -->
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
