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
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">لیست کمپین‌های در انتظار تایید</h3>

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
                                        متن پیام
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        تعداد مشتریان
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        هزینه کل
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
                                @forelse ($pendingCampaigns as $campaign)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $campaign->salon->name ?? 'نامشخص' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $campaign->user->name ?? 'نامشخص' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="space-y-2">
                                                <div>
                                                    <span class="text-xs font-bold text-gray-700">متن اصلی:</span>
                                                    {{-- show stored message first, fallback to linked template text if available --}}
                                                    <p class="text-xs text-gray-800 bg-gray-100 rounded-md p-2 mt-1 whitespace-pre-wrap">{{ $campaign->message ?: ($campaign->smsTemplate->template ?? 'بدون متن') }}</p>
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
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $campaign->customer_count }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $campaign->total_cost }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ \Morilog\Jalali\Jalalian::fromCarbon($campaign->created_at)->format('Y/m/d H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button onclick='openEditModal(@json($campaign))' class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mb-2">
                                                ویرایش پیام
                                            </button>
                                            <br>
                                            <button onclick="approveCampaign({{ $campaign->id }})" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 ml-2">
                                                تایید و ارسال
                                            </button>
                                            <button onclick="openRejectModal({{ $campaign->id }})" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                رد
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
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
                                    <textarea id="editMessageContent" rows="6" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 mt-1 block w-full sm:text-sm border-gray-300 rounded-md" placeholder="متن پیام خود را وارد کنید..."></textarea>
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

<script>
    let currentEditingCampaignId = null;
    let currentRejectingCampaignId = null;

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
        document.getElementById('editMessageContent').value = campaign.message;
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
    document.getElementById('editForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const message = document.getElementById('editMessageContent').value;
        
        fetch(`/admin/sms-campaign-approval/${currentEditingCampaignId}/update-content`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
            },
            body: JSON.stringify({ edited_message: message })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeEditModal();
                location.reload();
            } else {
                alert('خطا: ' + (data.message || 'خطای نامشخص'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('خطا در ویرایش پیام');
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
