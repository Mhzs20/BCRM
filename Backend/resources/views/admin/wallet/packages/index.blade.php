@extends('admin.layouts.app')

@section('title', 'مدیریت پکیج‌های شارژ کیف پول')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        مدیریت پکیج‌های شارژ کیف پول
    </h2>
@endsection

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Add Package Button -->
        <div class="mb-6">
            <button type="button" class="btn-primary bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md inline-flex items-center" onclick="openModal('addPackageModal')">
                <i class="ri-add-line mr-2"></i>
                افزودن پکیج جدید
            </button>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="ri-archive-line text-2xl text-indigo-600"></i>
                        </div>
                        <div class="mr-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">کل پکیج‌ها</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $totalPackages }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="ri-checkbox-circle-line text-2xl text-green-600"></i>
                        </div>
                        <div class="mr-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">پکیج‌های فعال</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $activePackages }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="ri-shopping-cart-line text-2xl text-blue-600"></i>
                        </div>
                        <div class="mr-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">کل فروش</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($totalSales) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="ri-money-dollar-circle-line text-2xl text-yellow-600"></i>
                        </div>
                        <div class="mr-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">کل درآمد</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($totalRevenue / 10) }} تومان</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Packages Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">لیست پکیج‌های شارژ کیف پول</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عنوان</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">مبلغ شارژ</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">قیمت</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تخفیف</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">قیمت نهایی</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($packages as $package)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $package->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900">{{ $package->title }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $package->formatted_amount }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $package->formatted_price }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($package->discount_percentage > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ $package->discount_percentage }}%
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $package->formatted_final_price }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2 space-x-reverse">
                                    <button type="button" class="edit-package text-indigo-600 hover:text-indigo-900" 
                                            data-id="{{ $package->id }}" onclick="editPackage({{ $package->id }})">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <button type="button" class="delete-package text-red-600 hover:text-red-900" 
                                            data-id="{{ $package->id }}" onclick="deletePackage({{ $package->id }})">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <i class="ri-inbox-line text-4xl text-gray-300 mb-4"></i>
                                    <p>هیچ پکیجی یافت نشد</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Package Modal -->
<div id="addPackageModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">افزودن پکیج جدید</h3>
                <button type="button" class="close-modal text-gray-400 hover:text-gray-600">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>
            <form id="addPackageForm" action="{{ route('admin.wallet.packages.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">عنوان پکیج *</label>
                        <input type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500" name="title" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">مبلغ شارژ (تومان) *</label>
                        <input type="number" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500" name="amount" min="1000" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">قیمت (تومان) *</label>
                        <input type="number" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500" name="price" min="1000" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">درصد تخفیف</label>
                        <input type="number" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500" name="discount_percentage" min="0" max="100" value="0">
                    </div>
                    <div class="flex items-center">
                        <label class="flex items-center">
                            <input type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" name="is_active" value="1" checked>
                            <span class="mr-2 text-sm text-gray-600">فعال</span>
                        </label>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">توضیحات</label>
                    <textarea class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500" name="description" rows="3"></textarea>
                </div>
                <div class="flex justify-end mt-6 space-x-2 space-x-reverse">
                    <button type="button" class="close-modal px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 border border-gray-300 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">انصراف</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">ذخیره</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Package Modal -->
<div id="editPackageModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">ویرایش پکیج</h3>
                <button type="button" class="close-modal text-gray-400 hover:text-gray-600">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>
            <form id="editPackageForm" method="POST">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">عنوان پکیج *</label>
                        <input type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500" name="title" id="edit_title" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">مبلغ شارژ (تومان) *</label>
                        <input type="number" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500" name="amount" id="edit_amount" min="1000" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">قیمت (تومان) *</label>
                        <input type="number" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500" name="price" id="edit_price" min="1000" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">درصد تخفیف</label>
                        <input type="number" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500" name="discount_percentage" id="edit_discount_percentage" min="0" max="100">
                    </div>
                    <div class="flex items-center">
                        <label class="flex items-center">
                            <input type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" name="is_active" id="edit_is_active" value="1">
                            <span class="mr-2 text-sm text-gray-600">فعال</span>
                        </label>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">توضیحات</label>
                    <textarea class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500" name="description" id="edit_description" rows="3"></textarea>
                </div>
                <div class="flex justify-end mt-6 space-x-2 space-x-reverse">
                    <button type="button" class="close-modal px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 border border-gray-300 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">انصراف</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">به‌روزرسانی</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal functions
    window.openModal = function(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    };

    // Close modal functions
    document.querySelectorAll('.close-modal').forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.fixed');
            modal.classList.add('hidden');
        });
    });

    // Close modal on outside click
    document.querySelectorAll('[id$="Modal"]').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    });

    // Edit package
    window.editPackage = function(packageId) {
        fetch(`{{ url('admin/wallet/packages') }}/${packageId}`)
        .then(response => response.json())
        .then(package => {
            document.getElementById('editPackageForm').action = `{{ url('admin/wallet/packages') }}/${packageId}`;
            document.getElementById('edit_title').value = package.title;
            document.getElementById('edit_amount').value = package.amount / 10; // Convert Rial to Toman
            document.getElementById('edit_price').value = package.price / 10; // Convert Rial to Toman
            document.getElementById('edit_discount_percentage').value = package.discount_percentage;
            document.getElementById('edit_is_active').checked = package.is_active;
            document.getElementById('edit_description').value = package.description;
            
            openModal('editPackageModal');
        })
        .catch(error => {
            alert('خطا در دریافت اطلاعات پکیج');
            console.error(error);
        });
    };

    // Delete package
    window.deletePackage = function(packageId) {
        if (confirm('آیا از حذف این پکیج اطمینان دارید؟')) {
            fetch(`{{ url('admin/wallet/packages') }}/${packageId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'خطا در حذف پکیج');
                }
            })
            .catch(error => {
                alert('خطا در حذف پکیج');
                console.error(error);
            });
        }
    };

    // Form submissions
    document.getElementById('addPackageForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'در حال پردازش...';
        
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                document.getElementById('addPackageModal').classList.add('hidden');
                this.reset();
                location.reload();
            } else {
                alert(data.message || 'خطا در ذخیره اطلاعات');
                console.error('Validation errors:', data.errors);
            }
        })
        .catch(error => {
            alert('خطا در ذخیره اطلاعات: ' + error.message);
            console.error('Error:', error);
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });

    document.getElementById('editPackageForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'در حال پردازش...';
        
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                document.getElementById('editPackageModal').classList.add('hidden');
                location.reload();
            } else {
                alert(data.message || 'خطا در به‌روزرسانی اطلاعات');
                console.error('Validation errors:', data.errors);
            }
        })
        .catch(error => {
            alert('خطا در به‌روزرسانی اطلاعات: ' + error.message);
            console.error('Error:', error);
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });
});
</script>
@endsection