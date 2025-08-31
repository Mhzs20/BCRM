<aside class="w-64 flex-shrink-0 bg-gray-900 text-gray-300 flex flex-col h-full overflow-y-auto">
    <!-- Logo -->
    <div class="p-6 text-center border-b border-gray-800">
        <a href="{{ route('admin.dashboard') }}" class="text-2xl font-semibold text-white">
            پنل مدیریت
        </a>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 px-4 py-4 space-y-2">
        <!-- Dashboard Link -->
        <a href="{{ route('admin.dashboard') }}"
           class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out
                  {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-600 text-white' : 'hover:bg-gray-800 hover:text-white' }}">
            <i class="ri-dashboard-line text-xl"></i>
            <span class="mr-4 font-medium">داشبورد</span>
        </a>

        <!-- Salons Management Dropdown -->
        <div x-data="{ open: {{ request()->routeIs('admin.salons.*') ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center justify-between px-4 py-3 rounded-lg transition-all duration-200 ease-in-out hover:bg-gray-800 hover:text-white">
                <span class="flex items-center">
                    <i class="ri-community-line text-xl"></i>
                    <span class="mr-4 font-medium">مدیریت سالن‌ها</span>
                </span>
                <i class="ri-arrow-down-s-line transition-transform duration-300" :class="{'rotate-180': open}"></i>
            </button>
            <div x-show="open" x-transition class="mt-1 mr-4 pl-4 border-r-2 border-gray-700 space-y-1">
                <a href="{{ route('admin.salons.index') }}"
                   class="block px-4 py-2 rounded-lg text-sm transition-all duration-200 ease-in-out
                          {{ request()->routeIs('admin.salons.index') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700 hover:text-white' }}">
                    لیست سالن‌ها
                </a>
            </div>
        </div>

        <!-- SMS Management Dropdown -->
        <div x-data="{ open: {{ request()->routeIs('admin.sms-packages.*', 'admin.manual_sms.*', 'admin.sms-templates.*', 'admin.sms_settings.*', 'admin.bulk-sms-gift.*') ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center justify-between px-4 py-3 rounded-lg transition-all duration-200 ease-in-out hover:bg-gray-800 hover:text-white">
                <span class="flex items-center">
                    <i class="ri-message-2-line text-xl"></i>
                    <span class="mr-4 font-medium">مدیریت پیامک‌ها</span>
                </span>
                <i class="ri-arrow-down-s-line transition-transform duration-300" :class="{'rotate-180': open}"></i>
            </button>
            <div x-show="open" x-transition class="mt-1 mr-4 pl-4 border-r-2 border-gray-700 space-y-1">
                <a href="{{ route('admin.sms-packages.index') }}" class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('admin.sms-packages.*') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700' }}">مدیریت پکیج‌ها</a>
                <a href="{{ route('admin.manual_sms.approval') }}" class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('admin.manual_sms.approval') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700' }}">تایید پیامک‌های دستی</a>
                <a href="{{ route('admin.manual_sms.reports') }}" class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('admin.manual_sms.reports') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700' }}">گزارش پیامک‌های دستی</a>
                <a href="{{ route('admin.sms-templates.index') }}" class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('admin.sms-templates.*') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700' }}">مدیریت قالب‌ها</a>
                <a href="{{ route('admin.sms_settings.index') }}" class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('admin.sms_settings.*') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700' }}">تنظیمات پیامک</a>
                <a href="{{ route('admin.bulk-sms-gift.index') }}" class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('admin.bulk-sms-gift.*') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700' }}">شارژ گروهی هدیه</a>
            </div>
        </div>

        <!-- App Updates Link -->
        <a href="{{ route('admin.app-updates.index') }}"
           class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out
                  {{ request()->routeIs('admin.app-updates.*') ? 'bg-indigo-600 text-white' : 'hover:bg-gray-800 hover:text-white' }}">
            <i class="ri-refresh-line text-xl"></i>
            <span class="mr-4 font-medium">مدیریت آپدیت‌ها</span>
        </a>

        <!-- Notifications Link -->
        <a href="{{ route('admin.notifications.index') }}"
           class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out
                  {{ request()->routeIs('admin.notifications.*') ? 'bg-indigo-600 text-white' : 'hover:bg-gray-800 hover:text-white' }}">
            <i class="ri-notification-3-line text-xl"></i>
            <span class="mr-4 font-medium">اعلانات</span>
        </a>

        <!-- Files Link -->
        <a href="{{ route('admin.files.index') }}"
           class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out
                  {{ request()->routeIs('admin.files.*') ? 'bg-indigo-600 text-white' : 'hover:bg-gray-800 hover:text-white' }}">
            <i class="ri-file-line text-xl"></i>
            <span class="mr-4 font-medium">فایل ها</span>
        </a>

        <!-- Banners Link -->
        <a href="{{ route('admin.banners.index') }}"
           class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out
                  {{ request()->routeIs('admin.banners.*') ? 'bg-indigo-600 text-white' : 'hover:bg-gray-800 hover:text-white' }}">
            <i class="ri-image-line text-xl"></i>
            <span class="mr-4 font-medium">مدیریت بنرها</span>
        </a>

        <!-- General Settings Dropdown -->
        <div x-data="{ open: {{ request()->routeIs('admin.how-introduced.*', 'admin.professions.*', 'admin.customer-groups.*') ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center justify-between px-4 py-3 rounded-lg transition-all duration-200 ease-in-out hover:bg-gray-800 hover:text-white">
                <span class="flex items-center">
                    <i class="ri-settings-4-line text-xl"></i>
                    <span class="mr-4 font-medium">تنظیمات عمومی</span>
                </span>
                <i class="ri-arrow-down-s-line transition-transform duration-300" :class="{'rotate-180': open}"></i>
            </button>
            <div x-show="open" x-transition class="mt-1 mr-4 pl-4 border-r-2 border-gray-700 space-y-1">
                <a href="{{ route('admin.how-introduced.index') }}" class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('admin.how-introduced.*') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700' }}">نحوه آشنایی</a>
                <a href="{{ route('admin.professions.index') }}" class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('admin.professions.*') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700' }}">مشاغل</a>
                <a href="{{ route('admin.customer-groups.index') }}" class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('admin.customer-groups.*') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700' }}">گروه‌های مشتریان</a>
            </div>
        </div>
    </nav>
</aside>
