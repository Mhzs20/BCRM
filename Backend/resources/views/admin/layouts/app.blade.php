<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    <title>@yield('title', 'پنل مدیریت')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <!-- Alpine.js (for sidebar toggle & existing x-data usages) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Custom button styles */
        .btn-primary {
            @apply inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500;
        }
        .btn-secondary {
            @apply inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500;
        }
        /* Custom red-110 for status */
        .bg-red-110 {
            background-color: #fee2e2; /* A lighter red for background */
        }
        .text-red-800 {
            color: #991b1b; /* Darker red for text */
        }
        
        /* Mobile improvements */
        @media (max-width: 640px) {
            /* Prevent horizontal scroll */
            body {
                overflow-x: hidden;
            }
            
            /* Better touch targets */
            button, a, [role="button"] {
                min-height: 44px;
                min-width: 44px;
            }
            
            /* Improve form inputs on mobile */
            input, select, textarea {
                font-size: 16px; /* Prevent zoom on iOS */
            }
        }
        
        /* Ensure sidebar doesn't overlap content on mobile */
        @media (max-width: 1024px) {
            .sidebar-mobile-hidden {
                transform: translateX(100%);
            }
            
            .sidebar-mobile-visible {
                transform: translateX(0);
            }
            
            /* Prevent body scroll when sidebar is open on mobile */
            .sidebar-open {
                overflow: hidden;
                position: fixed;
                width: 100%;
                height: 100%;
            }
            
            /* Ensure content doesn't go behind sidebar */
            .sidebar-open .main-content {
                position: relative;
                z-index: 1;
            }
        }
    </style>
    <style>
        [x-cloak]{display:none !important;}
    </style>
    
    <script>
        // Close all dropdowns when navigating to a new page
        document.addEventListener('DOMContentLoaded', function() {
            // Close dropdowns on page load
            Alpine.store('dropdowns', {
                closeAll() {
                    // Dispatch custom events to close all dropdowns
                    document.dispatchEvent(new CustomEvent('close-all-dropdowns'));
                }
            });
            
            // Close all dropdowns and sidebar on mobile when a link is clicked
            document.addEventListener('click', function(e) {
                if (e.target.tagName === 'A' && e.target.href && !e.target.href.includes('#')) {
                    // This is a navigation link, close all dropdowns
                    document.dispatchEvent(new CustomEvent('close-all-dropdowns'));
                    
                    // Close sidebar on mobile after navigation
                    if (window.innerWidth < 1024) {
                        setTimeout(() => {
                            const sidebarCloseEvent = new CustomEvent('close-mobile-sidebar');
                            document.dispatchEvent(sidebarCloseEvent);
                        }, 100);
                    }
                }
            });
        });
    </script>
</head>
<body class="font-sans antialiased bg-gray-100 text-gray-900" 
      :class="{ 'sidebar-open': sidebarOpen && window.innerWidth < 1024 }">
    <div x-data="{ 
        sidebarOpen: window.innerWidth >= 1024 ? JSON.parse(localStorage.getItem('sidebarOpen') ?? 'true') : false,
        isMobile: window.innerWidth < 1024
    }" 
         x-effect="localStorage.setItem('sidebarOpen', sidebarOpen)"
         @resize.window="
            isMobile = window.innerWidth < 1024;
            if (isMobile) {
                sidebarOpen = false;
            } else {
                sidebarOpen = JSON.parse(localStorage.getItem('sidebarOpen') ?? 'true');
            }
         "
         @keydown.escape.window="if (isMobile && sidebarOpen) sidebarOpen = false"
         @close-mobile-sidebar.document="if (isMobile) sidebarOpen = false">
        <!-- Sidebar -->
        @include('admin.layouts.sidebar')

        <!-- Main content -->
        <div class="transition-all duration-300 min-h-screen lg:mr-0 relative z-10"
             :class="{
                 'lg:mr-64': sidebarOpen && window.innerWidth >= 1024,
                 'lg:mr-20': !sidebarOpen && window.innerWidth >= 1024
             }">
            <!-- Mobile Header with Menu Button - Sticky -->
            <div class="lg:hidden bg-white shadow-sm border-b border-gray-200 px-4 py-3 flex items-center justify-between sticky top-0 z-[60]">
                <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <h1 class="text-lg font-semibold text-gray-900">پنل مدیریت</h1>
                
                <!-- Mobile Logout Button -->
                <div x-data="{ showDropdown: false }" 
                     class="relative"
                     @click.outside="showDropdown = false"
                     @keydown.escape.window="showDropdown = false"
                     @close-all-dropdowns.document="showDropdown = false">
                    <button @click="showDropdown = !showDropdown" 
                            class="p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </button>
                    
                    <!-- Dropdown -->
                    <div x-show="showDropdown" 
                         @click.away="showDropdown = false" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute left-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-[70]" 
                         x-cloak>
                        <div class="py-1">
                            <div class="px-4 py-2 text-sm text-gray-700 border-b border-gray-100">
                                {{ Auth::user()->name }}
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" 
                                        class="block w-full text-right px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                        @click="showDropdown = false">
                                    خروج
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page Heading -->
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-4 px-4 sm:py-6 sm:px-6 lg:px-8">
                    @yield('header')
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-4 sm:p-6">
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
