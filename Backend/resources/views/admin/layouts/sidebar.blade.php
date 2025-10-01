<!-- Mobile Overlay -->
<div x-show="sidebarOpen" 
     x-transition:enter="transition-opacity ease-linear duration-300" 
     x-transition:enter-start="opacity-0" 
     x-transition:enter-end="opacity-100" 
     x-transition:leave="transition-opacity ease-linear duration-300" 
     x-transition:leave-start="opacity-100" 
     x-transition:leave-end="opacity-0" 
     @click="sidebarOpen = false" 
     class="fixed inset-0 bg-gray-600 bg-opacity-75 z-30 lg:hidden" 
     x-cloak></div>

<aside class="bg-gray-900 text-gray-300 flex flex-col h-screen fixed top-0 right-0 transition-all duration-300 z-40 transform lg:transform-none"
       :class="{
           'w-64': sidebarOpen || (window.innerWidth >= 1024 && sidebarOpen),
           'w-20': !sidebarOpen && window.innerWidth >= 1024,
           'translate-x-0': (sidebarOpen && window.innerWidth < 1024) || window.innerWidth >= 1024,
           'translate-x-full': !sidebarOpen && window.innerWidth < 1024
       }">
    <!-- Toggle Button -->
    <button @click="sidebarOpen = !sidebarOpen"
            class="absolute top-1/2 -left-4 transform -translate-y-1/2 z-50 w-9 h-9 rounded-full shadow-lg flex items-center justify-center bg-indigo-600 text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-400 transition-colors border-2 border-white/20 lg:flex hidden"
            :aria-label="sidebarOpen ? 'بستن منو' : 'باز کردن منو'">
        <i class="text-xl transition-transform duration-300"
           :class="sidebarOpen ? 'ri-arrow-right-s-line' : 'ri-arrow-left-s-line'"></i>
    </button>

    <!-- Logo - Sticky Header -->
    <div class="sticky top-0 p-6 text-center border-b border-gray-800 flex-shrink-0 bg-gray-900 z-10 sidebar-sticky-header">
        <a href="{{ route('admin.dashboard') }}" class="text-2xl font-semibold text-white whitespace-nowrap" x-show="sidebarOpen" x-cloak>
            پنل مدیریت
        </a>
        <a href="{{ route('admin.dashboard') }}" class="text-xl font-semibold text-white" x-show="!sidebarOpen" x-cloak>
            <i class="ri-shield-star-line"></i>
        </a>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 px-2 py-4 space-y-2 overflow-y-auto overflow-x-visible custom-scroll" style="direction: ltr;">
        <div style="direction: rtl;">
            <!-- Dashboard Link -->
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-600 text-white' : 'hover:bg-gray-800 hover:text-white' }}"
               :class="sidebarOpen ? '' : 'justify-center'">
                <i class="ri-dashboard-line text-xl"></i>
                <span class="mr-4 font-medium" x-show="sidebarOpen" x-cloak>داشبورد</span>
            </a>

            <!-- Salons Management Dropdown -->
            <div x-data="{ 
                    open: false,
                    hovered: false,
                    init() {
                        // Only open by default if sidebar is expanded AND we're on a salon route
                        this.open = {{ request()->routeIs('admin.salons.*') ? 'true' : 'false' }} && this.sidebarOpen && window.innerWidth >= 1024;
                    }
                 }" 
                 class="relative"
                 @click.outside="open = false; hovered = false"
                 @keydown.escape.window="open = false; hovered = false"
                 @close-all-dropdowns.document="open = false; hovered = false"
                 x-effect="
                    // Close when sidebar collapses on desktop
                    if (!sidebarOpen && window.innerWidth >= 1024) { 
                        open = false; 
                        hovered = false; 
                    }
                    // Close when switching to mobile and sidebar opens
                    if (window.innerWidth < 1024 && sidebarOpen) {
                        open = false;
                        hovered = false;
                    }
                 "
                 @mouseenter="if (!sidebarOpen && window.innerWidth >= 1024) hovered = true"
                 @mouseleave="if (!sidebarOpen && window.innerWidth >= 1024) hovered = false">
                <button @click="
                    if (window.innerWidth >= 1024 || sidebarOpen) {
                        open = !open;
                    }
                "
                        class="w-full flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out hover:bg-gray-800 hover:text-white"
                        :class="sidebarOpen ? 'justify-between' : 'justify-center'">
                    <div class="flex items-center">
                        <i class="ri-community-line text-xl"></i>
                        <span class="mr-4 font-medium" x-show="sidebarOpen" x-cloak>مدیریت سالن‌ها</span>
                    </div>
                    <i class="ri-arrow-down-s-line transition-transform duration-300" :class="{'rotate-180': open}" x-show="sidebarOpen" x-cloak></i>
                </button>
                <!-- Dropdown for open sidebar -->
                <div x-show="open && sidebarOpen" 
                     x-transition:enter="transition ease-out duration-200" 
                     x-transition:enter-start="opacity-0 transform -translate-y-2" 
                     x-transition:enter-end="opacity-100 transform translate-y-0" 
                     x-transition:leave="transition ease-in duration-150" 
                     x-transition:leave-start="opacity-100 transform translate-y-0" 
                     x-transition:leave-end="opacity-0 transform -translate-y-2"
                     class="mt-1 mr-4 pl-4 border-r-2 border-gray-700 space-y-1" 
                     x-cloak>
                    <a href="{{ route('admin.salons.index') }}" 
                       class="block px-4 py-2 rounded-lg text-sm transition-all duration-200 ease-in-out {{ request()->routeIs('admin.salons.index') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700 hover:text-white' }}"
                       @click="open = false">
                        لیست سالن‌ها
                    </a>
                </div>
                <!-- Flyout for collapsed sidebar -->
                <div x-show="hovered && !sidebarOpen && window.innerWidth >= 1024" 
                     @mouseleave="hovered = false" 
                     @click.away="hovered = false"
                     class="fixed top-0 right-20 w-48 bg-gray-800 border border-gray-700 rounded-lg shadow-xl py-2 z-[9999]" 
                     x-cloak
                     :style="'top: ' + $el.parentElement.getBoundingClientRect().top + 'px'">
                    <a href="{{ route('admin.salons.index') }}" 
                       class="block px-4 py-2 text-sm hover:bg-gray-700 rounded-md {{ request()->routeIs('admin.salons.index') ? 'bg-gray-700 text-white' : '' }}"
                       @click="hovered = false">لیست سالن‌ها</a>
                </div>
            </div>

            <!-- SMS Management Dropdown -->
            <div x-data="{ 
                    open: false,
                    hovered: false,
                    init() {
                        // Only open by default if sidebar is expanded AND we're on a SMS route
                        this.open = {{ request()->routeIs('admin.sms-packages.*', 'admin.manual_sms.*', 'admin.sms-templates.*', 'admin.sms_settings.*', 'admin.bulk-sms-gift.*', 'admin.bulk-sms.*', 'admin.sms_campaign_approval.*') ? 'true' : 'false' }} && this.sidebarOpen && window.innerWidth >= 1024;
                    }
                 }" 
                 class="relative"
                 @click.outside="open = false; hovered = false"
                 @keydown.escape.window="open = false; hovered = false"
                 @close-all-dropdowns.document="open = false; hovered = false"
                 x-effect="
                    // Close when sidebar collapses on desktop
                    if (!sidebarOpen && window.innerWidth >= 1024) { 
                        open = false; 
                        hovered = false; 
                    }
                    // Close when switching to mobile and sidebar opens
                    if (window.innerWidth < 1024 && sidebarOpen) {
                        open = false;
                        hovered = false;
                    }
                 "
                 @mouseenter="if (!sidebarOpen && window.innerWidth >= 1024) hovered = true"
                 @mouseleave="if (!sidebarOpen && window.innerWidth >= 1024) hovered = false">
                <button @click="
                    if (window.innerWidth >= 1024 || sidebarOpen) {
                        open = !open;
                    }
                "
                        class="w-full flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out hover:bg-gray-800 hover:text-white"
                        :class="sidebarOpen ? 'justify-between' : 'justify-center'">
                    <div class="flex items-center">
                        <i class="ri-message-2-line text-xl"></i>
                        <span class="mr-4 font-medium" x-show="sidebarOpen" x-cloak>مدیریت پیامک‌ها</span>
                    </div>
                    <i class="ri-arrow-down-s-line transition-transform duration-300" :class="{'rotate-180': open}" x-show="sidebarOpen" x-cloak></i>
                </button>
                <div x-show="open && sidebarOpen" 
                     x-transition:enter="transition ease-out duration-200" 
                     x-transition:enter-start="opacity-0 transform -translate-y-2" 
                     x-transition:enter-end="opacity-100 transform translate-y-0" 
                     x-transition:leave="transition ease-in duration-150" 
                     x-transition:leave-start="opacity-100 transform translate-y-0" 
                     x-transition:leave-end="opacity-0 transform -translate-y-2"
                     class="mt-1 mr-4 pl-4 border-r-2 border-gray-700 space-y-1" 
                     x-cloak>
                    <a href="{{ route('admin.sms-packages.index') }}" 
                       class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('admin.sms-packages.*') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700' }}"
                       @click="open = false">مدیریت پکیج‌ها</a>
                    <a href="{{ route('admin.manual_sms.approval') }}" 
                       class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('admin.manual_sms.approval') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700' }}"
                       @click="open = false">تایید پیامک‌های دستی</a>
                    <a href="{{ route('admin.sms_campaign_approval.index') }}" 
                       class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('admin.sms_campaign_approval.*') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700' }}"
                       @click="open = false">تایید کمپین‌های پیامکی</a>
                    <a href="{{ route('admin.sms-campaign-reports.index') }}" 
                       class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('admin.sms-campaign-reports.*') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700' }}"
                       @click="open = false">گزارش کمپین‌های پیامکی</a>
                    <a href="{{ route('admin.manual_sms.reports') }}" 
                       class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('admin.manual_sms.reports') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700' }}"
                       @click="open = false">گزارش پیامک‌های دستی</a>
                    <a href="{{ route('admin.sms-templates.index') }}" 
                       class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('admin.sms-templates.*') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700' }}"
                       @click="open = false">مدیریت قالب‌ها</a>
                    <a href="{{ route('admin.sms_settings.index') }}" 
                       class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('admin.sms_settings.*') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700' }}"
                       @click="open = false">تنظیمات پیامک</a>
                    <a href="{{ route('admin.bulk-sms-gift.index') }}" 
                       class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('admin.bulk-sms-gift.*') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700' }}"
                       @click="open = false">شارژ گروهی هدیه</a>
                    <a href="{{ route('admin.bulk-sms.index') }}" 
                       class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('admin.bulk-sms.*') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700' }}"
                       @click="open = false">پیامک گروهی</a>
                </div>
                <div x-show="hovered && !sidebarOpen && window.innerWidth >= 1024" 
                     @mouseleave="hovered = false" 
                     @click.away="hovered = false"
                     class="fixed top-0 right-20 w-56 bg-gray-800 border border-gray-700 rounded-lg shadow-xl py-2 z-[9999] space-y-1" 
                     x-cloak
                     :style="'top: ' + $el.parentElement.getBoundingClientRect().top + 'px'">
                    <a href="{{ route('admin.sms-packages.index') }}" 
                       class="block px-4 py-2 text-sm hover:bg-gray-700 rounded-md {{ request()->routeIs('admin.sms-packages.*') ? 'bg-gray-700 text-white' : '' }}"
                       @click="hovered = false">مدیریت پکیج‌ها</a>
                    <a href="{{ route('admin.manual_sms.approval') }}" 
                       class="block px-4 py-2 text-sm hover:bg-gray-700 rounded-md {{ request()->routeIs('admin.manual_sms.approval') ? 'bg-gray-700 text-white' : '' }}"
                       @click="hovered = false">تایید پیامک‌های دستی</a>
                    <a href="{{ route('admin.sms_campaign_approval.index') }}" 
                       class="block px-4 py-2 text-sm hover:bg-gray-700 rounded-md {{ request()->routeIs('admin.sms_campaign_approval.*') ? 'bg-gray-700 text-white' : '' }}"
                       @click="hovered = false">تایید کمپین‌های پیامکی</a>
                    <a href="{{ route('admin.sms-campaign-reports.index') }}" 
                       class="block px-4 py-2 text-sm hover:bg-gray-700 rounded-md {{ request()->routeIs('admin.sms-campaign-reports.*') ? 'bg-gray-700 text-white' : '' }}"
                       @click="hovered = false">گزارش کمپین‌های پیامکی</a>
                    <a href="{{ route('admin.manual_sms.reports') }}" 
                       class="block px-4 py-2 text-sm hover:bg-gray-700 rounded-md {{ request()->routeIs('admin.manual_sms.reports') ? 'bg-gray-700 text-white' : '' }}"
                       @click="hovered = false">گزارش پیامک‌های دستی</a>
                    <a href="{{ route('admin.sms-templates.index') }}" 
                       class="block px-4 py-2 text-sm hover:bg-gray-700 rounded-md {{ request()->routeIs('admin.sms-templates.*') ? 'bg-gray-700 text-white' : '' }}"
                       @click="hovered = false">مدیریت قالب‌ها</a>
                    <a href="{{ route('admin.sms_settings.index') }}" 
                       class="block px-4 py-2 text-sm hover:bg-gray-700 rounded-md {{ request()->routeIs('admin.sms_settings.*') ? 'bg-gray-700 text-white' : '' }}"
                       @click="hovered = false">تنظیمات پیامک</a>
                    <a href="{{ route('admin.bulk-sms-gift.index') }}" 
                       class="block px-4 py-2 text-sm hover:bg-gray-700 rounded-md {{ request()->routeIs('admin.bulk-sms-gift.*') ? 'bg-gray-700 text-white' : '' }}"
                       @click="hovered = false">شارژ گروهی هدیه</a>
                    <a href="{{ route('admin.bulk-sms.index') }}" 
                       class="block px-4 py-2 text-sm hover:bg-gray-700 rounded-md {{ request()->routeIs('admin.bulk-sms.*') ? 'bg-gray-700 text-white' : '' }}"
                       @click="hovered = false">پیامک گروهی</a>
                </div>
            </div>

            <!-- App Updates Link -->
            <a href="{{ route('admin.app-updates.index') }}" class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out {{ request()->routeIs('admin.app-updates.*') ? 'bg-indigo-600 text-white' : 'hover:bg-gray-800 hover:text-white' }}"
               :class="sidebarOpen ? '' : 'justify-center'">
                <i class="ri-refresh-line text-xl"></i>
                <span class="mr-4 font-medium" x-show="sidebarOpen" x-cloak>مدیریت آپدیت‌ها</span>
            </a>

            <!-- Notifications Link -->
            <a href="{{ route('admin.notifications.index') }}" class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out {{ request()->routeIs('admin.notifications.*') ? 'bg-indigo-600 text-white' : 'hover:bg-gray-800 hover:text-white' }}"
               :class="sidebarOpen ? '' : 'justify-center'">
                <i class="ri-notification-3-line text-xl"></i>
                <span class="mr-4 font-medium" x-show="sidebarOpen" x-cloak>اعلانات</span>
            </a>

            <!-- Files Link -->
            <a href="{{ route('admin.files.index') }}" class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out {{ request()->routeIs('admin.files.*') ? 'bg-indigo-600 text-white' : 'hover:bg-gray-800 hover:text-white' }}"
               :class="sidebarOpen ? '' : 'justify-center'">
                <i class="ri-file-line text-xl"></i>
                <span class="mr-4 font-medium" x-show="sidebarOpen" x-cloak>فایل ها</span>
            </a>

            <!-- Banners Link -->
            <a href="{{ route('admin.banners.index') }}" class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out {{ request()->routeIs('admin.banners.*') ? 'bg-indigo-600 text-white' : 'hover:bg-gray-800 hover:text-white' }}"
               :class="sidebarOpen ? '' : 'justify-center'">
                <i class="ri-image-line text-xl"></i>
                <span class="mr-4 font-medium" x-show="sidebarOpen" x-cloak>مدیریت بنرها</span>
            </a>

            <!-- Discount Codes Link -->
            <a href="{{ route('admin.discount-codes.index') }}" class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out {{ request()->routeIs('admin.discount-codes.*') ? 'bg-indigo-600 text-white' : 'hover:bg-gray-800 hover:text-white' }}"
               :class="sidebarOpen ? '' : 'justify-center'">
                <i class="ri-coupon-line text-xl"></i>
                <span class="mr-4 font-medium" x-show="sidebarOpen" x-cloak>کدهای تخفیف</span>
            </a>

            <!-- Transactions Link -->
            <a href="{{ route('admin.transactions.index') }}" class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out {{ request()->routeIs('admin.transactions.*') ? 'bg-indigo-600 text-white' : 'hover:bg-gray-800 hover:text-white' }}"
               :class="sidebarOpen ? '' : 'justify-center'">
                <i class="ri-exchange-dollar-line text-xl"></i>
                <span class="mr-4 font-medium" x-show="sidebarOpen" x-cloak>تراکنش‌ها</span>
            </a>

            <!-- Packages Management Dropdown -->
            <div x-data="{ 
                    open: false,
                    hovered: false,
                    init() {
                        this.open = {{ request()->routeIs('admin.packages.*', 'admin.options.*') ? 'true' : 'false' }} && this.sidebarOpen && window.innerWidth >= 1024;
                    }
                 }" 
                 class="relative"
                 @click.outside="open = false; hovered = false"
                 @keydown.escape.window="open = false; hovered = false"
                 @close-all-dropdowns.document="open = false; hovered = false"
                 x-effect="
                    if (!sidebarOpen && window.innerWidth >= 1024) { 
                        open = false; 
                        hovered = false; 
                    }
                    if (window.innerWidth < 1024 && sidebarOpen) {
                        open = false;
                        hovered = false;
                    }
                 "
                 @mouseenter="if (!sidebarOpen && window.innerWidth >= 1024) hovered = true"
                 @mouseleave="if (!sidebarOpen && window.innerWidth >= 1024) hovered = false">
                <button @click="
                    if (window.innerWidth >= 1024 || sidebarOpen) {
                        open = !open;
                    }
                "
                        class="w-full flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out hover:bg-gray-800 hover:text-white"
                        :class="sidebarOpen ? 'justify-between' : 'justify-center'">
                    <div class="flex items-center">
                        <i class="ri-inbox-line text-xl"></i>
                        <span class="mr-4 font-medium" x-show="sidebarOpen" x-cloak>مدیریت پکیج‌ها</span>
                    </div>
                    <i class="ri-arrow-down-s-line transition-transform duration-300" :class="{'rotate-180': open}" x-show="sidebarOpen" x-cloak></i>
                </button>
                <div x-show="open && sidebarOpen" 
                     x-transition:enter="transition ease-out duration-200" 
                     x-transition:enter-start="opacity-0 transform -translate-y-2" 
                     x-transition:enter-end="opacity-100 transform translate-y-0" 
                     x-transition:leave="transition ease-in duration-150" 
                     x-transition:leave-start="opacity-100 transform translate-y-0" 
                     x-transition:leave-end="opacity-0 transform -translate-y-2"
                     class="mt-1 mr-4 pl-4 border-r-2 border-gray-700 space-y-1" 
                     x-cloak>
                    <a href="{{ route('admin.packages.index') }}" 
                       class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('admin.packages.*') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700' }}"
                       @click="open = false">پکیج‌ها</a>
                    <a href="{{ route('admin.options.index') }}" 
                       class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('admin.options.*') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700' }}"
                       @click="open = false">آپشن‌ها</a>
                </div>
                <div x-show="hovered && !sidebarOpen && window.innerWidth >= 1024" 
                     @mouseleave="hovered = false" 
                     @click.away="hovered = false"
                     class="fixed top-0 right-20 w-48 bg-gray-800 border border-gray-700 rounded-lg shadow-xl py-2 z-[9999]" 
                     x-cloak
                     :style="'top: ' + $el.parentElement.getBoundingClientRect().top + 'px'">
                    <a href="{{ route('admin.packages.index') }}" 
                       class="block px-4 py-2 text-sm hover:bg-gray-700 rounded-md {{ request()->routeIs('admin.packages.*') ? 'bg-gray-700 text-white' : '' }}"
                       @click="hovered = false">پکیج‌ها</a>
                    <a href="{{ route('admin.options.index') }}" 
                       class="block px-4 py-2 text-sm hover:bg-gray-700 rounded-md {{ request()->routeIs('admin.options.*') ? 'bg-gray-700 text-white' : '' }}"
                       @click="hovered = false">آپشن‌ها</a>
                </div>
            </div>

            <!-- General Settings Dropdown -->
            <div x-data="{ open: {{ request()->routeIs('admin.how-introduced.*', 'admin.professions.*', 'admin.customer-groups.*') ? 'true' : 'false' }} }" class="relative">
                <button @click="open = !open"
                        class="w-full flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out hover:bg-gray-800 hover:text-white"
                        :class="sidebarOpen ? 'justify-between' : 'justify-center'">
                    <div class="flex items-center">
                        <i class="ri-settings-4-line text-xl"></i>
                        <span class="mr-4 font-medium" x-show="sidebarOpen" x-cloak>تنظیمات عمومی</span>
                    </div>
                    <i class="ri-arrow-down-s-line transition-transform duration-300" :class="{'rotate-180': open}" x-show="sidebarOpen" x-cloak></i>
                </button>
                <div x-show="open && sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-2"
                     class="mt-1 mr-4 pl-4 border-r-2 border-gray-700 space-y-1" x-cloak>
                    <a href="{{ route('admin.how-introduced.index') }}" class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('admin.how-introduced.*') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700' }}">نحوه آشنایی</a>
                    <a href="{{ route('admin.professions.index') }}" class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('admin.professions.*') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700' }}">مشاغل</a>
                    <a href="{{ route('admin.customer-groups.index') }}" class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('admin.customer-groups.*') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700' }}">گروه‌های مشتریان</a>
                     <a href="{{ route('card-setting.index') }}" class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('card-setting.*') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700' }}">تنظیمات کارت</a>
                </div>
                <div x-show="open && !sidebarOpen" @mouseleave="open = false" @click.away="open = false"
                     class="fixed top-0 right-20 w-52 bg-gray-800 border border-gray-700 rounded-lg shadow-xl py-2 z-[9999] space-y-1" 
                     x-cloak
                     :style="'top: ' + $el.parentElement.getBoundingClientRect().top + 'px'">
                    <a href="{{ route('admin.how-introduced.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-700 rounded-md {{ request()->routeIs('admin.how-introduced.*') ? 'bg-gray-700 text-white' : '' }}">نحوه آشنایی</a>
                    <a href="{{ route('admin.professions.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-700 rounded-md {{ request()->routeIs('admin.professions.*') ? 'bg-gray-700 text-white' : '' }}">مشاغل</a>
                    <a href="{{ route('admin.customer-groups.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-700 rounded-md {{ request()->routeIs('admin.customer-groups.*') ? 'bg-gray-700 text-white' : '' }}">گروه‌های مشتریان</a>
                </div>
            </div>
        </div>
    </nav>
</aside>
