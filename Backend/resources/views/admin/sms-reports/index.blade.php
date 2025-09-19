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
                                    <div class="space-y-2">
                                        <div>
                                            <span class="text-xs font-bold text-gray-700">متن اصلی:</span>
                                            <p class="text-xs text-gray-800 bg-gray-100 rounded-md p-2 mt-1 whitespace-pre-wrap">{{ $batch->original_content ?? $batch->content }}</p>
                                        </div>
                                        @if(!empty($batch->edited_content) && $batch->edited_content !== $batch->original_content)
                                        <div>
                                            <span class="text-xs font-bold text-blue-700">متن ارسال شده:</span>
                                            <p class="text-xs text-blue-800 bg-blue-50 rounded-md p-2 mt-1 whitespace-pre-wrap">{{ $batch->edited_content }}</p>
                                        </div>
                                        @endif
                                    </div>
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
