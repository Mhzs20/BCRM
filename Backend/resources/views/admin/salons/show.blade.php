@extends('admin.layouts.app')

@section('title', 'پروفایل سالن: ' . $salon->name)

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-extrabold text-gray-900 mb-8 text-center">پروفایل سالن: {{ $salon->name }}</h1>

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
                    <p><strong>شماره تماس:</strong> {{ $salon->mobile }}</p>
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
            <button type="button" onclick="openNoteModal({{ $salon->id }})" class="btn-action bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500">
                <i class="ri-sticky-note-line ml-2"></i> افزودن یادداشت
            </button>
            <button type="button" onclick="openSmsCreditModal({{ $salon->id }})" class="btn-action bg-green-600 text-white hover:bg-green-700 focus:ring-green-500">
                <i class="ri-message-2-line ml-2"></i> افزودن اعتبار پیامک
            </button>
        </div>
    </div>

    {{-- Modals --}}
    @include('admin.salons.modals.edit_salon_modal', ['salon' => $salon, 'businessCategories' => $businessCategories, 'businessSubcategories' => $businessSubcategories, 'provinces' => $provinces, 'cities' => $cities])
    @include('admin.salons.modals.toggle_status_modal', ['salon' => $salon])
    @include('admin.salons.modals.reset_password_modal', ['salon' => $salon])
    @include('admin.salons.modals.add_note_modal', ['salon' => $salon])
    @include('admin.salons.modals.add_sms_credit_modal', ['salon' => $salon])
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
    }
</script>
@endsection
