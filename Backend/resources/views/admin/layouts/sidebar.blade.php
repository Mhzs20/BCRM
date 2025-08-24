<div class="w-64 bg-gray-800 text-white flex-shrink-0 h-full overflow-y-auto">
    <div class="p-6 text-center">
        <a href="{{ route('admin.dashboard') }}" class="text-2xl font-bold">پنل مدیریت</a>
    </div>
    <nav class="mt-6 flex-1 overflow-y-auto">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center py-3 px-6 transition duration-200 hover:bg-gray-700 @if(request()->routeIs('admin.dashboard')) bg-gray-700 @endif">
            <i class="ri-dashboard-line text-xl"></i>
            <span class="mr-4">داشبورد</span>
        </a>
        <div x-data="{ open: {{ request()->routeIs('admin.salons.*') ? 'true' : 'false' }} }">
            <a @click="open = !open" href="#" class="flex items-center justify-between py-3 px-6 transition duration-200 hover:bg-gray-700 cursor-pointer">
                <div class="flex items-center">
                    <i class="ri-community-line text-xl"></i>
                    <span class="mr-4">مدیریت سالن‌ها</span>
                </div>
                <i class="ri-arrow-down-s-line" :class="{'rotate-180': open}"></i>
            </a>
            <div x-show="open" class="pl-8 bg-gray-750">
                <a href="{{ route('admin.salons.index') }}" class="flex items-center py-2 px-4 transition duration-200 hover:bg-gray-700 @if(request()->routeIs('admin.salons.index')) bg-gray-600 @endif">
                    <i class="ri-list-check text-lg"></i>
                    <span class="mr-3">لیست سالن‌ها</span>
                </a>
            </div>
        </div>
        <div x-data="{ open: {{ request()->routeIs('admin.sms-packages.*') || request()->routeIs('admin.manual_sms.*') || request()->routeIs('admin.sms-templates.*') || request()->routeIs('admin.sms_settings.*') || request()->routeIs('admin.bulk-sms-gift.*') ? 'true' : 'false' }} }">
            <a @click="open = !open" href="#" class="flex items-center justify-between py-3 px-6 transition duration-200 hover:bg-gray-700 cursor-pointer">
                <div class="flex items-center">
                    <i class="ri-message-2-line text-xl"></i>
                    <span class="mr-4">مدیریت پیامک‌ها</span>
                </div>
                <i class="ri-arrow-down-s-line" :class="{'rotate-180': open}"></i>
            </a>
            <div x-show="open" class="pl-8 bg-gray-750">
                <a href="{{ route('admin.sms-packages.index') }}" class="flex items-center py-2 px-4 transition duration-200 hover:bg-gray-700 @if(request()->routeIs('admin.sms-packages.*')) bg-gray-600 @endif">
                    <i class="ri-mail-send-line text-lg"></i>
                    <span class="mr-3">مدیریت پکیج‌ها</span>
                </a>
                <a href="{{ route('admin.manual_sms.approval') }}" class="flex items-center py-2 px-4 transition duration-200 hover:bg-gray-700 @if(request()->routeIs('admin.manual_sms.approval')) bg-gray-600 @endif">
                    <i class="ri-check-double-line text-lg"></i>
                    <span class="mr-3">تایید پیامک‌های دستی</span>
                </a>
                <a href="{{ route('admin.manual_sms.reports') }}" class="flex items-center py-2 px-4 transition duration-200 hover:bg-gray-700 @if(request()->routeIs('admin.manual_sms.reports')) bg-gray-600 @endif">
                    <i class="ri-bar-chart-box-line text-lg"></i>
                    <span class="mr-3">گزارش پیامک‌های دستی</span>
                </a>
                <a href="{{ route('admin.sms-templates.index') }}" class="flex items-center py-2 px-4 transition duration-200 hover:bg-gray-700 @if(request()->routeIs('admin.sms-templates.*')) bg-gray-600 @endif">
                    <i class="ri-file-text-line text-lg"></i>
                    <span class="mr-3">مدیریت قالب‌ها</span>
                </a>
                <a href="{{ route('admin.sms_settings.index') }}" class="flex items-center py-2 px-4 transition duration-200 hover:bg-gray-700 @if(request()->routeIs('admin.sms_settings.*')) bg-gray-600 @endif">
                    <i class="ri-settings-3-line text-lg"></i>
                    <span class="mr-3">تنظیمات پیامک</span>
                </a>
                <a href="{{ route('admin.bulk-sms-gift.index') }}" class="flex items-center py-2 px-4 transition duration-200 hover:bg-gray-700 @if(request()->routeIs('admin.bulk-sms-gift.*')) bg-gray-600 @endif">
                    <i class="ri-gift-line text-lg"></i>
                    <span class="mr-3">شارژ گروهی هدیه</span>
                </a>
            </div>
        </div>
        <a href="{{ route('admin.app-updates.index') }}" class="flex items-center py-3 px-6 transition duration-200 hover:bg-gray-700 @if(request()->routeIs('admin.app-updates.*')) bg-gray-700 @endif">
            <i class="ri-refresh-line text-xl"></i> {{-- Using a relevant icon --}}
            <span class="mr-4">مدیریت آپدیت‌ها</span>
        </a>
        <a href="{{ route('admin.notifications.index') }}" class="flex items-center py-3 px-6 transition duration-200 hover:bg-gray-700 @if(request()->routeIs('admin.notifications.*')) bg-gray-700 @endif">
            <i class="ri-notification-3-line text-xl"></i>
            <span class="mr-4">اعلانات</span>
        </a>
        <a href="{{ route('admin.files.index') }}" class="flex items-center py-3 px-6 transition duration-200 hover:bg-gray-700 @if(request()->routeIs('admin.files.*')) bg-gray-700 @endif">
            <i class="ri-file-line text-xl"></i>
            <span class="mr-4">فایل ها</span>
        </a>
        <a href="{{ route('admin.banners.index') }}" class="flex items-center py-3 px-6 transition duration-200 hover:bg-gray-700 @if(request()->routeIs('admin.banners.*')) bg-gray-700 @endif">
            <i class="ri-image-line text-xl"></i>
            <span class="mr-4">مدیریت بنرها</span>
        </a>
        <div x-data="{ open: {{ request()->routeIs('admin.how-introduced.*') || request()->routeIs('admin.professions.*') || request()->routeIs('admin.customer-groups.*') ? 'true' : 'false' }} }">
            <a @click="open = !open" href="#" class="flex items-center justify-between py-3 px-6 transition duration-200 hover:bg-gray-700 cursor-pointer">
                <div class="flex items-center">
                    <i class="ri-settings-4-line text-xl"></i>
                    <span class="mr-4">تنظیمات عمومی</span>
                </div>
                <i class="ri-arrow-down-s-line" :class="{'rotate-180': open}"></i>
            </a>
            <div x-show="open" class="pl-8 bg-gray-750">
                <a href="{{ route('admin.how-introduced.index') }}" class="flex items-center py-2 px-4 transition duration-200 hover:bg-gray-700 @if(request()->routeIs('admin.how-introduced.*')) bg-gray-600 @endif">
                    <i class="ri-question-line text-lg"></i>
                    <span class="mr-3">نحوه آشنایی</span>
                </a>
                <a href="{{ route('admin.professions.index') }}" class="flex items-center py-2 px-4 transition duration-200 hover:bg-gray-700 @if(request()->routeIs('admin.professions.*')) bg-gray-600 @endif">
                    <i class="ri-briefcase-line text-lg"></i>
                    <span class="mr-3">مشاغل</span>
                </a>
                <a href="{{ route('admin.customer-groups.index') }}" class="flex items-center py-2 px-4 transition duration-200 hover:bg-gray-700 @if(request()->routeIs('admin.customer-groups.*')) bg-gray-600 @endif">
                    <i class="ri-group-line text-lg"></i>
                    <span class="mr-3">گروه‌های مشتریان</span>
                </a>
            </div>
        </div>
    </nav>
</div>
