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
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">لیست پیامک‌های در انتظار تایید</h3>

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

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        سالن
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        فرستنده
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        نوع گیرندگان
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        متن پیام (اصلی / ویرایش شده)
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        تعداد گیرندگان
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        تاریخ درخواست
                                    </th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">اقدامات</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($pendingSmsBatches as $batch)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $batch->salon->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $batch->user->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
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
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="space-y-2">
                                                <div>
                                                    <span class="text-xs font-bold text-gray-700">متن اصلی:</span>
                                                    <p class="text-xs text-gray-800 bg-gray-100 rounded-md p-2 mt-1 whitespace-pre-wrap">{{ $batch->original_content ?? $batch->content }}</p>
                                                </div>
                                                @if(!empty($batch->edited_content) && $batch->edited_content !== $batch->original_content)
                                                <div>
                                                    <span class="text-xs font-bold text-blue-700">متن ویرایش‌شده:</span>
                                                    <p class="text-xs text-blue-800 bg-blue-50 rounded-md p-2 mt-1 whitespace-pre-wrap">{{ $batch->edited_content }}</p>
                                                </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $batch->recipients_count }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ \Morilog\Jalali\Jalalian::fromCarbon($batch->created_at)->format('Y/m/d H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button onclick='openEditModal(@json($batch))' class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mb-2">
                                                ویرایش پیام
                                            </button>
                                            <form id="approveForm_{{ $batch->batch_id }}" action="{{ route('admin.manual_sms.approve', $batch->batch_id) }}" method="POST" class="inline-block mr-2">
                                                @csrf
                                                <input type="hidden" name="edited_message_content" id="approve_message_content_{{ $batch->batch_id }}" value="{{ $batch->display_content }}">
                                                <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                    تایید و ارسال
                                                </button>
                                            </form>
                                            <button onclick="openRejectModal('{{ $batch->batch_id }}')" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                رد
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                            هیچ پیامک در انتظار تاییدی وجود ندارد.
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
            <div class="inline-block align-bottom bg-white rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start w-full">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="ri-pencil-line text-2xl text-blue-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:mr-4 sm:text-right w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                ویرایش پیامک
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    متن پیامک را ویرایش کنید.
                                </p>
                                <textarea id="edit_message_content_textarea" rows="6" class="mt-2 shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-400 rounded-md bg-gray-50"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse w-full justify-between">
                    <button type="button" id="saveEditedContentButton" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        ذخیره ویرایش
                    </button>
                    <button type="button" onclick="closeEditModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
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
            <div class="inline-block w-full align-bottom bg-white rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="rejectForm" method="POST">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 w-full sm:pb-4">
                        <div class="sm:flex sm:items-start w-full">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="ri-close-circle-line text-2xl text-red-600"></i>
                            </div>
                            <div class="mt-3 w-full text-center sm:mt-0 sm:mr-4 sm:text-right w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    رد کردن درخواست پیامک
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        لطفا دلیل رد کردن این درخواست را وارد کنید. این دلیل به کاربر نمایش داده خواهد شد.
                                    </p>
                                    <textarea name="rejection_reason" id="rejection_reason" rows="3" class="mt-2 w-full shadow-sm focus:ring-red-500 focus:border-red-500 block w-full sm:text-sm border-gray-300 rounded-md" required></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse w-full justify-between">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            رد کردن
                        </button>
                        <button type="button" onclick="closeRejectModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                            انصراف
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- حذف مودال مشاهده پیام -->

    <script>
        let currentEditingBatchId = null;

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

        function openEditModal(batch) {
            currentEditingBatchId = batch.batch_id;
            const modal = document.getElementById('editMessageModal');
            const textarea = document.getElementById('edit_message_content_textarea');
            textarea.value = batch.display_content;
            modal.classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editMessageModal').classList.add('hidden');
            currentEditingBatchId = null;
        }

        document.getElementById('saveEditedContentButton').addEventListener('click', async () => {
            if (!currentEditingBatchId) return;

            const textarea = document.getElementById('edit_message_content_textarea');
            const editedContent = textarea.value;

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
                    // Update the hidden input for the approve form
                    document.getElementById(`approve_message_content_${currentEditingBatchId}`).value = editedContent;
                    closeEditModal();
                } else {
                    alert('خطا در ذخیره ویرایش: ' + (data.message || 'خطای ناشناخته'));
                }
            } catch (error) {
                console.error('Error saving edited content:', error);
                alert('خطا در ارتباط با سرور برای ذخیره ویرایش.');
            }
        });
    </script>
@endsection
