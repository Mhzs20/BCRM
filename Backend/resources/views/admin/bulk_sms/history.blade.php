@extends('admin.layouts.app')

@section('title', 'تاریخچه ارسال پیامک گروهی')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-8">
    <h1 class="text-3xl font-extrabold text-gray-900 text-center font-sans">تاریخچه ارسال پیامک گروهی</h1>
        <a href="{{ route('admin.bulk-sms.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <i class="ri-arrow-right-line ml-2"></i> بازگشت به ارسال پیامک
        </a>
    </div>

    <div class="bg-white shadow-xl rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        تاریخ ارسال
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        متن پیامک
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        تعداد کل
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        موفق
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        ناموفق
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        عملیات
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($paginatedTransactions as $group)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ \Morilog\Jalali\Jalalian::fromCarbon($group->created_at)->format('Y/m/d H:i') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <button 
                                type="button" 
                                class="view-message-btn inline-flex items-center px-2.5 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                data-message="{{ $group->content }}">
                                <i class="ri-eye-line ml-1"></i> مشاهده متن
                            </button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $group->total_count }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                            {{ $group->success_count }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                            {{ $group->failed_count }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <button 
                                type="button" 
                                class="recipients-btn-simple inline-flex items-center px-2.5 py-1.5 border border-blue-300 shadow-sm text-xs font-medium rounded text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                data-group-index="{{ $loop->index }}">
                                <i class="ri-group-line ml-1"></i> لیست دریافت کنندگان
                            </button>
                        </td>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                            هیچ سابقه‌ای یافت نشد.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($paginatedTransactions->hasPages())
            <div class="p-4">
                {{ $paginatedTransactions->links() }}
            </div>
        @endif
    </div>
</div>

<!-- View Message Modal -->
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
                        مشاهده متن پیامک
                    </h3>
                </div>
            </div>
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="w-full">
                        <div>
                            <p class="text-sm font-bold text-gray-700">متن پیامک:</p>
                            <p id="messageContent" class="text-sm text-gray-600 mt-1 p-3 bg-gray-100 rounded-md whitespace-pre-wrap"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button onclick="closeViewModal()" type="button" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm">
                    بستن
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Recipients Modal -->
<div id="recipientsModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="recipients-modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4 text-center">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeRecipientsModal()"></div>
        
        <!-- Modal Content Container -->
        <div class="inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all my-8 align-middle max-w-3xl w-full">
            <!-- Header -->
            <div class="bg-gray-50 px-4 py-3 sm:px-6 flex justify-between items-center border-b">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-full bg-blue-100">
                        <i class="ri-group-line text-xl text-blue-600"></i>
                    </div>
                    <h3 id="recipients-modal-title" class="text-lg leading-6 font-medium text-gray-900 mr-3">لیست دریافت کنندگان پیامک</h3>
                </div>
                <button onclick="closeRecipientsModal()" class="text-gray-400 hover:text-gray-600 transition-colors duration-200">
                    <i class="ri-close-line text-2xl"></i>
                </button>
            </div>
            
            <!-- Content -->
            <div class="p-6" style="max-height: 60vh; overflow-y: auto;">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 table-fixed">
                        <colgroup>
                            <col style="width: 30%;">
                            <col style="width: 25%;">
                            <col style="width: 25%;">
                            <col style="width: 20%;">
                        </colgroup>
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">نام سالن</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">مالک</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">شماره موبایل</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">وضعیت</th>
                            </tr>
                        </thead>
                        <tbody id="recipientsTableBody" class="bg-white divide-y divide-gray-200">
                            <!-- Recipients will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="bg-gray-50 px-4 py-3 sm:px-6 flex justify-start border-t">
                <button onclick="closeRecipientsModal()" type="button" class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 w-auto sm:text-sm">
                    بستن
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    /* Modal backdrop and animation styles */
    .modal-open {
        overflow: hidden !important;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // View message modal handlers
        document.querySelectorAll('.view-message-btn').forEach(button => {
            button.addEventListener('click', function() {
                const message = this.getAttribute('data-message');
                openViewModal(message);
            });
        });

        // Recipients modal handlers
        document.querySelectorAll('.recipients-btn-simple').forEach((button, index) => {
            button.addEventListener('click', function() {
                const groupIndex = parseInt(this.getAttribute('data-group-index'));
                
                // Get the paginated data
                window.paginatedData = window.paginatedData || @json($paginatedTransactions->items());
                
                if (groupIndex >= 0 && groupIndex < window.paginatedData.length) {
                    const group = window.paginatedData[groupIndex];
                    
                    if (group && group.transactions) {
                        const recipients = group.transactions.map(function(t) {
                            return {
                                'salon_name': (t.salon && t.salon.name) ? t.salon.name : 'نامشخص',
                                'owner_name': (t.salon && t.salon.owner && t.salon.owner.name) ? t.salon.owner.name : 'نامشخص',
                                'mobile': t.receptor || 'نامشخص',
                                'status': t.status || 'نامشخص'
                            };
                        });
                        openRecipientsModal(recipients);
                    }
                }
            });
        });
    });

    function openViewModal(content) {
        try {
            document.getElementById('messageContent').textContent = content || '';
            document.getElementById('viewMessageModal').classList.remove('hidden');
        } catch (error) {
            console.error('Error opening view modal:', error);
        }
    }

    function closeViewModal() {
        try {
            document.getElementById('viewMessageModal').classList.add('hidden');
        } catch (error) {
            console.error('Error closing view modal:', error);
        }
    }

    function openRecipientsModal(recipients) {
        const modal = document.getElementById('recipientsModal');
        const tbody = document.getElementById('recipientsTableBody');
        
        if (!modal || !tbody) return;
        
        // Clear table
        tbody.innerHTML = '';
        
        // Add recipients
        if (recipients && recipients.length > 0) {
            recipients.forEach(recipient => {
                const tr = document.createElement('tr');
                const statusHtml = recipient.status === 'delivered' 
                    ? '<span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">✓ ارسال شده</span>'
                    : '<span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs">✗ ناموفق</span>';
                
                tr.innerHTML = `
                    <td class="px-4 py-2 text-sm text-start">${recipient.salon_name || 'نامشخص'}</td>
                    <td class="px-4 py-2 text-sm text-start">${recipient.owner_name || 'نامشخص'}</td>
                    <td class="px-4 py-2 text-sm text-start">${recipient.mobile || 'نامشخص'}</td>
                    <td class="px-4 py-2 text-sm text-start">${statusHtml}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-2 text-center text-gray-500">هیچ داده‌ای یافت نشد</td></tr>';
        }
        
        // Show modal
        modal.classList.remove('hidden');
        
        // Lock body scroll
        document.body.style.overflow = 'hidden';
        document.body.classList.add('modal-open');
        
        // Focus management
        setTimeout(() => {
            const closeButton = modal.querySelector('button');
            if (closeButton) {
                closeButton.focus();
            }
        }, 100);
    }

    function closeRecipientsModal() {
        const modal = document.getElementById('recipientsModal');
        if (modal) {
            modal.classList.add('hidden');
            
            // Restore body scroll
            document.body.style.overflow = '';
            document.body.classList.remove('modal-open');
        }
    }

    function escapeHtml(text) {
        if (typeof text !== 'string') return text;
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Close modals when clicking outside
    document.addEventListener('click', function(event) {
        if (event.target && event.target.id === 'viewMessageModal') {
            closeViewModal();
        }
        if (event.target && (event.target.id === 'recipientsModal' || event.target.classList.contains('bg-gray-500'))) {
            closeRecipientsModal();
        }
    });

    // Close modals with ESC key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeViewModal();
            closeRecipientsModal();
        }
        
        // Trap focus in modal when it's open
        if (!document.getElementById('recipientsModal').classList.contains('hidden')) {
            const modal = document.getElementById('recipientsModal');
            const focusableElements = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];
            
            if (event.key === 'Tab') {
                if (event.shiftKey) {
                    if (document.activeElement === firstElement) {
                        event.preventDefault();
                        lastElement.focus();
                    }
                } else {
                    if (document.activeElement === lastElement) {
                        event.preventDefault();
                        firstElement.focus();
                    }
                }
            }
        }
    });
</script>
@endpush
