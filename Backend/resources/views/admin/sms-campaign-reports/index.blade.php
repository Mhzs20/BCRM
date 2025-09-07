@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-4 sm:px-8">
    <div class="py-8">
        <div>
            <h2 class="text-2xl font-semibold leading-tight">گزارش کمپین‌های پیامکی</h2>
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
                                فرستنده
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                محتوای پیام
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                تعداد مشتریان
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                هزینه کل
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
                        @forelse ($campaigns as $campaign)
                            <tr>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <p class="text-gray-900 whitespace-no-wrap">{{ $campaign->salon->name ?? 'نامشخص' }}</p>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <p class="text-gray-900 whitespace-no-wrap">{{ $campaign->user->name ?? 'نامشخص' }}</p>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <button onclick='openViewModal(@json($campaign))' class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        <i class="ri-eye-line ml-1"></i>
                                        مشاهده
                                    </button>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <p class="text-gray-900 whitespace-no-wrap">{{ $campaign->customer_count }}</p>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <p class="text-gray-900 whitespace-no-wrap">{{ $campaign->total_cost }}</p>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    @php
                                        $approvalStatusClasses = [
                                            'approved' => 'bg-green-100 text-green-800',
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'rejected' => 'bg-red-100 text-red-800'
                                        ];
                                        $approvalStatusTexts = [
                                            'approved' => 'تایید شده',
                                            'pending' => 'در انتظار تایید',
                                            'rejected' => 'رد شده'
                                        ];
                                    @endphp
                                    <span class="relative inline-block px-3 py-1 font-semibold leading-tight {{ $approvalStatusClasses[$campaign->approval_status] ?? 'bg-gray-100 text-gray-800' }}">
                                        <span aria-hidden class="absolute inset-0 opacity-50 rounded-full"></span>
                                        <span class="relative">{{ $approvalStatusTexts[$campaign->approval_status] ?? $campaign->approval_status }}</span>
                                    </span>
                                    @if($campaign->approval_status === 'approved' && $campaign->approver)
                                        <p class="text-xs text-gray-500 mt-1">توسط: {{ $campaign->approver->name }}</p>
                                        <p class="text-xs text-gray-500">{{ \Morilog\Jalali\Jalalian::fromCarbon($campaign->approved_at)->format('Y/m/d H:i') }}</p>
                                    @endif
                                    @if($campaign->approval_status === 'rejected' && $campaign->rejection_reason)
                                        <p class="text-xs text-red-600 mt-1" title="{{ $campaign->rejection_reason }}">
                                            {{ Str::limit($campaign->rejection_reason, 30) }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    @php
                                        $statusClasses = [
                                            'draft' => 'bg-gray-100 text-gray-800',
                                            'pending' => 'bg-blue-100 text-blue-800',
                                            'sending' => 'bg-yellow-100 text-yellow-800',
                                            'completed' => 'bg-green-100 text-green-800',
                                            'failed' => 'bg-red-100 text-red-800'
                                        ];
                                        $statusTexts = [
                                            'draft' => 'پیش‌نویس',
                                            'pending' => 'در انتظار ارسال',
                                            'sending' => 'در حال ارسال',
                                            'completed' => 'ارسال شده',
                                            'failed' => 'ناموفق'
                                        ];
                                    @endphp
                                    <span class="relative inline-block px-3 py-1 font-semibold leading-tight {{ $statusClasses[$campaign->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        <span aria-hidden class="absolute inset-0 opacity-50 rounded-full"></span>
                                        <span class="relative">{{ $statusTexts[$campaign->status] ?? $campaign->status }}</span>
                                    </span>
                                    @if($campaign->status === 'completed')
                                        @php
                                            $sentCount = $campaign->messages->where('status', 'sent')->count();
                                            $failedCount = $campaign->messages->where('status', 'failed')->count();
                                            $totalCount = $campaign->messages->count();
                                        @endphp
                                        <div class="text-xs text-gray-500 mt-1">
                                            <p>ارسال شده: {{ $sentCount }}</p>
                                            @if($failedCount > 0)
                                                <p class="text-red-500">ناموفق: {{ $failedCount }}</p>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <p class="text-gray-900 whitespace-no-wrap">
                                        {{ \Morilog\Jalali\Jalalian::fromCarbon($campaign->created_at)->format('Y/m/d H:i') }}
                                    </p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center text-gray-500">
                                    هیچ کمپینی یافت نشد.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-5 py-5 bg-white border-t flex flex-col xs:flex-row items-center xs:justify-between">
                    <div class="inline-flex mt-2 xs:mt-0">
                        {{ $campaigns->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Message Modal -->
<div id="viewMessageModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeViewModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                    <i class="ri-file-text-line text-2xl text-indigo-600"></i>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:mr-4 sm:text-right w-full">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        جزئیات کمپین پیامکی
                    </h3>
                </div>
            </div>
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="w-full">
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm font-bold text-gray-700">سالن:</p>
                                    <p id="viewSalonName" class="text-sm text-gray-600 mt-1"></p>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-700">فرستنده:</p>
                                    <p id="viewUserName" class="text-sm text-gray-600 mt-1"></p>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-700">تعداد مشتریان:</p>
                                    <p id="viewCustomerCount" class="text-sm text-gray-600 mt-1"></p>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-700">هزینه کل:</p>
                                    <p id="viewTotalCost" class="text-sm text-gray-600 mt-1"></p>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-700">نوع:</p>
                                    <p id="viewType" class="text-sm text-gray-600 mt-1"></p>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-700">وضعیت تایید:</p>
                                    <p id="viewApprovalStatus" class="text-sm text-gray-600 mt-1"></p>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-700">متن پیام:</p>
                                <p id="viewMessageContent" class="text-sm text-gray-600 mt-1 p-3 bg-gray-100 rounded-md whitespace-pre-wrap"></p>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-700">فیلترهای اعمال شده:</p>
                                <div id="viewFilters" class="text-sm text-gray-600 mt-1 p-3 bg-gray-100 rounded-md"></div>
                            </div>
                            <div id="rejectionReasonContainer" class="hidden">
                                <p class="text-sm font-bold text-red-700">دلیل رد:</p>
                                <p id="viewRejectionReason" class="text-sm text-red-600 mt-1 p-3 bg-red-50 rounded-md"></p>
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

<script>
    function openViewModal(campaign) {
        const modal = document.getElementById('viewMessageModal');
        
        document.getElementById('viewSalonName').innerText = campaign.salon?.name || 'نامشخص';
        document.getElementById('viewUserName').innerText = campaign.user?.name || 'نامشخص';
        document.getElementById('viewCustomerCount').innerText = campaign.customer_count;
        document.getElementById('viewTotalCost').innerText = campaign.total_cost;
        document.getElementById('viewType').innerText = campaign.uses_template ? 'قالب از پیش تعریف شده' : 'پیام دستی';
        document.getElementById('viewMessageContent').innerText = campaign.message;

        // Approval status
        const approvalStatusTexts = {
            'approved': 'تایید شده',
            'pending': 'در انتظار تایید',
            'rejected': 'رد شده'
        };
        let approvalStatusText = approvalStatusTexts[campaign.approval_status] || campaign.approval_status;
        if (campaign.approval_status === 'approved' && campaign.approver) {
            approvalStatusText += ` (توسط: ${campaign.approver.name})`;
        }
        document.getElementById('viewApprovalStatus').innerText = approvalStatusText;

        // Display filters in a readable format
        let filtersText = '';
        if (campaign.filters) {
            const filters = campaign.filters;
            
            // Age filter
            if (filters.min_age || filters.max_age) {
                filtersText += `سن: ${filters.min_age || 0} تا ${filters.max_age || 'نامحدود'}\n`;
            }
            
            // Professions - prioritize enhanced data
            if (filters.professions && filters.professions.length > 0) {
                filtersText += `مشاغل: ${filters.professions.map(p => p.name).join(', ')}\n`;
            } else if (filters.profession_id && filters.profession_id.length > 0) {
                filtersText += `شناسه مشاغل: ${filters.profession_id.join(', ')}\n`;
            }
            
            // Customer groups - prioritize enhanced data
            if (filters.customer_groups && filters.customer_groups.length > 0) {
                filtersText += `گروه‌های مشتری: ${filters.customer_groups.map(g => g.name).join(', ')}\n`;
            } else if (filters.customer_group_id && filters.customer_group_id.length > 0) {
                filtersText += `شناسه گروه‌های مشتری: ${filters.customer_group_id.join(', ')}\n`;
            }
            
            // How introduced - prioritize enhanced data
            if (filters.how_introduceds && filters.how_introduceds.length > 0) {
                filtersText += `نحوه آشنایی: ${filters.how_introduceds.map(h => h.name).join(', ')}\n`;
            } else if (filters.how_introduced_id && filters.how_introduced_id.length > 0) {
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
            if (filters.phone_numbers && filters.phone_numbers.length > 0) {
                filtersText += `شماره‌های تلفن: ${filters.phone_numbers.join(', ')}\n`;
            }
        }
        
        document.getElementById('viewFilters').innerText = filtersText || 'فیلتری اعمال نشده';

        // Rejection reason
        const rejectionContainer = document.getElementById('rejectionReasonContainer');
        if (campaign.approval_status === 'rejected' && campaign.rejection_reason) {
            document.getElementById('viewRejectionReason').innerText = campaign.rejection_reason;
            rejectionContainer.classList.remove('hidden');
        } else {
            rejectionContainer.classList.add('hidden');
        }

        modal.classList.remove('hidden');
    }

    function closeViewModal() {
        const modal = document.getElementById('viewMessageModal');
        modal.classList.add('hidden');
    }
</script>
@endsection
