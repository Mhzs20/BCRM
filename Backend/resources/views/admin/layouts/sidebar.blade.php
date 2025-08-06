<div class="w-64 bg-gray-800 text-white flex-shrink-0">
    <div class="p-6 text-center">
        <a href="{{ route('admin.dashboard') }}" class="text-2xl font-bold">پنل مدیریت</a>
    </div>
    <nav class="mt-6">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center py-3 px-6 transition duration-200 hover:bg-gray-700 @if(request()->routeIs('admin.dashboard')) bg-gray-700 @endif">
            <i class="ri-dashboard-line text-xl"></i>
            <span class="mr-4">داشبورد</span>
        </a>
        <a href="{{ route('admin.sms-packages.index') }}" class="flex items-center py-3 px-6 transition duration-200 hover:bg-gray-700 @if(request()->routeIs('admin.sms-packages.*')) bg-gray-700 @endif">
            <i class="ri-mail-send-line text-xl"></i>
            <span class="mr-4">مدیریت پکیج‌های SMS</span>
        </a>
        <a href="{{ route('admin.manual_sms.approval') }}" class="flex items-center py-3 px-6 transition duration-200 hover:bg-gray-700 @if(request()->routeIs('admin.manual_sms.approval')) bg-gray-700 @endif">
            <i class="ri-check-double-line text-xl"></i> {{-- Using a relevant icon --}}
            <span class="mr-4">تایید پیامک‌های دستی</span>
        </a>
        <a href="{{ route('admin.sms-templates.index') }}" class="flex items-center py-3 px-6 transition duration-200 hover:bg-gray-700 @if(request()->routeIs('admin.sms-templates.*')) bg-gray-700 @endif">
            <i class="ri-file-text-line text-xl"></i>
            <span class="mr-4">مدیریت قالب‌های SMS</span>
        </a>
        <a href="{{ route('admin.sms_settings.index') }}" class="flex items-center py-3 px-6 transition duration-200 hover:bg-gray-700 @if(request()->routeIs('admin.sms_settings.*')) bg-gray-700 @endif">
            <i class="ri-settings-3-line text-xl"></i> {{-- Using a relevant icon --}}
            <span class="mr-4">تنظیمات پیامک</span>
        </a>
    </nav>
</div>
