@extends('admin.layouts.app')

@section('title', 'پروفایل سالن: ' . $salon->name)

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-extrabold text-gray-900 mb-8 text-center font-sans">پروفایل سالن: {{ $salon->name }}</h1>

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6 shadow-md" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6 shadow-md" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <div class="bg-white shadow-xl rounded-lg p-8 mb-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 mb-4 border-b-2 border-indigo-500 pb-2">اطلاعات عمومی</h2>
                <div class="space-y-3 text-gray-700">
                    <p><strong>نام سالن:</strong> {{ $salon->name }}</p>
                    <p><strong>نام مالک سالن:</strong> {{ $salon->user->name ?? 'ثبت نشده' }}</p>
                        <p><strong>شماره تلفن مالک سالن:</strong> {{ $salon->user->mobile ?? 'ثبت نشده' }}</p>
                    <p><strong>ایمیل:</strong> {{ $salon->email ?? 'ثبت نشده' }}</p>
                    <p><strong>وضعیت:</strong>
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full {{ $salon->is_active ? 'bg-green-100 text-green-800' : 'bg-red-110 text-red-800' }}">
                            {{ $salon->is_active ? 'فعال' : 'غیرفعال' }}
                        </span>
                    </p>
                    <p><strong>تاریخ ثبت‌نام:</strong> {{ verta($salon->created_at)->format('Y/m/d H:i') }}</p>
                    <p><strong>آخرین ورود:</strong> {{ $salon->user->last_login_at ? verta($salon->user->last_login_at)->format('Y/m/d H:i') : 'ثبت نشده' }}</p>
                    <p><strong>نوع فعالیت:</strong> {{ $salon->businessCategory->name ?? 'ثبت نشده' }}</p>
                    <p><strong>زیرمجموعه فعالیت:</strong>
                        @forelse ($salon->businessSubcategories as $subcategory)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 ml-1">
                                {{ $subcategory->name }}
                            </span>
                        @empty
                            ثبت نشده
                        @endforelse
                    </p>
                    <p><strong>اعتبار پیامک:</strong> {{ $salon->current_sms_balance }}</p>
                </div>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-800 mb-4 border-b-2 border-indigo-500 pb-2">اطلاعات تماس و شبکه‌های اجتماعی</h2>
                <div class="space-y-3 text-gray-700">
                    <p><strong>واتساپ:</strong> {{ $salon->whatsapp ?? 'ثبت نشده' }}</p>
                    <p><strong>تلگرام:</strong> {{ $salon->telegram ?? 'ثبت نشده' }}</p>
                    <p><strong>اینستاگرام:</strong> {{ $salon->instagram ?? 'ثبت نشده' }}</p>
                    <p><strong>وبسایت:</strong> {{ $salon->website ?? 'ثبت نشده' }}</p>
                    <p><strong>شماره تماس پشتیبانی:</strong> {{ $salon->support_phone_number ?? 'ثبت نشده' }}</p>
                    <p><strong>بیوگرافی سالن:</strong> {{ $salon->bio ?? 'ثبت نشده' }}</p>
                </div>
            </div>
        </div>

        <div class="mt-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4 border-b-2 border-indigo-500 pb-2">آدرس و لوکیشن</h2>
            <div class="space-y-3 text-gray-700">
                <p><strong>استان:</strong> {{ $salon->province->name ?? 'ثبت نشده' }}</p>
                <p><strong>شهر:</strong> {{ $salon->city->name ?? 'ثبت نشده' }}</p>
                <p><strong>آدرس دقیق:</strong> {{ $salon->address ?? 'ثبت نشده' }}</p>
                @if ($salon->lat && $salon->lang)
                    <div id="map" class="w-full h-96 rounded-lg shadow-md mt-4"></div>
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const lat = parseFloat({{ $salon->lat }});
                            const lang = parseFloat({{ $salon->lang }});

                            if (lat && lang) {
                                const map = L.map('map').setView([lat, lang], 13);
                                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                                }).addTo(map);
                                L.marker([lat, lang]).addTo(map);
                            }
                        });
                    </script>
                @else
                    <p><strong>لوکیشن:</strong> ثبت نشده</p>
                @endif
            </div>
        </div>

        <div class="mt-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4 border-b-2 border-indigo-500 pb-2">یادداشت‌های داخلی</h2>
            <div class="space-y-4">
                @forelse ($salon->notes as $note)
                    <div class="bg-gray-50 p-4 rounded-lg shadow-sm border border-gray-200">
                        <p class="text-base text-gray-800">{{ $note->content }}</p>
                        <p class="text-xs text-gray-500 mt-2">ثبت شده توسط: {{ $note->user->name ?? 'N/A' }} در تاریخ: {{ verta($note->created_at)->format('Y/m/d H:i') }}</p>
                    </div>
                @empty
                    <p class="text-gray-500">هیچ یادداشتی برای این سالن ثبت نشده است.</p>
                @endforelse
            </div>
        </div>

        <div class="mt-8 flex flex-wrap gap-4 justify-center">
            <button type="button" onclick="openEditModal({{ $salon->id }})" class="btn-action bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-500">
                <i class="ri-edit-line ml-2"></i> ویرایش اطلاعات
            </button>
            <button type="button" onclick="openToggleStatusModal({{ $salon->id }}, {{ $salon->is_active ? 'true' : 'false' }})" class="btn-action {{ $salon->is_active ? 'bg-red-600 hover:bg-red-700 focus:ring-red-500' : 'bg-green-600 hover:bg-green-700 focus:ring-green-500' }} text-white">
                <i class="ri-{{ $salon->is_active ? 'close-circle-line' : 'check-circle-line' }} ml-2"></i> {{ $salon->is_active ? 'غیرفعال کردن' : 'فعال کردن' }}
            </button>
            <button type="button" onclick="openResetPasswordModal({{ $salon->id }})" class="btn-action bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500">
                <i class="ri-key-line ml-2"></i> بازنشانی رمز عبور
            </button>
            <a href="{{ route('admin.salons.purchase-history', $salon->id) }}" class="btn-action bg-purple-600 text-white hover:bg-purple-700 focus:ring-purple-500">
                <i class="ri-shopping-cart-line ml-2"></i> سوابق خرید
            </a>
            <button type="button" onclick="openDiscountCodesModal({{ $salon->id }})" class="btn-action bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500">
                <i class="ri-coupon-line ml-2"></i> کدهای تخفیف فعال
            </button>
            <button type="button" onclick="openNoteModal({{ $salon->id }})" class="btn-action bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500">
                <i class="ri-sticky-note-line ml-2"></i> افزودن یادداشت
            </button>
            <button type="button" onclick="openSmsCreditModal({{ $salon->id }})" class="btn-action bg-green-600 text-white hover:bg-green-700 focus:ring-green-500">
                <i class="ri-message-2-line ml-2"></i> افزودن اعتبار پیامک
            </button>
            <button type="button" onclick="openReduceSmsCreditModal({{ $salon->id }})" class="btn-action bg-red-600 text-white hover:bg-red-700 focus:ring-red-500">
                <i class="ri-subtract-line ml-2"></i> کاهش اعتبار پیامک
            </button>
        </div>
    </div>

    {{-- Modals --}}
    @include('admin.salons.modals.edit_salon_modal', ['salon' => $salon, 'businessCategories' => $businessCategories, 'businessSubcategories' => $businessSubcategories, 'provinces' => $provinces, 'cities' => $cities])
    @include('admin.salons.modals.toggle_status_modal', ['salon' => $salon])
    @include('admin.salons.modals.reset_password_modal', ['salon' => $salon])
    @include('admin.salons.modals.add_note_modal', ['salon' => $salon])
    @include('admin.salons.modals.add_sms_credit_modal', ['salon' => $salon])
    @include('admin.salons.modals.reduce_sms_credit_modal', ['salon' => $salon])
    @include('admin.salons.modals.discount_codes_modal', ['salon' => $salon])
</div>

<script>
    // General modal open/close functions
    function openModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    // Specific modal functions
    function openEditModal(salonId) {
        // Populate form fields if needed, though blade values should handle initial load
        openModal('editModal');
    }

    function openToggleStatusModal(salonId, isActive) {
        const form = document.getElementById('toggleStatusForm');
        form.action = `/admin/salons/${salonId}/toggle-status`;
        const statusText = isActive ? 'غیرفعال' : 'فعال';
        document.getElementById('toggleStatusMessage').innerText = `آیا مطمئن هستید که می‌خواهید این سالن را ${statusText} کنید؟`;
        openModal('toggleStatusModal');
    }

    function openResetPasswordModal(salonId) {
        const form = document.getElementById('resetPasswordForm');
        form.action = `/admin/salons/${salonId}/reset-password`;
        openModal('resetPasswordModal');
    }

    function openNoteModal(salonId) {
        document.getElementById('modalSalonId').value = salonId;
        document.getElementById('noteForm').action = `/admin/salons/${salonId}/notes`;
        openModal('addNoteModal');
    }

    function openSmsCreditModal(salonId) {
        document.getElementById('modalSalonIdSms').value = salonId;
        document.getElementById('smsCreditForm').action = `/admin/salons/${salonId}/add-sms-credit`;
        openModal('addSmsCreditModal');
    }

    function openReduceSmsCreditModal(salonId) {
        document.getElementById('modalSalonIdReduceSms').value = salonId;
        document.getElementById('reduceSmsCreditForm').action = `/admin/salons/${salonId}/reduce-sms-credit`;
        openModal('reduceSmsCreditModal');
    }

    function openDiscountCodesModal(salonId) {
        openModal('discountCodesModal');
        loadDiscountCodes(salonId);
    }

    function loadDiscountCodes(salonId) {
        const loadingElement = document.getElementById('discountCodesLoading');
        const contentElement = document.getElementById('discountCodesContent');
        
        loadingElement.classList.remove('hidden');
        contentElement.classList.add('hidden');
        
        fetch(`/admin/salons/${salonId}/active-discount-codes`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Received data:', data);
                loadingElement.classList.add('hidden');
                contentElement.classList.remove('hidden');
                
                const discountCodesList = document.getElementById('discountCodesList');
                const totalCodesElement = document.getElementById('totalDiscountCodes');
                
                totalCodesElement.textContent = data.totalCodes || 0;
                
                // Update summary information
                if (data.summary) {
                    const summaryElement = document.getElementById('discountCodesSummary');
                    if (summaryElement) {
                        summaryElement.innerHTML = `
                            <div class="grid grid-cols-3 gap-4 text-center">
                                <div class="bg-blue-50 p-3 rounded">
                                    <div class="text-2xl font-bold text-blue-600">${data.summary.total_available}</div>
                                    <div class="text-xs text-blue-800">کل کدهای موجود</div>
                                </div>
                                <div class="bg-green-50 p-3 rounded">
                                    <div class="text-2xl font-bold text-green-600">${data.summary.can_still_use}</div>
                                    <div class="text-xs text-green-800">قابل استفاده</div>
                                </div>
                                <div class="bg-gray-50 p-3 rounded">
                                    <div class="text-2xl font-bold text-gray-600">${data.summary.already_used}</div>
                                    <div class="text-xs text-gray-800">استفاده شده</div>
                                </div>
                            </div>
                        `;
                    }
                }
                
                if (data.discountCodes.length > 0) {
                    discountCodesList.innerHTML = data.discountCodes.map(code => `
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 ${code.has_been_used_by_salon ? 'opacity-75 bg-gray-100' : ''}">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-bold text-lg text-gray-800">${code.code}</h4>
                                        ${code.has_been_used_by_salon ? '<span class="text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded-full">استفاده شده</span>' : '<span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">قابل استفاده</span>'}
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1">
                                        ${code.type === 'percentage' ? code.value + '% تخفیف' : 'تخفیف ثابت ' + new Intl.NumberFormat('fa-IR').format(code.value) + ' تومان'}
                                    </p>
                                    ${code.description ? `<p class="text-xs text-gray-500 mt-1">${code.description}</p>` : ''}
                                </div>
                                <div class="text-left">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${code.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                        ${code.is_active ? 'فعال' : 'غیرفعال'}
                                    </span>
                                </div>
                            </div>
                            <div class="mt-3 text-xs text-gray-500 space-y-1">
                                ${code.starts_at ? `<p><i class="ri-calendar-line ml-1"></i>شروع: ${formatPersianDate(code.starts_at)}</p>` : ''}
                                ${code.expires_at ? `<p><i class="ri-calendar-line ml-1"></i>انقضا: ${formatPersianDate(code.expires_at)}</p>` : '<p><i class="ri-time-line ml-1"></i>بدون تاریخ انقضا</p>'}
                                ${code.usage_limit ? `<p><i class="ri-user-line ml-1"></i>استفاده کل: ${code.usage_count || 0}/${code.usage_limit} ${code.remaining_uses !== null ? `(باقی‌مانده: ${code.remaining_uses})` : ''}</p>` : '<p><i class="ri-infinite-line ml-1"></i>بدون محدودیت استفاده</p>'}
                                ${code.min_order_amount ? `<p><i class="ri-money-dollar-circle-line ml-1"></i>حداقل سفارش: ${new Intl.NumberFormat('fa-IR').format(code.min_order_amount)} تومان</p>` : ''}
                                ${code.max_discount_amount ? `<p><i class="ri-subtract-line ml-1"></i>حداکثر تخفیف: ${new Intl.NumberFormat('fa-IR').format(code.max_discount_amount)} تومان</p>` : ''}
                                ${code.user_filter_type === 'filtered' ? '<p class="text-blue-600"><i class="ri-filter-line ml-1"></i>فقط برای کاربران خاص</p>' : '<p class="text-green-600"><i class="ri-global-line ml-1"></i>برای همه کاربران</p>'}
                            </div>
                        </div>
                    `).join('');
                } else {
                    discountCodesList.innerHTML = `
                        <div class="text-center py-12">
                            <i class="ri-coupon-line text-6xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 text-lg">هیچ کد تخفیف فعالی برای این سالن موجود نیست.</p>
                            <p class="text-gray-400 text-sm mt-2">کدهای تخفیف ممکن است منقضی شده باشند یا شرایط استفاده را ندارند.</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading discount codes:', error);
                loadingElement.classList.add('hidden');
                contentElement.classList.remove('hidden');
                
                document.getElementById('totalDiscountCodes').textContent = '0';
                document.getElementById('discountCodesList').innerHTML = `
                    <div class="text-center py-12">
                        <i class="ri-error-warning-line text-6xl text-red-300 mb-4"></i>
                        <p class="text-red-500 text-lg">خطا در بارگذاری کدهای تخفیف</p>
                        <p class="text-gray-400 text-sm mt-2">لطفاً دوباره تلاش کنید</p>
                        <button onclick="loadDiscountCodes(${salonId})" class="mt-4 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
                            تلاش مجدد
                        </button>
                    </div>
                `;
            });
    }

    // Function to format Persian dates
    function formatPersianDate(dateString) {
        if (!dateString) return '';
        
        try {
            const date = new Date(dateString);
            const year = date.getFullYear();
            const month = date.getMonth() + 1;
            const day = date.getDate();
            
            // Convert to Persian digits
            const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            const formatDate = `${year}/${month.toString().padStart(2, '0')}/${day.toString().padStart(2, '0')}`;
            
            return formatDate.replace(/\d/g, (digit) => persianDigits[parseInt(digit)]);
        } catch (error) {
            return dateString;
        }
    }

    function closeNoteModal() {
        closeModal('addNoteModal');
        document.getElementById('noteContent').value = ''; // Clear textarea
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        if (event.target.id === 'editModal') {
            closeModal('editModal');
        }
        if (event.target.id === 'toggleStatusModal') {
            closeModal('toggleStatusModal');
        }
        if (event.target.id === 'resetPasswordModal') {
            closeModal('resetPasswordModal');
        }
        if (event.target.id === 'addNoteModal') {
            closeModal('addNoteModal');
        }
        if (event.target.id === 'addSmsCreditModal') {
            closeModal('addSmsCreditModal');
        }
        if (event.target.id === 'reduceSmsCreditModal') {
            closeModal('reduceSmsCreditModal');
        }
        if (event.target.id === 'discountCodesModal') {
            closeModal('discountCodesModal');
        }
    }
</script>
@endsection
