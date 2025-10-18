@extends('admin.layouts.app')

@section('title', 'شارژ کیف پول کاربران')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="p-6">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">شارژ کیف پول کاربران</h1>
            <p class="text-gray-600">مدیریت شارژ کیف پول کاربران از طریق درگاه پرداخت</p>
        </div>

        <!-- Search Section -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">جستجوی کاربر</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">نام کاربر</label>
                    <input type="text" id="searchName" placeholder="نام کاربر" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">شماره موبایل</label>
                    <input type="text" id="searchMobile" placeholder="09123456789" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-end">
                    <button onclick="searchUsers()" 
                            class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        جستجو
                    </button>
                </div>
            </div>
        </div>

        <!-- Users List -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold">لیست کاربران</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">کاربر</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">موبایل</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">موجودی فعلی</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody" class="bg-white divide-y divide-gray-200">
                        <!-- Users will be loaded here -->
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-3 border-t border-gray-200" id="pagination">
                <!-- Pagination will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Charge Wallet Modal -->
<div id="chargeModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">شارژ کیف پول</h3>
            <form id="chargeForm">
                <input type="hidden" id="selectedUserId" value="">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">نام کاربر</label>
                    <input type="text" id="selectedUserName" readonly 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">مبلغ شارژ (ریال)</label>
                    <input type="number" id="chargeAmount" min="10000" max="50000000" 
                           placeholder="مثال: 100000" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">حداقل: 10,000 ریال - حداکثر: 50,000,000 ریال</p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">توضیحات</label>
                    <textarea id="chargeDescription" rows="3" 
                              placeholder="توضیحات شارژ (اختیاری)"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <div class="flex justify-end space-x-3 space-x-reverse">
                    <button type="button" onclick="closeChargeModal()" 
                            class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">
                        انصراف
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        ایجاد درخواست شارژ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let currentSearch = {};

// Load users on page load
document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
});

function searchUsers() {
    currentSearch = {
        name: document.getElementById('searchName').value,
        mobile: document.getElementById('searchMobile').value
    };
    currentPage = 1;
    loadUsers();
}

function loadUsers(page = 1) {
    currentPage = page;
    
    let params = new URLSearchParams({
        page: page,
        per_page: 20,
        ...currentSearch
    });
    
    fetch(`/api/admin/referral/users?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayUsers(data.data.data);
                displayPagination(data.data);
            } else {
                console.error('Error loading users:', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function displayUsers(users) {
    const tbody = document.getElementById('usersTableBody');
    tbody.innerHTML = '';
    
    users.forEach(user => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${user.name}</div>
                <div class="text-sm text-gray-500">${user.business_name || 'نامشخص'}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${user.mobile}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-lg font-semibold text-green-600">
                    ${Number(user.wallet_balance || 0).toLocaleString() / 10} تومان
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button onclick="openChargeModal(${user.id}, '${user.name}')" 
                        class="bg-blue-600 text-white px-3 py-1 rounded-md hover:bg-blue-700 transition-colors">
                    شارژ کیف پول
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function displayPagination(paginationData) {
    const paginationDiv = document.getElementById('pagination');
    paginationDiv.innerHTML = '';
    
    if (paginationData.last_page > 1) {
        let paginationHTML = '<div class="flex justify-between items-center">';
        
        // Previous button
        if (paginationData.current_page > 1) {
            paginationHTML += `<button onclick="loadUsers(${paginationData.current_page - 1})" 
                                      class="bg-gray-300 text-gray-700 px-3 py-1 rounded-md hover:bg-gray-400">قبلی</button>`;
        } else {
            paginationHTML += '<span></span>';
        }
        
        // Page info
        paginationHTML += `<span class="text-sm text-gray-600">
                            صفحه ${paginationData.current_page} از ${paginationData.last_page}
                          </span>`;
        
        // Next button
        if (paginationData.current_page < paginationData.last_page) {
            paginationHTML += `<button onclick="loadUsers(${paginationData.current_page + 1})" 
                                      class="bg-gray-300 text-gray-700 px-3 py-1 rounded-md hover:bg-gray-400">بعدی</button>`;
        } else {
            paginationHTML += '<span></span>';
        }
        
        paginationHTML += '</div>';
        paginationDiv.innerHTML = paginationHTML;
    }
}

function openChargeModal(userId, userName) {
    document.getElementById('selectedUserId').value = userId;
    document.getElementById('selectedUserName').value = userName;
    document.getElementById('chargeAmount').value = '';
    document.getElementById('chargeDescription').value = '';
    document.getElementById('chargeModal').classList.remove('hidden');
}

function closeChargeModal() {
    document.getElementById('chargeModal').classList.add('hidden');
}

document.getElementById('chargeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const userId = document.getElementById('selectedUserId').value;
    const amount = document.getElementById('chargeAmount').value;
    const description = document.getElementById('chargeDescription').value;
    
    if (!amount || amount < 10000) {
        alert('مبلغ شارژ باید حداقل 10,000 ریال باشد');
        return;
    }
    
    // Create charge request
    fetch('/api/admin/wallet/create-charge', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            user_id: userId,
            amount: amount,
            description: description || 'شارژ کیف پول توسط مدیر'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('درخواست شارژ با موفقیت ایجاد شد');
            closeChargeModal();
            loadUsers(currentPage); // Reload current page
            
            // Redirect to payment gateway if needed
            if (data.payment_url) {
                window.open(data.payment_url, '_blank');
            }
        } else {
            alert('خطا در ایجاد درخواست شارژ: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('خطا در ارسال درخواست');
    });
});

// Close modal when clicking outside
document.getElementById('chargeModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeChargeModal();
    }
});
</script>
@endsection