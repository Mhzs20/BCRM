@php
    $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $serverDate = now()->format('Y-m-d');
    
    // Calculate Persian date on server side using verta
    $persianDate = verta(now());
    $persianYear = $persianDate->year;
    $persianMonth = $persianDate->month;
    $persianDay = $persianDate->day;
@endphp
<!DOCTYPE html>
<html lang="fa" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>رزرو نوبت آنلاین - {{ $salon->name }}</title>
    <meta name="salon-id" content="{{ $salon->id }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="{{ asset('js/jquery-3.6.0.min.js') }}"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --holiday-bg: #fef2f2;
            --holiday-border: #ef4444;
            --holiday-text: #b91c1c;
            --disabled-bg: #f3f4f6;
            --disabled-border: #d1d5db;
            --disabled-text: #9ca3af;
            --transition-fast: 150ms;
            --transition-normal: 300ms;
        }
        
        * {
            -webkit-tap-highlight-color: transparent;
        }
        
        .font-iranyekan {
            font-family: 'IRANYekanMobileFN', 'IRANYekan', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .font-peyda {
            font-family: 'Peyda', 'IRANYekan', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .calendar-tooltip {
            font-family: 'Peyda', sans-serif;
            animation: fadeInUp var(--transition-normal) ease-out;
            border: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 9999 !important;
        }
        
        .calendar-tooltip::before {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: #1f2937;
        }
        
        #calendar-days {
            width: 100%;
        }

        #calendar-days > div {
            width: 100%;
        }

        #calendar-days .calendar-day {
            flex: 0 0 auto;
        }

        .booking-summary-card__content,
        .booking-summary-card__action {
            transition: all var(--transition-fast) ease;
        }

        .calendar-day {
            transition: all var(--transition-fast) ease;
            will-change: transform, background-color;
        }
        
        .calendar-day:active {
            transform: scale(0.95);
        }
        
        .calendar-day.holiday,
        .calendar-day.holiday-jalali {
            background-color: var(--holiday-bg) !important;
            border-color: var(--holiday-border) !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12) !important;
        }

        .calendar-day.holiday .day-number,
        .calendar-day.holiday-jalali .day-number {
            color: var(--holiday-text) !important;
            font-weight: 800 !important;
        }

        .calendar-day.disabled,
        .calendar-day.disabled-day {
            background-color: var(--disabled-bg) !important;
            border-color: var(--disabled-border) !important;
            color: var(--disabled-text) !important;
            opacity: 0.6 !important;
        }

        .calendar-day.disabled .day-number,
        .calendar-day.disabled-day .day-number {
            color: var(--disabled-text) !important;
        }

        .calendar-day.normal {
            background-color: #ffffff !important;
            border-color: #1f2937 !important;
            color: #374151 !important;
        }

        .calendar-day.normal .day-number {
            color: #374151 !important;
        }

        .calendar-day.holiday-hijri {
            background-color: #fef3c7 !important;
            border-color: #f59e0b !important;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.12) !important;
        }

        .calendar-day.holiday-hijri .day-number {
            color: #92400e !important;
            font-weight: 800 !important;
        }

        .calendar-day .events {
            font-size: 10px;
            color: #6b7280;
            margin-top: 2px;
        }

        .calendar-day .event {
            line-height: 1.2;
            margin-bottom: 1px;
        }
        
        /* Responsive improvements - Mobile First */
        
        /* Extra Small Devices (phones, less than 375px) */
        @media (max-width: 374px) {
            .calendar-day {
                width: 1.85rem !important;
                height: 2.5rem !important;
                font-size: 0.75rem;
                box-shadow: none !important;
                border-bottom-width: 1px !important;
                border-top-width: 0 !important;
                border-left-width: 0 !important;
                border-right-width: 0 !important;
            }
            #calendar-days > div {
                gap: 0.15rem !important;
                min-height: 2.5rem !important;
            }
            .calendar-day .day-number {
                font-size: 0.9rem !important;
                font-weight: 500 !important;
            }
            .booking-summary-card {
                gap: 0.45rem !important;
            }
            .booking-summary-card__content {
                padding: 0.4rem 0.6rem !important;
                min-height: 36px !important;
            }
            .booking-summary-card__action {
                width: 3.25rem !important;
                min-height: 36px !important;
            }
            .booking-summary-card__action-text {
                font-size: 0.9rem !important;
            }
            .calendar-day:not(.empty) {
                margin: 0 !important;
            }
            .text-xl { font-size: 1rem !important; }
            .text-lg { font-size: 0.95rem !important; }
            .text-base { font-size: 0.875rem !important; }
            .px-4 { padding-left: 0.75rem !important; padding-right: 0.75rem !important; }
        }
        
        /* Small Devices (phones, 375px to 424px) */
        @media (min-width: 375px) and (max-width: 424px) {
            .calendar-day {
                width: 2.7rem !important;
                height: 3.4rem !important;
                border-bottom-width: 2px !important;
                border-top-width: 0 !important;
                border-left-width: 0 !important;
                border-right-width: 0 !important;
            }
            #calendar-days > div {
                gap: 0.25rem !important;
                min-height: 3.4rem !important;
            }
            .calendar-day .day-number {
                font-size: 1.2rem !important;
            }
            .booking-summary-card {
                gap: 0.5rem !important;
            }
            .booking-summary-card__content {
                padding: 0.5rem 0.75rem !important;
            }
            .booking-summary-card__action {
                width: 3.5rem !important;
            }
            .booking-summary-card__action-text {
                font-size: 1rem !important;
            }
        }

        /* Medium Mobile Devices (phones, 425px to 640px) */
        @media (min-width: 425px) and (max-width: 640px) {
            .calendar-day {
                width: 3.1rem !important;
                height: 3.8rem !important;
                border-bottom-width: 2px !important;
                border-top-width: 0 !important;
                border-left-width: 0 !important;
                border-right-width: 0 !important;
            }
            #calendar-days > div {
                gap: 0.35rem !important;
                min-height: 3.8rem !important;
            }
            .calendar-day .day-number {
                font-size: 1.4rem !important;
            }
            .booking-summary-card {
                gap: 0.6rem !important;
            }
            .booking-summary-card__content {
                padding: 0.5rem 0.75rem !important;
            }
            .booking-summary-card__action {
                width: 3.5rem !important;
            }
            .booking-summary-card__action-text {
                font-size: 1rem !important;
            }
        }
        
        /* Medium Devices (tablets, 641px to 768px) */
        @media (min-width: 641px) and (max-width: 768px) {
            main {
                max-width: 36rem !important;
            }
            .calendar-day {
                width: 3.5rem !important;
                height: 4.5rem !important;
            }
            .day-number {
                font-size: 2rem !important;
            }
        }
        
        /* Large Devices (desktops, 769px and up) */
        @media (min-width: 769px) {
            main {
                max-width: 28rem !important;
            }
        }
        
        /* Landscape orientation adjustments */
        @media (orientation: landscape) and (max-height: 500px) {
            .calendar-day {
                height: 3rem !important;
            }
            .h-16 { height: 3rem !important; }
            .py-8 { padding-top: 1rem !important; padding-bottom: 1rem !important; }
        }
        
        /* Performance optimizations */
        .service-item,
        .calendar-day,
        button {
            -webkit-transform: translateZ(0);
            transform: translateZ(0);
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
        }
        
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            * { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }
        
        /* Touch device optimizations */
        @media (hover: none) and (pointer: coarse) {
            .service-item,
            .calendar-day:not(.disabled):not(.disabled-day),
            button:not(:disabled) {
                min-height: 44px;
                min-width: 44px;
            }
        }
    </style>
</head>
<body class="bg-gray-100 font-peyda text-right">
<main class="w-full max-w-md mx-auto bg-gray-100 rounded-3xl p-3 sm:p-4 min-h-screen">
    <!-- Header -->
    <div id="salon-header" class="flex flex-col items-center justify-center">
        <header class="relative w-full bg-white rounded-bl-3xl rounded-br-3xl border-b-2 border-teal-900 text-center py-6 sm:py-8">
            <!-- Background Decorative SVG -->
            <div class="absolute inset-0 z-0 overflow-hidden rounded-bl-3xl rounded-br-3xl">
<svg class="absolute top-0 right-0 w-full h-full opacity-20" width="360" height="120" viewBox="0 0 360 120" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M293.758 112.696L294.313 112.55L295.933 112.214C296.662 112.083 297.538 111.878 298.574 111.616C299.61 111.353 300.792 111.06 302.12 110.637C309.609 108.389 316.682 104.936 323.062 100.412C332.575 93.7128 340.265 84.7404 345.433 74.3101C351.769 61.5215 354.243 47.1621 352.555 32.988L352.978 33.3093H352.671C342.504 34.5744 332.775 38.2079 324.264 43.918C315.754 49.6281 308.699 57.2559 303.667 66.1889C300.987 70.8813 298.778 75.8279 297.071 80.9562C294.549 88.3958 293.238 96.193 293.189 104.049C293.164 105.282 293.203 106.516 293.306 107.745C293.333 108.649 293.411 109.551 293.54 110.447C293.54 111.148 293.671 111.689 293.729 112.098C293.745 112.287 293.745 112.478 293.729 112.667C293.679 112.485 293.64 112.299 293.613 112.112C293.613 111.703 293.467 111.163 293.35 110.462C293.198 109.567 293.096 108.665 293.043 107.759C292.941 106.693 292.854 105.452 292.854 104.064C292.793 96.1653 294.026 88.31 296.502 80.8101C298.183 75.6208 300.383 70.6146 303.069 65.8675C306.118 60.3809 309.942 55.3631 314.422 50.9687C324.78 40.8369 338.158 34.3648 352.525 32.5352H352.832H353.211V32.9149C354.909 47.2402 352.372 61.7494 345.915 74.6461C343.123 80.248 339.594 85.4504 335.422 90.1145C331.775 94.1517 327.688 97.7675 323.237 100.894C316.789 105.427 309.626 108.844 302.047 111.002C299.335 111.79 296.563 112.357 293.758 112.696Z" fill="#1C1C1E" fill-opacity="0.4"/>
<path d="M336.219 56.1211C336.219 56.1211 336.117 56.3402 335.839 56.7346L334.599 58.3851L329.958 64.3592C326.018 69.3839 320.516 76.3075 314.416 83.9175C308.316 91.5276 302.873 98.5242 299.21 103.695C297.357 106.295 295.912 108.442 294.934 109.932C294.457 110.732 293.926 111.498 293.344 112.225C293.421 111.993 293.529 111.772 293.665 111.568C293.884 111.159 294.219 110.545 294.686 109.771C295.591 108.238 296.963 106.047 298.758 103.388C302.333 98.0714 307.703 91.031 313.818 83.4355C319.933 75.84 325.493 68.9603 329.535 64.0233L334.365 58.1806L335.723 56.6177C335.867 56.4328 336.034 56.2661 336.219 56.1211Z" fill="#1C1C1E" fill-opacity="0.4"/>
<path d="M274.09 92.7705C274.08 92.6539 274.08 92.5366 274.09 92.42C274.09 92.1571 274.177 91.8357 274.25 91.4413C274.411 90.5649 274.6 89.2795 274.761 87.6144C275.195 82.9071 274.889 78.1609 273.856 73.5482C272.366 66.6935 269.202 60.3151 264.648 54.9832C259.063 48.448 251.661 43.7243 243.385 41.4136L243.852 41.1507V41.326C241.628 49.6595 242.092 58.4838 245.18 66.537C249.733 77.493 258.324 86.2749 269.172 91.0616C269.945 91.3975 270.631 91.6458 271.258 91.8503C271.765 92.0474 272.281 92.2181 272.805 92.3615L273.768 92.6537C273.987 92.6537 274.104 92.7559 274.09 92.7705C272.368 92.4685 270.685 91.9787 269.069 91.3099C264.626 89.5335 260.52 87.009 256.928 83.8459C251.473 79.2495 247.216 73.3945 244.524 66.7853C241.319 58.5994 240.808 49.6037 243.064 41.1069V40.9316L243.152 40.5664L243.531 40.6687C251.951 43.0121 259.471 47.8369 265.115 54.5157C269.713 59.9478 272.878 66.4457 274.323 73.4167C275.329 78.0845 275.556 82.887 274.994 87.629C274.853 88.9167 274.633 90.1947 274.338 91.4559C274.25 91.8503 274.162 92.1717 274.104 92.4346C274.121 92.5465 274.116 92.6605 274.09 92.7705Z" fill="#1C1C1E" fill-opacity="0.4"/>
<path d="M252.562 55.832C252.936 56.2761 253.25 56.7671 253.496 57.2927C254.109 58.3736 254.868 59.7028 255.773 61.2657C257.67 64.6398 260.253 69.314 263.07 74.4993C265.886 79.6847 268.455 84.3588 270.483 87.6453C271.49 89.2958 272.336 90.5666 272.905 91.516C273.25 91.9664 273.539 92.4569 273.766 92.9767C273.35 92.6001 272.983 92.1733 272.672 91.7059C272.015 90.8441 271.096 89.5587 270.031 87.952C267.871 84.7239 265.2 80.079 262.398 74.806C259.596 69.533 257.101 64.9028 255.321 61.4556C254.503 59.8489 253.861 58.5343 253.248 57.3803C252.961 56.8922 252.731 56.3727 252.562 55.832Z" fill="#1C1C1E" fill-opacity="0.4"/>
<path d="M289.326 57.7128C289.326 57.7128 290.085 57.0117 291.355 55.6387C293.151 53.7088 294.629 51.5048 295.733 49.1095C297.373 45.5761 298.124 41.6948 297.922 37.804C297.661 33.0612 296.034 28.4953 293.237 24.658L293.763 24.7456C289.952 27.6368 287.161 31.6686 285.795 36.2557C284.745 40.0142 284.595 43.9675 285.357 47.7949C285.822 50.3917 286.688 52.9 287.925 55.2297C288.83 56.8656 289.501 57.669 289.385 57.7128C289.187 57.5504 289.011 57.364 288.859 57.1577C288.397 56.5995 287.982 56.0034 287.619 55.3757C286.248 53.0652 285.285 50.5352 284.773 47.8971C283.914 43.9828 284.014 39.9184 285.065 36.0512C286.443 31.3043 289.308 27.1259 293.237 24.1321H293.325L293.631 23.8984L293.85 24.2052C296.755 28.1752 298.421 32.9171 298.637 37.8332C298.78 41.1659 298.232 44.4923 297.026 47.6022C295.821 50.7121 293.984 53.5381 291.632 55.9016C291.123 56.4217 290.582 56.9095 290.012 57.3622C289.56 57.5813 289.341 57.742 289.326 57.7128Z" fill="#1C1C1E" fill-opacity="0.4"/>
<path d="M292.43 34.0039C292.459 35.1802 292.356 36.3561 292.124 37.5095C291.846 39.6421 291.408 42.6072 290.869 45.8499C290.329 49.0926 289.876 52.0431 289.613 54.1903C289.538 55.3554 289.372 56.5128 289.117 57.6521C288.961 56.4839 288.961 55.3 289.117 54.1319C289.249 51.9701 289.628 48.9903 290.168 45.733C290.708 42.4758 291.233 39.5398 291.627 37.4219C291.774 36.2574 292.043 35.1117 292.43 34.0039Z" fill="#1C1C1E" fill-opacity="0.4"/>
<path d="M326.423 114.135C326.423 114.135 327.254 114.749 328.83 115.727C331.053 117.094 333.476 118.105 336.01 118.722C339.764 119.639 343.688 119.599 347.422 118.605C351.992 117.392 356.1 114.85 359.228 111.301V111.842H359.155C355.625 108.669 351.168 106.718 346.444 106.277C342.582 105.993 338.708 106.645 335.149 108.176C332.727 109.171 330.477 110.541 328.48 112.236C327.021 113.449 326.452 114.281 326.393 114.223C326.335 114.164 326.525 114.004 326.817 113.609C327.264 113.044 327.752 112.512 328.276 112.017C330.23 110.19 332.48 108.708 334.93 107.635C338.557 105.995 342.531 105.273 346.503 105.532C351.384 105.958 355.994 107.963 359.637 111.243H359.71L360.002 111.491L359.739 111.783C356.516 115.45 352.265 118.061 347.539 119.277C344.331 120.089 340.989 120.218 337.728 119.654C334.467 119.09 331.361 117.847 328.612 116.005C328.012 115.607 327.442 115.168 326.904 114.69C326.583 114.34 326.408 114.164 326.423 114.135Z" fill="#1C1C1E" fill-opacity="0.4"/>
<path d="M349.991 112.444C348.861 112.702 347.706 112.839 346.547 112.853C344.416 112.999 341.469 113.16 338.2 113.292C334.931 113.423 332.012 113.54 329.896 113.715C328.752 113.888 327.595 113.961 326.438 113.934C327.53 113.526 328.675 113.276 329.838 113.189C332.602 112.823 335.383 112.603 338.17 112.532C341.439 112.401 344.387 112.327 346.518 112.313C347.677 112.232 348.841 112.276 349.991 112.444Z" fill="#1C1C1E" fill-opacity="0.4"/>
<path d="M291.865 0.496626C290.722 0.251835 289.56 0.110069 288.392 0.0730326C285.019 0.165156 281.734 1.17507 278.891 2.99436C276.795 4.2417 274.838 5.71078 273.054 7.37635C271.113 9.20218 269.172 11.364 267.071 13.6572C264.861 16.1679 262.445 18.4887 259.847 20.5954C256.904 22.9409 253.473 24.5978 249.807 25.4448C245.736 26.2484 241.555 26.3128 237.461 25.6347C233.05 24.917 228.709 23.8173 224.488 22.3482C220.11 20.8875 215.659 19.1347 210.96 17.7617C206.167 16.2333 201.189 15.3636 196.162 15.1763C190.875 14.9618 185.651 16.4015 181.219 19.2954C176.71 22.2167 173.368 26.8032 170.303 31.4773C167.238 36.1514 164.466 41.2784 161.051 46.0839C160.234 47.2963 159.241 48.3918 158.307 49.5457C157.826 50.1154 157.271 50.612 156.761 51.1525C156.248 51.6795 155.697 52.1678 155.112 52.6131L153.36 54.0738C152.733 54.4974 152.062 54.8625 151.419 55.2569L150.456 55.8412L149.391 56.2794L147.275 57.1266C141.438 58.9816 135.119 57.9153 129.675 55.8266C124.373 53.5561 119.333 50.7155 114.644 47.3547C110.145 44.1481 105.328 41.4121 100.27 39.1896C95.3805 37.1868 90.2396 35.8648 84.991 35.2604C75.2374 34.1711 65.3639 35.1669 56.0234 38.1818C51.7946 39.5154 47.6885 41.2111 43.7505 43.2503C40.1289 45.1552 36.6426 47.3074 33.3163 49.6918C27.6021 53.8252 22.3302 58.539 17.5848 63.758C13.9753 67.6765 10.6217 71.8237 7.5447 76.1736C4.97629 79.7376 3.16671 82.6152 1.94088 84.5724C1.34256 85.5365 0.890168 86.2814 0.481557 86.8073C0.0729466 87.3331 0 87.5668 0 87.5668C0 87.5668 0.131329 87.2893 0.423194 86.7781L1.73658 84.4994C2.91863 82.5129 4.65524 79.5916 7.22365 75.9983C10.2759 71.6126 13.6051 67.4265 17.1908 63.4658C21.9275 58.1946 27.1997 53.4315 32.9223 49.2536C36.2673 46.8483 39.7676 44.6672 43.4003 42.7244C47.3664 40.6609 51.5017 38.9408 55.7608 37.5829C65.1667 34.5214 75.1153 33.4958 84.9472 34.5739C90.2636 35.1859 95.4723 36.5175 100.431 38.5323C105.534 40.7642 110.395 43.515 114.936 46.7412C119.587 50.0762 124.588 52.8925 129.851 55.1401C135.192 57.1996 141.292 58.2221 146.954 56.4254C152.455 54.3633 157.15 50.5903 160.351 45.6604C163.765 40.9716 166.48 35.8885 169.573 31.1414C172.667 26.3942 176.067 21.8077 180.723 18.7695C185.249 15.7775 190.596 14.2761 196.016 14.4752C201.104 14.6697 206.142 15.5592 210.989 17.119C215.717 18.5797 220.168 20.2886 224.532 21.7493C228.714 23.219 233.015 24.3235 237.388 25.0504C241.416 25.7325 245.533 25.6879 249.544 24.9189C253.167 24.1342 256.568 22.547 259.497 20.274C262.085 18.1932 264.496 15.9019 266.706 13.4235C268.822 11.1449 270.778 9.04151 272.748 7.17186C274.534 5.50927 276.49 4.04043 278.585 2.78987C281.478 0.978635 284.819 0.0123676 288.231 6.10669e-06C288.72 -0.000562076 289.208 0.0385195 289.69 0.116862C290.06 0.157415 290.426 0.225772 290.785 0.321354C291.573 0.394387 291.865 0.496626 291.865 0.496626Z" fill="#1C1C1E" fill-opacity="0.4"/>
<path d="M137.773 27.9418C137.773 27.9418 137.875 27.9418 138.094 27.8249C138.411 27.7577 138.734 27.7234 139.057 27.7227C140.266 27.7604 141.438 28.1514 142.428 28.8474C143.179 29.3404 143.818 29.9848 144.306 30.7393C144.793 31.4939 145.118 32.342 145.259 33.2294C145.392 34.3177 145.182 35.4202 144.661 36.3844C144.08 37.3972 143.232 38.2308 142.209 38.7945C141.249 39.4073 140.114 39.689 138.979 39.5967C137.843 39.5045 136.769 39.0432 135.92 38.2833C135.302 37.6053 134.861 36.7857 134.634 35.8967C134.408 35.0078 134.403 34.0767 134.621 33.1855C134.86 31.9825 135.536 30.9106 136.518 30.1766C136.787 29.996 137.082 29.8581 137.394 29.7676C137.613 29.7676 137.715 29.6799 137.729 29.7676C137.744 29.8552 137.306 29.9721 136.693 30.4833C135.844 31.2401 135.295 32.2768 135.146 33.4046C134.988 34.2005 135.019 35.0224 135.237 35.804C135.455 36.5855 135.854 37.3048 136.401 37.9035C137.152 38.5359 138.086 38.9111 139.065 38.9742C140.045 39.0373 141.019 38.7849 141.845 38.254C142.738 37.7502 143.483 37.0197 144.004 36.1361C144.469 35.3112 144.663 34.3607 144.559 33.4192C144.451 32.6098 144.179 31.8309 143.76 31.1301C143.341 30.4294 142.784 29.8216 142.122 29.344C141.225 28.6589 140.162 28.2253 139.043 28.0878C138.24 27.9271 137.788 28.0148 137.773 27.9418Z" fill="#1C1C1E" fill-opacity="0.4"/>
</svg>

            </div>
            
            <div class="relative z-10">
                <!-- Salon Image with Online Status -->
                <div class="w-28 h-28 sm:w-32 sm:h-32 mx-auto bg-zinc-300 rounded-full border-2 border-zinc-900 overflow-hidden relative">
                    <img class="w-full h-full object-cover"
                         src="{{ $salon->image ?? 'https://placehold.co/134x134' }}"
                         alt="{{ $salon->name }}"/>
                    <!-- Online Status Indicator -->
                    <div class="absolute bottom-2 right-2 w-4 h-4 bg-orange-400 rounded-full border-2 border-white"></div>
                </div>
                
                <div class="mt-4">
                    <h1 class="text-neutral-700 text-lg sm:text-xl font-black">{{ $salon->name }}</h1>
                    <div class="inline-flex justify-center items-center gap-1.5 bg-orange-400/10 rounded-[10px] px-4 py-1 mt-2">
                        <span class="text-orange-400 text-lg font-bold font-iranyekan">رزرو نوبت آنلاین</span>
                    </div>
                </div>
            </div>
        </header>
    </div>

    <!-- Main Content -->
    <div class="mt-5 sm:mt-7 space-y-5 sm:space-y-7">
        <!-- Service Selection Section -->
        <section id="service-selection-section">
            <div class="space-y-3.5">
                <!-- Section Header -->
                <div id="step-1-header" class="flex flex-col justify-center items-center gap-4">
                    <div class="flex justify-end items-center gap-1.5 w-full">
                        <div class="text-center justify-start text-neutral-700 text-base sm:text-lg font-bold font-peyda">انتخاب خدمت</div>
                        <div class="w-6 h-6 relative overflow-hidden">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="3" y="3" width="7" height="7" stroke="#374151" stroke-width="2"/>
                                <rect x="14" y="3" width="7" height="7" stroke="#374151" stroke-width="2"/>
                                <rect x="14" y="14" width="7" height="7" stroke="#374151" stroke-width="2"/>
                                <rect x="3" y="14" width="7" height="7" stroke="#374151" stroke-width="2"/>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- Search Bar -->
                <div id="search-bar-section" class="flex justify-between items-center gap-2">
                    <button id="search-btn" class="w-20 sm:w-24 h-9 bg-zinc-900/5 rounded-lg shadow-sm border border-zinc-900 text-center text-zinc-900 text-sm sm:text-base font-bold font-iranyekan">
                        جسـتـجــو
                    </button>
                    <div class="flex-1 h-10 bg-white rounded-lg shadow-sm relative">
                        <input 
                            type="text" 
                            id="service-search" 
                            placeholder="نام خدمت جهت جستجو ..." 
                            class="w-full h-full px-4 pr-12 text-right text-neutral-700 text-base font-normal font-iranyekan bg-transparent border-none outline-none rounded-lg"
                        />
                        <div class="absolute left-3 top-1/2 transform -translate-y-1/2 w-6 h-6">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="11" cy="11" r="8" stroke="#9CA3AF" stroke-width="2"/>
                                <path d="M21 21l-4.35-4.35" stroke="#9CA3AF" stroke-width="2"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Services List -->
                <div id="services-container" class="space-y-2.5">
                    <div id="services-loading" class="text-center py-8">
                        <div class="inline-block w-8 h-8 border-4 border-teal-900 border-t-transparent rounded-full animate-spin"></div>
                        <p class="mt-2 text-neutral-500 text-sm">در حال بارگذاری خدمات...</p>
                    </div>
                </div>
                
                <!-- Calendar Section (Initially Hidden) -->
                <div id="calendar-section" class="hidden mt-6">
                    <!-- Service Selection Summary Card -->
                    <div class="w-full flex flex-col justify-center items-center gap-4 mb-6">
                        <x-booking-summary-card 
                            id="calendar-selected-service" 
                            buttonId="change-service-from-calendar" 
                            title="خدمت انتخاب شده" 
                            icon="service" 
                        />
                    </div>

                    <!-- Calendar Title -->
                    <div class="w-full flex flex-col justify-center items-center gap-4 mb-6">
                        <div class="self-stretch inline-flex justify-end items-center gap-1.5">
                            <div class="text-center justify-start text-neutral-700 text-lg font-bold font-peyda">انتخاب زمان نوبت</div>
                            <div class="w-6 h-6 relative overflow-hidden">
                                <img src="{{ asset('assets/img/clock.svg') }}" alt="clock" class="w-full h-full object-contain">
                            </div>
                        </div>
                    </div>

                    <!-- Year and Month Navigation -->
                    <div class="flex flex-col justify-start items-start gap-2.5 sm:gap-3.5 mb-4 sm:mb-6">
                        <div class="w-full inline-flex justify-start items-start gap-2 sm:gap-3.5">
                            <div class="flex-1 h-7 sm:h-8 relative bg-white rounded-lg shadow-[0px_3px_15px_0px_rgba(65,105,225,0.08)] overflow-hidden">
                                <button id="next-year" class="w-6 h-6 absolute left-1.5 top-1/2 -translate-y-1/2 flex items-center justify-center pointer-events-none">
                                    <svg width="8" height="14" viewBox="0 0 8 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <g clip-path="url(#clip0_next_year)">
                                            <path d="M7 13L1 7L7 1" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </g>
                                        <defs>
                                            <clipPath id="clip0_next_year">
                                                <rect width="8" height="14" fill="white"/>
                                            </clipPath>
                                        </defs>
                                    </svg>
                                </button>
                                <div id="current-year" class="absolute inset-0 flex items-center justify-center text-center text-neutral-700 text-sm sm:text-base font-bold font-iranyekan cursor-pointer">1404</div>
                            </div>
                            <div class="flex-1 h-7 sm:h-8 relative bg-zinc-900 rounded-lg shadow-[0px_3px_15px_0px_rgba(65,105,225,0.08)] overflow-hidden">
                                <div id="selected-year" class="absolute inset-0 flex items-center justify-center text-center text-white text-sm sm:text-base font-bold font-iranyekan">1404</div>
                            </div>
                            <div class="flex-1 h-7 sm:h-8 relative bg-white rounded-lg shadow-[0px_3px_15px_0px_rgba(65,105,225,0.08)] overflow-hidden">
                                <div id="prev-year-name" class="absolute inset-0 flex items-center justify-center text-center text-neutral-700 text-sm sm:text-base font-bold font-iranyekan cursor-pointer">۱۴۰۳</div>
                                <button id="prev-year" class="w-6 h-6 absolute right-1.5 top-1/2 -translate-y-1/2 flex items-center justify-center pointer-events-none">
                                    <svg width="8" height="14" viewBox="0 0 8 14" fill="none" xmlns="http://www.w3.org/2000/svg" class="transform rotate-180">
                                        <g clip-path="url(#clip0_prev_year)">
                                            <path d="M7 13L1 7L7 1" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </g>
                                        <defs>
                                            <clipPath id="clip0_prev_year">
                                                <rect width="8" height="14" fill="white"/>
                                            </clipPath>
                                        </defs>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="w-full inline-flex justify-start items-start gap-2 sm:gap-3.5">
                            <div class="flex-1 h-7 sm:h-8 relative bg-white rounded-lg shadow-[0px_3px_15px_0px_rgba(65,105,225,0.08)] overflow-hidden">
                                <button id="next-month" class="w-6 h-6 absolute left-1.5 top-1/2 -translate-y-1/2 flex items-center justify-center pointer-events-none">
                                    <svg width="8" height="14" viewBox="0 0 8 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <g clip-path="url(#clip0_next_month)">
                                            <path d="M7 13L1 7L7 1" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </g>
                                        <defs>
                                            <clipPath id="clip0_next_month">
                                                <rect width="8" height="14" fill="white"/>
                                            </clipPath>
                                        </defs>
                                    </svg>
                                </button>
                                <div id="current-month" class="absolute inset-0 flex items-center justify-center text-center text-neutral-700 text-xs sm:text-sm font-bold font-peyda cursor-pointer">خرداد</div>
                            </div>
                            <div class="flex-1 h-7 sm:h-8 relative bg-zinc-900 rounded-lg shadow-[0px_3px_15px_0px_rgba(65,105,225,0.08)] overflow-hidden">
                                <div id="selected-month" class="absolute inset-0 flex items-center justify-center text-center text-white text-xs sm:text-sm font-bold font-peyda">اردیبهشــت</div>
                            </div>
                            <div class="flex-1 h-7 sm:h-8 relative bg-white rounded-lg shadow-[0px_3px_15px_0px_rgba(65,105,225,0.08)] overflow-hidden">
                                <div id="prev-month-name" class="absolute inset-0 flex items-center justify-center text-center text-neutral-700 text-xs sm:text-sm font-bold font-peyda cursor-pointer">فروردین</div>
                                <button id="prev-month" class="w-6 h-6 absolute right-1.5 top-1/2 -translate-y-1/2 flex items-center justify-center pointer-events-none">
                                    <svg width="8" height="14" viewBox="0 0 8 14" fill="none" xmlns="http://www.w3.org/2000/svg" class="transform rotate-180">
                                        <g clip-path="url(#clip0_prev_month)">
                                            <path d="M7 13L1 7L7 1" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </g>
                                        <defs>
                                            <clipPath id="clip0_prev_month">
                                                <rect width="8" height="14" fill="white"/>
                                            </clipPath>
                                        </defs>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Calendar -->
                    <div class="self-stretch flex flex-col justify-start items-start gap-3 sm:gap-5" dir="rtl">
                        <!-- Calendar Header - Persian Week Days -->
                        <div class="inline-flex justify-center items-center gap-1 sm:gap-2 w-full">
                            <div class="w-full h-8 sm:h-10 relative bg-zinc-900 rounded-tl-2xl rounded-tr-2xl shadow-[0px_3px_15px_0px_rgba(65,105,225,0.08)]">
                                <!-- Persian week starts with Saturday, distributed evenly -->
                                <div class="w-full h-full flex items-center justify-center">
                                    <div class="flex justify-between items-center w-full px-2 sm:px-4">
                                        <div class="text-center text-white text-sm sm:text-base font-bold font-peyda flex-1">ش</div>
                                        <div class="text-center text-white text-sm sm:text-base font-bold font-peyda flex-1">ی</div>
                                        <div class="text-center text-white text-sm sm:text-base font-bold font-peyda flex-1">د</div>
                                        <div class="text-center text-white text-sm sm:text-base font-bold font-peyda flex-1">س</div>
                                        <div class="text-center text-white text-sm sm:text-base font-bold font-peyda flex-1">چ</div>
                                        <div class="text-center text-white text-sm sm:text-base font-bold font-peyda flex-1">پ</div>
                                        <div class="text-center text-white text-sm sm:text-base font-bold font-peyda flex-1">ج</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Calendar Days -->
                        <div id="calendar-days" class="self-stretch flex flex-col justify-start items-start gap-1 sm:gap-1.5">
                            <!-- Calendar will be generated by JavaScript -->
                        </div>
                    </div>
                </div>
                    
                    <!-- Time Slots Section -->
                    <div id="time-slots-section" class="hidden mt-4 sm:mt-6 flex flex-col gap-3 sm:gap-4">
                        <!-- Selected Date Display -->
                        <div class="self-stretch h-9 sm:h-10 inline-flex justify-start items-center gap-2">
                            <div class="flex-1 self-stretch relative bg-gradient-to-b from-zinc-900/0 to-zinc-900/10 rounded-lg shadow-[0px_3px_15px_0px_rgba(65,105,225,0.08)] border-b-2 border-zinc-900">
                                <div id="selected-date-display" class="absolute inset-0 flex items-center justify-center text-center" dir="rtl">
                                    <span class="text-neutral-700 text-base font-bold font-iranyekan">تاریخ انتخابی : </span>
                                    <span id="selected-date-text" class="text-neutral-700 text-base font-normal font-iranyekan"></span>
                                    <span class="text-neutral-700 text-base font-normal font-iranyekan"> </span>
                                    <span id="selected-date-number" class="text-neutral-700 text-xl px-2 font-bold font-iranyekan"></span>
                                    <span class="text-neutral-700 text-base font-normal font-iranyekan"> </span>
                                    <span id="selected-date-month" class="text-neutral-700 text-base font-normal font-iranyekan"></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Time Slots Container -->
                        <div id="time-slots-container" class="self-stretch inline-flex flex-col justify-start items-start gap-2">
                            <div class="self-stretch text-center py-4">در حال بارگذاری...</div>
                        </div>
                    </div>
                    
                    <!-- Customer Info Section (New Design) -->
                    <div id="customer-info-section" class="hidden mt-4 sm:mt-6">
                        <div class="w-full flex flex-col gap-6">
                            
                            <!-- Selected Details Cards -->
                            <div class="w-full flex flex-col gap-4">
                                <x-booking-summary-card 
                                    id="form-selected-service" 
                                    buttonId="change-service-from-form" 
                                    title="خدمت انتخاب شده" 
                                    icon="service" 
                                />

                                <x-booking-summary-card 
                                    id="form-selected-datetime" 
                                    buttonId="change-time-from-form" 
                                    title="زمان رزرو شده" 
                                    icon="clock" 
                                />
                            </div>
                            
                            <!-- Section Header (Outside White Box) -->
                            <div class="flex items-center justify-end gap-2">
                                <h3 class="font-bold text-lg text-neutral-700 font-peyda">اطلاعات نوبت گیرنده</h3>
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M20 21C20 19.6044 20 18.9067 19.8278 18.3389C19.44 17.0605 18.4395 16.06 17.1611 15.6722C16.5933 15.5 15.8956 15.5 14.5 15.5H9.5C8.10444 15.5 7.40665 15.5 6.83886 15.6722C5.56045 16.06 4.56004 17.0605 4.17224 18.3389C4 18.9067 4 19.6044 4 21" stroke="#374151" stroke-width="1.5" stroke-linecap="round"/>
                                    <circle cx="12" cy="9" r="4" stroke="#374151" stroke-width="1.5"/>
                                </svg>
                            </div>
                            
                            <!-- Customer Form -->
                            <div class="w-full bg-white rounded-2xl p-5 shadow-sm">
                                <form id="customer-form-new" class="flex flex-col gap-4 sm:gap-6">
                                    
                                    <!-- Mobile Field (Always Visible) -->
                                    <div class="relative w-full">
                                        <input type="tel" id="customer-mobile-new" class="w-full h-12 sm:h-14 px-3 sm:px-4 rounded-xl border border-gray-200 text-left text-sm sm:text-base font-light font-iranyekan focus:outline-none focus:border-gray-800 placeholder-gray-300" placeholder="09121234567" dir="ltr" pattern="09[0-9]{9}" maxlength="11" inputmode="numeric" required />
                                        <label for="customer-mobile-new" class="absolute -top-3 right-6 bg-white px-2 text-base font-medium text-gray-800 font-peyda">شماره موبایل</label>
                                    </div>

                                    <!-- New User Fields (Initially Hidden) -->
                                    <div id="new-user-fields" class="hidden flex flex-col gap-4 sm:gap-6">
                                        <!-- Name Field -->
                                        <div class="relative w-full">
                                            <input type="text" id="customer-name-new" class="w-full h-12 sm:h-14 px-3 sm:px-4 rounded-xl border border-gray-200 text-right text-sm sm:text-base font-light font-iranyekan focus:outline-none focus:border-gray-800 placeholder-gray-300" placeholder="مثل : حنانه عاشوری" />
                                            <label for="customer-name-new" class="absolute -top-3 right-6 bg-white px-2 text-base font-medium text-gray-800 font-peyda">نام و نام خانوادگی</label>
                                        </div>
                                        
                                        <!-- Referral Source Field -->
                                        <div class="relative w-full">
                                            <select id="customer-referral-new" class="w-full h-12 sm:h-14 px-3 sm:px-4 rounded-xl border border-gray-200 text-right text-sm sm:text-base font-normal font-iranyekan focus:outline-none focus:border-gray-800 appearance-none bg-transparent">
                                                <option value="" disabled selected class="text-gray-300">انتخاب کنید</option>
                                                <option value="google">سرچ گوگل</option>
                                                <option value="instagram">اینستاگرام</option>
                                                <option value="friend">معرفی دوستان</option>
                                                <option value="other">سایر</option>
                                            </select>
                                            <label for="customer-referral-new" class="absolute -top-3 right-6 bg-white px-2 text-base font-medium text-gray-800 font-peyda">نحوه آشنایی</label>
                                            <div class="absolute left-4 top-1/2 transform -translate-y-1/2 pointer-events-none">
                                                <svg width="14" height="8" viewBox="0 0 14 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M1 1L7 7L13 1" stroke="#374151" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Submit and Back Buttons -->
                                    <div class="flex flex-col gap-4 mt-2">
                                        <button type="submit" id="check-mobile-btn" class="w-full h-11 sm:h-12 bg-gradient-to-b from-zinc-800 to-zinc-900 text-white rounded-[10px] font-bold font-peyda text-lg sm:text-xl shadow-lg hover:shadow-xl transition-all duration-200 disabled:opacity-70 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                                            <svg id="loading-spinner" class="hidden animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span id="submit-btn-text">ثبت و ادامه</span>
                                        </button>
                                        
                                        <button type="button" id="back-to-time-selection" class="w-full text-zinc-900 font-normal font-peyda text-lg hover:text-zinc-700 transition-colors">
                                            بازگشت
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- OTP Section (Hidden Initially) -->
                    <div id="otp-section" class="hidden mt-4 sm:mt-6">
                        <div class="w-full flex flex-col gap-6">
                            
                            <!-- Summary Cards for OTP Section -->
                            <div class="w-full flex flex-col gap-4">
                                <x-booking-summary-card 
                                    id="otp-selected-service" 
                                    buttonId="change-service-from-otp" 
                                    title="خدمت انتخاب شده" 
                                    icon="service" 
                                />

                                <x-booking-summary-card 
                                    id="otp-selected-datetime" 
                                    buttonId="change-time-from-otp" 
                                    title="زمان رزرو شده" 
                                    icon="clock" 
                                />
                                
                                <x-booking-summary-card 
                                    id="otp-personal-info" 
                                    buttonId="change-info-from-otp" 
                                    title="اطلاعات شخصی تکمیل شد" 
                                    icon="user" 
                                />
                            </div>
                            
                            <!-- Section Header (Outside White Box) -->
                            <div class="flex items-center justify-end gap-2">
                                <h3 class="font-bold text-lg text-neutral-700 font-peyda">تائید شماره موبایل</h3>
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M20 21C20 19.6044 20 18.9067 19.8278 18.3389C19.44 17.0605 18.4395 16.06 17.1611 15.6722C16.5933 15.5 15.8956 15.5 14.5 15.5H9.5C8.10444 15.5 7.40665 15.5 6.83886 15.6722C5.56045 16.06 4.56004 17.0605 4.17224 18.3389C4 18.9067 4 19.6044 4 21" stroke="#374151" stroke-width="1.5" stroke-linecap="round"/>
                                    <circle cx="12" cy="9" r="4" stroke="#374151" stroke-width="1.5"/>
                                </svg>
                            </div>

                            <div class="w-full bg-white rounded-2xl p-5 shadow-sm">
                                <div class="flex flex-col gap-6 sm:gap-8">
                                    <!-- Success Message -->
                                    <div id="otp-error-box" style="display:none" class="w-full bg-rose-50 border border-rose-500 rounded-xl p-3 flex items-center justify-center mb-2">
                                        <span class="text-rose-500 font-medium font-iranyekan text-sm otp-error-text">کد تایید اشتباه یا منقضی شده است</span>
                                    </div>
                                    <div id="otp-success-box" class="w-full bg-green-50 border border-green-500 rounded-xl p-3 flex items-center justify-center">
                                        <span class="text-green-600 font-medium font-iranyekan text-sm">رمز یکبار مصرف 6 رقمی برای شما پیامک شد</span>
                                    </div>

                                    <!-- OTP Input -->
                                    <div class="flex flex-col gap-3">
                                        <div class="relative w-full">
                                            <input type="text" id="otp-input" class="w-full h-12 sm:h-14 px-3 sm:px-4 rounded-xl border border-gray-200 text-center text-xl sm:text-2xl font-bold font-iranyekan tracking-[0.5em] focus:outline-none focus:border-gray-800 placeholder-gray-200" placeholder="_ _ _ _ _ _" maxlength="6" dir="ltr" />
                                            <label class="absolute -top-3 right-6 bg-white px-2 text-base font-medium text-gray-800 font-peyda">کد ارسالی</label>
                                        </div>
                                        
                                        <!-- Resend & Timer -->
                                        <div class="flex flex-row-reverse justify-between items-center px-2" style="direction: rtl;">
                                            <button id="resend-otp-btn" class="text-zinc-900 font-medium font-iranyekan text-sm hover:text-zinc-700 disabled:text-gray-400 disabled:cursor-not-allowed">ارسال مجدد</button>
                                            <div class="flex items-center gap-2">
                                                <span class="text-gray-900 font-medium font-iranyekan text-sm">اعتبار کد :</span>
                                                <span class="text-gray-500 font-medium font-iranyekan text-sm" id="otp-timer" dir="ltr">87 ثانیه</span>
                                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <circle cx="10" cy="10" r="7.5" stroke="#374151" stroke-width="1.5"/>
                                                    <path d="M10 6V10L12.5 12.5" stroke="#374151" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex flex-col gap-4">
                                        <button id="verify-otp-btn" class="w-full h-12 bg-gradient-to-b from-zinc-800 to-zinc-900 text-white rounded-[10px] font-bold font-peyda text-xl shadow-lg hover:shadow-xl transition-all duration-200 disabled:opacity-70 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                                            <svg id="verify-loading-spinner" class="hidden animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span id="verify-btn-text">ثبت نوبت</span>
                                        </button>
                                        <button id="back-to-mobile-btn" class="w-full text-zinc-900 font-normal font-peyda text-lg hover:text-zinc-700 transition-colors">
                                            بازگشت
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            </div>
        </section>

        <!-- Separator -->
        <div id="separator-section" class="h-px bg-zinc-300 my-4 sm:my-0"></div>

        <!-- Contact Section -->
        <section id="contact-section">
            <div class="space-y-3.5">
                <div class="flex justify-end items-center gap-1.5">
                    <div class="text-center justify-start text-neutral-700 text-base sm:text-lg font-bold font-peyda">ارتباط با {{ $salon->name }}</div>
                    <div class="w-6 h-6 relative overflow-hidden">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 13C10.4295 13.5741 10.9774 14.0491 11.6066 14.3929C12.2357 14.7367 12.9315 14.9411 13.6467 14.9923C14.3618 15.0435 15.0796 14.9403 15.7513 14.6897C16.4231 14.4392 17.0331 14.047 17.54 13.54L20.54 10.54C21.4508 9.59695 21.9548 8.33394 21.9434 7.02296C21.932 5.71198 21.4061 4.45791 20.4791 3.53087C19.5521 2.60383 18.298 2.07799 16.987 2.0666C15.676 2.0552 14.413 2.55918 13.47 3.46997L11.75 5.17997" stroke="#374151" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M13.9982 10.9992C13.5688 10.4251 13.0209 9.95007 12.3917 9.60631C11.7625 9.26255 11.0667 9.05813 10.3516 9.00691C9.63645 8.9557 8.91866 9.05888 8.2469 9.30947C7.57514 9.56005 6.96513 9.95218 6.45825 10.4592L3.45825 13.4592C2.54746 14.4023 2.04348 15.6653 2.05488 16.9763C2.06627 18.2872 2.59211 19.5413 3.51915 20.4683C4.44619 21.3954 5.70026 21.9212 7.01124 21.9326C8.32222 21.944 9.58524 21.44 10.5282 20.5292L12.2382 18.8192" stroke="#374151" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
                
                <!-- Contact Options -->
                <div class="space-y-2">
                    <div class="grid grid-cols-3 gap-1.5 sm:gap-2">
                        @if($salon->whatsapp)
                            <a href="https://wa.me/0098{{ $salon->whatsapp }}" class="bg-white rounded-lg shadow-sm p-3 sm:p-4 text-center">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 mx-auto bg-zinc-900/5 rounded-full flex items-center justify-center">
                                    <img src="{{ asset('assets/img/whatsapp.png') }}" alt="whatsapp" class="w-5 h-5 sm:w-6 sm:h-6"/>
                                </div>
                                <p class="text-neutral-700 text-xs sm:text-sm font-bold mt-1.5 sm:mt-2">واتس اپ</p>
                                <p class="text-neutral-400 text-[8px] font-light" dir="ltr">{{ str_replace($englishDigits, $persianDigits, $salon->whatsapp) }}</p>
                            </a>
                        @endif
                        
                        @if($salon->telegram)
                            <a href="https://t.me/{{ $salon->telegram }}" class="bg-white rounded-lg shadow-sm p-3 sm:p-4 text-center">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 mx-auto bg-zinc-900/5 rounded-full flex items-center justify-center">
                                    <img src="{{ asset('assets/img/telegram.png') }}" alt="telegram" class="w-5 h-5 sm:w-6 sm:h-6"/>
                                </div>
                                <p class="text-neutral-700 text-xs sm:text-sm font-bold mt-1.5 sm:mt-2">تلگرام</p>
                                <p class="text-neutral-400 text-[8px] font-light" dir="ltr">{{ $salon->telegram }}</p>
                            </a>
                        @endif
                        
                        @if($salon->instagram)
                            <a href="https://instagram.com/{{ $salon->instagram }}" class="bg-white rounded-lg shadow-sm p-3 sm:p-4 text-center">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 mx-auto bg-zinc-900/5 rounded-full flex items-center justify-center">
                                    <img src="{{ asset('assets/img/instagram.png') }}" alt="instagram" class="w-5 h-5 sm:w-6 sm:h-6"/>
                                </div>
                                <p class="text-neutral-700 text-xs sm:text-sm font-bold mt-1.5 sm:mt-2">اینستاگرام</p>
                                <p class="text-neutral-400 text-[8px] font-light" dir="ltr">{{ $salon->instagram }}</p>
                            </a>
                        @endif
                    </div>
                    
                    @if($salon->support_phone_number)
                        <a href="tel:{{ $salon->support_phone_number }}" class="bg-white flex rounded-lg shadow-sm p-3 sm:p-4 text-center justify-center items-center">
                            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center">
                                <img src="{{ asset('assets/img/phone.png') }}" alt="phone" class="w-5 h-5 sm:w-6 sm:h-6"/>
                            </div>
                            <p class="text-neutral-700 text-sm sm:text-base font-bold mr-2 sm:mr-3" dir="ltr">{{ str_replace($englishDigits, $persianDigits, $salon->support_phone_number) }}</p>
                        </a>
                    @endif
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <x-app-footer />
</main>

<!-- Booking Flow Modals -->

<!-- Customer Info Modal -->
<div id="customer-info-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 p-4">
    <div class="bg-white rounded-2xl max-w-md mx-auto mt-20 p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-neutral-700">اطلاعات مشتری</h3>
            <button id="close-customer-modal" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="customer-form" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">نام و نام خانوادگی</label>
                <input type="text" id="customer-name" class="w-full p-3 border border-gray-300 rounded-lg text-right" placeholder="نام خود را وارد کنید" required>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">شماره موبایل</label>
                <input type="tel" id="customer-mobile" class="w-full p-3 border border-gray-300 rounded-lg text-right" placeholder="۰۹۱۲۳۴۵۶۷۸۹" required>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">توضیحات (اختیاری)</label>
                <textarea id="customer-notes" class="w-full p-3 border border-gray-300 rounded-lg text-right" rows="3" placeholder="توضیحات اضافی خود را بنویسید..."></textarea>
            </div>
            
            <button type="submit" class="w-full bg-teal-900 text-white py-3 rounded-lg font-bold">
                ثبت نوبت
            </button>
        </form>
    </div>
</div>

<!-- Success Modal -->
<div id="success-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 p-4">
    <div class="bg-white rounded-2xl max-w-md mx-auto mt-20 p-6 text-center">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-neutral-700 mb-2">نوبت شما ثبت شد!</h3>
        <div id="success-details" class="text-sm text-gray-600 space-y-2">
            <!-- Success details will be populated here -->
        </div>
        <button id="close-success-modal" class="mt-6 w-full bg-teal-900 text-white py-3 rounded-lg font-bold">
            تمام
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // Helper function to get element safely
    const $ = id => document.getElementById(id);
    const $$ = selector => document.querySelectorAll(selector);
    
    // Global variables
    let selectedServices = [];
    let selectedDate = null;
    let selectedDateTime = null;
    const salonId = {{ $salon->id ?? 1051 }}; // Default to 1051 if not set

    // Helper function to convert digits to Persian - Optimized
    const persianDigits = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    const persianMonthNames = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
    const englishDigits = /\d/g;

    function toPersianDigits(val) {
        return (val === null || val === undefined) ? '' : String(val).replace(englishDigits, x => persianDigits[x]);
    }
    


    // Convert Persian (Eastern Arabic) digits to ASCII digits - Optimized
    const digitMap = new Map([
        ['۰','0'],['۱','1'],['۲','2'],['۳','3'],['۴','4'],['۵','5'],['۶','6'],['۷','7'],['۸','8'],['۹','9'],
        ['٠','0'],['١','1'],['٢','2'],['٣','3'],['٤','4'],['٥','5'],['٦','6'],['٧','7'],['٨','8'],['٩','9']
    ]);
    
    function fromPersianDigits(str) {
        if (!str) return str;
        return str.toString().replace(/[۰-۹٠-٩]/g, ch => digitMap.get(ch) || ch);
    }

    // Load services from API - Optimized
    async function loadServices() {
        const container = document.getElementById('services-container');
        if (!container) return console.error('services-container not found');
        
        try {
            const response = await fetch(`/api/booking/${salonId}/services`);
            if (!response.ok) throw new Error('Network response was not ok');
            
            const data = await response.json();
            data.success ? renderServices(data.data) : 
                (container.innerHTML = `<div class="text-center py-8 text-red-500">خطا در بارگذاری خدمات: ${data.message || 'نامعلوم'}</div>`);
        } catch (error) {
            console.error('Error loading services:', error);
            container.innerHTML = `<div class="text-center py-8 text-red-500">خطا در بارگذاری خدمات: ${error.message || 'خطای شبکه'}</div>`;
        }
    }

    // Render services in the UI - Optimized with DocumentFragment
    function renderServices(services) {
        const container = document.getElementById('services-container');
        if (!container) return console.error('services-container not found');
        
        const fragment = document.createDocumentFragment();
        
        services.forEach(service => {
            const serviceDiv = document.createElement('div');
            serviceDiv.className = 'service-item self-stretch h-16 relative bg-white rounded-lg shadow-[0px_3px_15px_0px_rgba(65,105,225,0.08)] cursor-pointer hover:bg-gray-50 transition-colors';
            serviceDiv.dataset.serviceId = service.id;

            const firstAvailable = (service.next_available?.jalali_date && service.next_available?.time) ?
                `اولین نوبت خالی : ${service.next_available.jalali_date} - ${toPersianDigits(service.next_available.time)}` :
                'در حال بررسی نوبت‌های خالی...';

            serviceDiv.innerHTML = `
                <div class="flex items-center h-full px-4">
                    <div class="flex-1 flex flex-col justify-center items-end gap-1">
                        <div class="text-right text-neutral-700 text-base font-bold font-peyda">${service.name}</div>
                        <div class="text-right text-neutral-400 text-sm font-normal font-iranyekan">${firstAvailable}</div>
                    </div>
                </div>
            `;

            serviceDiv.addEventListener('click', () => selectService(service, serviceDiv));
            fragment.appendChild(serviceDiv);
        });
        
        container.innerHTML = '';
        container.appendChild(fragment);
    }

    // Change service - go back to service selection
    function changeService() {
        // Hide calendar and time slots, show services (guarded)
        const calendarSection = document.getElementById('calendar-section'); if (calendarSection) calendarSection.classList.add('hidden');
        const timeSlotsSection = document.getElementById('time-slots-section'); if (timeSlotsSection) timeSlotsSection.classList.add('hidden');
        const servicesContainer = document.getElementById('services-container'); if (servicesContainer) servicesContainer.classList.remove('hidden');
        const searchBarSection = document.getElementById('search-bar-section'); if (searchBarSection) searchBarSection.classList.remove('hidden');
        document.getElementById('step-1-header')?.classList.remove('hidden');

        // Clear selections
        selectedServices = [];
        selectedDate = null;
        selectedDateTime = null;

        // Remove any continue buttons
        const continueBtn = document.getElementById('continue-btn');
        if (continueBtn) continueBtn.remove();

        const continueTimeBtn = document.getElementById('continue-time-btn');
        if (continueTimeBtn) continueTimeBtn.remove();
    }
    
    // Select service
    async function selectService(service, element) {
        // Clear previous selections
        selectedServices = [];
        document.querySelectorAll('.service-item').forEach(item => {
            item.classList.remove('bg-teal-50', 'border-teal-300', 'border');
            item.classList.add('bg-white');
        });
        
        // Select current service
        selectedServices.push(service);
        element.classList.remove('bg-white');
        element.classList.add('bg-teal-50', 'border-teal-300', 'border');
        
        // Remove continue button if exists
        const continueBtn = document.getElementById('continue-btn');
        if (continueBtn) {
            continueBtn.remove();
        }
        
        // Show calendar directly
        await showTimeSelection();
    }
    
    // Show time selection
    async function showTimeSelection() {
        if (selectedServices.length === 0) {
            alert('لطفا حداقل یک خدمت انتخاب کنید');
            return;
        }
        
        // Hide services container and search bar, show calendar section
        document.getElementById('services-container')?.classList.add('hidden');
        document.getElementById('search-bar-section')?.classList.add('hidden');
        document.getElementById('step-1-header')?.classList.add('hidden');
        document.getElementById('calendar-section')?.classList.remove('hidden');
        
        // Update selected service name (guarded)
        const ssDisp = document.getElementById('selected-service-display'); if (ssDisp) ssDisp.textContent = selectedServices[0].name;
        const calendarSsDisp = document.getElementById('calendar-selected-service'); if (calendarSsDisp) calendarSsDisp.textContent = selectedServices[0].name;
        
        await updateCalendarWithHolidays();
    }
    
    // Persian calendar utilities
    // Month names are provided by the calendar API (months[].header.jalali).
    // We will extract month names from `calendarMonths` when available.

    function extractMonthNameFromHeader(header) {
        if (!header) return '';
        // header.jalali may be like "1404 آذر" or "آذر 1404" or just "آذر"
        try {
            const h = String(header.jalali || header || '').trim();
            if (!h) return '';
            // remove year numbers and return the last word that is non-numeric
            const parts = h.split(/\s+/).filter(Boolean);
            for (let i = parts.length - 1; i >= 0; i--) {
                const p = parts[i];
                if (!/^\d+$/.test(fromPersianDigits(p.replace(/[^0-9]/g, '')))) return p;
            }
            return parts[parts.length - 1] || '';
        } catch (e) { return '' }
    }
    
    
    // Iran public holidays - populated from API data
    let iranHolidays = [];

    // Full calendar months cache (API-driven). Keys by Persian year.
    let calendarMonthsCache = {};
    let calendarMonthsPromises = {};
    let calendarMonths = []; // months for the currently loaded year

    // All holiday data is now derived exclusively from the calendar API months[].days[]
    // Legacy fallbacks, manual overrides and multi-endpoint loaders have been removed
    // to ensure the calendar UI is driven only by the canonical endpoint.

    // Load full calendar months for a Persian year from the canonical calendar endpoint
    async function loadCalendarForYear(persianYear) {
        console.log(`Loading full calendar (months[]) for year ${persianYear} from calendar API...`);

        if (calendarMonthsCache[persianYear]) {
            console.log(`Using cached calendar months for year ${persianYear}`);
            calendarMonths = calendarMonthsCache[persianYear];
            return calendarMonths;
        }

        if (calendarMonthsPromises[persianYear]) {
            console.log(`Awaiting in-flight calendar fetch for year ${persianYear}...`);
            calendarMonths = await calendarMonthsPromises[persianYear];
            return calendarMonths;
        }

        calendarMonthsPromises[persianYear] = (async () => {
            try {
                const res = await fetch(`/api/calendar-proxy?year=${persianYear}`, { method: 'GET', headers: { 'Accept': 'application/json' } });
                if (!res.ok) {
                    throw new Error(`Calendar API failed with status ${res.status}`);
                }
                const data = await res.json();
                let months = parsePersianCalendarMonths(data, persianYear);
                if (!months.length) {
                    console.warn('Calendar API returned empty months array, using fallback calendar.');
                    months = buildFallbackCalendar(persianYear);
                }
                calendarMonthsCache[persianYear] = months;
                calendarMonths = months;
                console.log(`Loaded calendar months for ${persianYear}:`, months);
                return months;
            } catch (e) {
                console.error('Error loading calendar months for year', persianYear, e);
                const fallbackMonths = buildFallbackCalendar(persianYear);
                calendarMonthsCache[persianYear] = fallbackMonths;
                calendarMonths = fallbackMonths;
                console.warn('Using locally generated fallback calendar for year', persianYear);
                return fallbackMonths;
            } finally {
                delete calendarMonthsPromises[persianYear];
            }
        })();

        return calendarMonthsPromises[persianYear];
    }

    // Parse months[].days[] into a normalized months array. Each month contains days with:
    // { jalali: Number, isHoliday: Boolean, name: String|null, gregorian: String|null, weekdayPersian: 0-6 }
    function parsePersianCalendarMonths(apiData, persianYear) {
        const months = [];
        if (!Array.isArray(apiData)) return months;

        apiData.forEach((month, idx) => {
            const parsedMonth = { header: month.header || {}, days: [] };
            if (!Array.isArray(month.days)) {
                months.push(parsedMonth);
                return;
            }

            month.days.forEach(day => {
                // Skip disabled days (previous/next month days)
                if (day.disabled === true) return;

                const events = day.events || {};
                const isHoliday = events.isHoliday === true || (Array.isArray(events.list) && events.list.some(ev => ev && ev.isHoliday === true));

                let name = null;
                if (Array.isArray(events.list)) {
                    const first = events.list.find(ev => ev && ev.isHoliday === true && ev.event);
                    if (first && first.event) name = first.event;
                }
                if (!name && events.holidayType) name = events.holidayType;

                // Holiday type
                let holidayType = '';
                if (Array.isArray(events.list)) {
                    const hasHijri = events.list.some(ev => ev && ev.calendarType === 'hijri');
                    if (hasHijri) holidayType = 'hijri';
                    else {
                        const first = events.list.find(ev => ev && ev.isHoliday === true && ev.calendarType);
                        if (first && first.calendarType) holidayType = first.calendarType;
                    }
                }
                if (!holidayType && events.holidayType) holidayType = events.holidayType;

                // Determine jalali and hijri day numbers from day.day if present
                let jalali = null;
                let hijri = null;
                if (day.day && typeof day.day.jalali !== 'undefined' && day.day.jalali !== null) {
                    try {
                        const raw = fromPersianDigits(String(day.day.jalali));
                        const digits = raw.replace(/[^0-9]/g, '');
                        if (digits.length) jalali = parseInt(digits, 10);
                    } catch (e) { jalali = null; }
                }

                // Extract hijri if provided (many APIs return hijri in Eastern Arabic numerals)
                if (day.day && typeof day.day.hijri !== 'undefined' && day.day.hijri !== null) {
                    try {
                        const rawH = fromPersianDigits(String(day.day.hijri));
                        const digitsH = rawH.replace(/[^0-9]/g, '');
                        if (digitsH.length) hijri = parseInt(digitsH, 10);
                    } catch (e) { hijri = null; }
                } else if (day.day && typeof day.day === 'string') {
                    try {
                        const s = fromPersianDigits(day.day.toString());
                        const m = s.match(/(\d{4})[-\/]?(\d{1,2})[-\/]?(\d{1,2})/);
                        if (m && m[3]) jalali = parseInt(m[3].replace(/^0+/, ''), 10);
                        else {
                            const simple = s.replace(/[^0-9]/g, '');
                            if (simple.length) jalali = parseInt(simple.replace(/^0+/, ''), 10);
                        }
                    } catch (e) { jalali = null; }
                }



                // Build a robust ISO gregorian date and weekday for each Jalali day using conversion
                let greg = null;
                let weekdayPersian = null; // 0=Saturday .. 6=Friday

                try {
                    if (jalali !== null) {
                        const persianMonth = idx + 1; // 1-based
                        // Use the shared helper to compute ISO gregorian date
                        greg = persianToGregorian(persianYear, persianMonth, jalali);

                        const d = new Date(greg + 'T00:00:00');
                        if (!isNaN(d.getTime())) {
                            const jsWeek = d.getDay(); // JS getDay(): Sun=0..Sat=6
                            weekdayPersian = (jsWeek + 1) % 7; // Persian: Sat=0..Fri=6
                        }
                    }
                } catch (e) {
                    console.warn('Failed computing gregorian for', persianYear, idx + 1, jalali, e);
                }

                // Keep raw day data for later reference as well
                parsedMonth.days.push({ jalali: jalali, hijri: hijri, isHoliday: !!isHoliday, name: name || null, gregorian: greg || null, weekdayPersian, holidayType, raw: day });
            });

            months.push(parsedMonth);
        });

        // POST-PROCESSING: Shift Hijri holidays by +1 day (User Request)
        // If a day is a Hijri holiday, the holiday status is moved to the NEXT day.
        
        const shifts = []; // Store {fromM, fromD} to avoid chain reaction during iteration

        for (let m = 0; m < months.length; m++) {
            const month = months[m];
            for (let d = 0; d < month.days.length; d++) {
                const day = month.days[d];
                if (day.isHoliday && day.holidayType === 'hijri') {
                    shifts.push({m: m, d: d});
                }
            }
        }

        // Apply shifts
        shifts.forEach(shift => {
            const m = shift.m;
            const d = shift.d;
            const currentDay = months[m].days[d];
            
            // 1. Unmark current day
            // We only unmark if it doesn't have other holiday types (simplification: assume Hijri dominates)
            currentDay.isHoliday = false;
            // Keep name? No, move it.
            const holidayName = currentDay.name;
            currentDay.name = null;
            currentDay.holidayType = '';

            // 2. Mark next day
            let nextDay = null;
            if (d < months[m].days.length - 1) {
                // Same month, next day
                nextDay = months[m].days[d + 1];
            } else if (m < months.length - 1) {
                // Next month, first day
                if (months[m + 1].days.length > 0) {
                    nextDay = months[m + 1].days[0];
                }
            }
            
            if (nextDay) {
                nextDay.isHoliday = true;
                nextDay.holidayType = 'hijri'; // Keep as hijri to maintain yellow styling
                // Append name if existing
                nextDay.name = nextDay.name ? (nextDay.name + ' - ' + holidayName) : holidayName;
            }
        });

        console.log(`Parsed calendar months for ${persianYear} (count: ${months.length})`);
        return months;
    }

    // Check if a Persian date is a holiday
    function isHoliday(persianDate) {
        const holiday = iranHolidays.find(h => 
            h.month === persianDate.month && h.day === persianDate.day
        );
        
        if (holiday) {
            console.log(`Holiday found: ${holiday.name} on ${persianDate.year}/${persianDate.month}/${persianDate.day}`);
        }
        
        return !!holiday;
    }

    // Get holiday name for a Persian date
    function getHolidayName(persianDate) {
        const holiday = iranHolidays.find(h => 
            h.month === persianDate.month && h.day === persianDate.day
        );
        return holiday ? holiday.name : null;
    }

    // Persian calendar helper functions

    // Check if a Persian date is a registered Iran holiday. Returns holiday name or null.
    function isIranHoliday(year, month, day) {
        // month parameter is now 1-based (1-12)

        console.log(`🔍 Checking holiday for ${year}/${month}/${day} (total holidays: ${iranHolidays.length})`);
        console.log('Current iranHolidays:', iranHolidays);

        // Normalize types to numbers for robust comparison
        const holiday = iranHolidays.find(h =>
            Number(h.month) === Number(month) && Number(h.day) === Number(day)
        );

        if (holiday) {
            console.log(`✅ Found holiday: ${holiday.name} on ${year}/${month}/${day} (stored for year ${holiday.year})`);
            return holiday;
        }

        console.log(`❌ No holiday found for ${year}/${month}/${day}`);
        return null;
    }
    
    // Initialize with current Persian date from server
    const today = new Date('{{ $serverDate }}');
    
    // Use server-calculated Persian date (more accurate than client-side conversion)
    let currentPersianYear = {{ $persianYear }};
    let currentPersianMonth = {{ $persianMonth }} - 1; // Convert to 0-based (0-11)
    let currentPersianDay = {{ $persianDay }};
    
    let startDayOfWeek = 0; // For calendar calculations
    let lastLoadedYear = null; // Track which year's holidays are loaded
    
    console.log('📅 Calendar Initialization:');
    console.log('Server Date:', '{{ $serverDate }}');
    console.log('Persian Date (from server):', `${currentPersianYear}/${currentPersianMonth + 1}/${currentPersianDay}`);
    console.log('Current Persian Year:', currentPersianYear);
    console.log('Current Persian Month (0-based):', currentPersianMonth);
    
    // Accurate Persian date conversion using a reliable algorithm
    function gregorianToPersian(gDate) {
        const year = gDate.getFullYear();
        const month = gDate.getMonth() + 1;
        const day = gDate.getDate();
        
        console.log(`Converting Gregorian date: ${year}-${month}-${day}`);
        
        // Use standard Persian calendar conversion algorithm
        const PERSIAN_EPOCH = 1948321; // Julian day of 1/1/1 Persian calendar
        
        // Calculate Julian day number
        let a = Math.floor((14 - month) / 12);
        let y = year - a;
        let m = month + 12 * a - 3;
        
        let jd = day + Math.floor((153 * m + 2) / 5) + 365 * y + Math.floor(y / 4) - Math.floor(y / 100) + Math.floor(y / 400) + 1721119;
        
        // Convert Julian day to Persian calendar
        let persianYear, persianMonth, persianDay;
        
        let epyear = jd - PERSIAN_EPOCH;
        let cycle = Math.floor(epyear / 1029983);
        let cyear = epyear % 1029983;
        
        let ycycle;
        if (cyear < 366) {
            ycycle = 0;
        } else {
            ycycle = Math.floor((cyear - 366) / 365);
        }
        
        persianYear = cycle * 2816 + ycycle + 1;
        
        let yday = cyear - (ycycle > 0 ? 365 * ycycle + 366 : 0) + 1;
        
        if (yday <= 186) {
            persianMonth = Math.ceil(yday / 31);
            persianDay = yday - (persianMonth - 1) * 31;
        } else {
            persianMonth = Math.ceil((yday - 186) / 30) + 6;
            persianDay = yday - 186 - (persianMonth - 7) * 30;
        }
        
        // Ensure we have valid day
        if (persianDay <= 0) {
            persianMonth--;
            if (persianMonth <= 0) {
                persianYear--;
                persianMonth = 12;
            }
            persianDay = (persianMonth <= 6) ? 31 : 30;
        }
        
        const result = { year: persianYear, month: persianMonth, day: persianDay };
        console.log(`Converted to Persian: ${result.year}/${result.month}/${result.day}`);
        
        return result;
    }

    // Convert Persian (Jalali) date to Gregorian ISO string (YYYY-MM-DD)
    function persianToGregorian(pYear, pMonth, pDay) {
        pYear = Number(pYear); pMonth = Number(pMonth); pDay = Number(pDay);
        const epbase = pYear - (pYear >= 0 ? 474 : 473);
        const epyear = 474 + (epbase % 2820);
        const mdays = pMonth <= 7 ? (pMonth - 1) * 31 : ((pMonth - 1) * 30) + 6;
        // Use canonical Persian epoch constant so conversions match the API (1948321)
        const jdn = pDay + mdays + Math.floor((epyear * 682 - 110) / 2816) + (epyear - 1) * 365 + Math.floor(epbase / 2820) * 1029983 + 1948321;

        // JDN -> Gregorian
        let j = jdn + 32044;
        let g = Math.floor(j / 146097);
        let dg = j % 146097;
        let c = Math.floor((Math.floor(dg / 36524) + 1) * 3 / 4);
        let dc = dg - c * 36524;
        let b = Math.floor(dc / 1461);
        let db = dc % 1461;
        let a = Math.floor((Math.floor(db / 365) + 1) * 3 / 4);
        let da = db - a * 365;
        let y = g * 400 + c * 100 + b * 4 + a;
        let m = Math.floor((da * 5 + 308) / 153) - 2;
        let d = da - Math.floor((m + 4) * 153 / 5) + 122;
        let Y = y - 4800 + Math.floor((m + 2) / 12);
        let M = (m + 2) % 12 + 1;
        let D = d + 1;
        return `${Y.toString().padStart(4,'0')}-${M.toString().padStart(2,'0')}-${D.toString().padStart(2,'0')}`;
    }

    function isPersianLeapYear(year) {
        const epbase = year - (year >= 0 ? 474 : 473);
        const epyear = 474 + (epbase % 2820);
        return ((epyear * 682) % 2816) < 682;
    }

    function getPersianMonthLength(year, month) {
        if (month <= 6) return 31;
        if (month <= 11) return 30;
        return isPersianLeapYear(year) ? 30 : 29;
    }

    function buildFallbackCalendar(year) {
        const months = [];
        for (let m = 1; m <= 12; m++) {
            const daysInMonth = getPersianMonthLength(year, m);
            const days = [];
            for (let d = 1; d <= daysInMonth; d++) {
                let gregorian = '';
                try {
                    gregorian = persianToGregorian(year, m, d);
                } catch (error) {
                    console.warn('Fallback calendar conversion failed', year, m, d, error);
                }
                let weekdayPersian = null;
                if (gregorian) {
                    const gDate = new Date(gregorian + 'T00:00:00');
                    if (!isNaN(gDate.getTime())) {
                        weekdayPersian = (gDate.getDay() + 1) % 7;
                    }
                }
                days.push({
                    jalali: d,
                    hijri: null,
                    isHoliday: false,
                    name: null,
                    gregorian: gregorian || null,
                    weekdayPersian,
                    holidayType: '',
                    raw: {}
                });
            }
            months.push({
                header: { jalali: `${year} ${persianMonthNames[m - 1] || ''}` },
                days
            });
        }
        return months;
    }

    // Note: day counts and conversions are derived from the calendar API; legacy conversion helpers removed.
    
    async function updateCalendarNavigation() {
        // Holiday data is loaded via updateCalendarWithHolidays(); no legacy loaders are called here.

        // Center (selected/current) - show the month/year we're currently viewing using API month headers
        const selectedYearEl = document.getElementById('selected-year'); if (selectedYearEl) selectedYearEl.textContent = toPersianDigits(currentPersianYear);
        const currentMonthName = (calendarMonths && calendarMonths[currentPersianMonth]) ? extractMonthNameFromHeader(calendarMonths[currentPersianMonth].header) : '';
        const selectedMonthEl = document.getElementById('selected-month'); if (selectedMonthEl) selectedMonthEl.textContent = currentMonthName || '';

        // Left side (next year/month) - show next year for year navigation, next month for month navigation
        const nextYear = currentPersianYear + 1;
        const nextMonthIndex = currentPersianMonth === 11 ? 0 : currentPersianMonth + 1;
        const nextMonthName = (calendarMonths && calendarMonths[nextMonthIndex]) ? extractMonthNameFromHeader(calendarMonths[nextMonthIndex].header) : '';

        const currentYearEl = document.getElementById('current-year'); if (currentYearEl) currentYearEl.textContent = toPersianDigits(nextYear);
        const currentMonthEl = document.getElementById('current-month'); if (currentMonthEl) currentMonthEl.textContent = nextMonthName || '';

        // Right side (prev year/month) - show previous year for year navigation, previous month for month navigation
        const prevYear = currentPersianYear - 1;
        const prevMonthIndex = currentPersianMonth === 0 ? 11 : currentPersianMonth - 1;
        const prevMonthName = (calendarMonths && calendarMonths[prevMonthIndex]) ? extractMonthNameFromHeader(calendarMonths[prevMonthIndex].header) : '';

        const prevMonthNameEl = document.getElementById('prev-month-name'); if (prevMonthNameEl) prevMonthNameEl.textContent = prevMonthName || '';
        const prevYearNameEl = document.getElementById('prev-year-name'); if (prevYearNameEl) prevYearNameEl.textContent = toPersianDigits(prevYear);
    }

    // Wrapper function to update calendar (API-driven)
    async function updateCalendarWithHolidays() {
        console.log(`🔄 Updating calendar (API-driven) for year ${currentPersianYear}, month ${currentPersianMonth + 1}`);

        // Load full calendar months[] for the year from the single canonical endpoint
        if (!calendarMonths || calendarMonths.length === 0 || lastLoadedYear !== currentPersianYear) {
            console.log(`📅 Loading calendar months for year ${currentPersianYear}...`);
            await loadCalendarForYear(currentPersianYear);

            // Build iranHolidays as a flattened list derived strictly from the months[] data
            const flat = [];
            calendarMonths.forEach((m, mi) => {
                const monthIndex = mi + 1; // 1-based
                if (!m || !Array.isArray(m.days)) return;
                m.days.forEach(d => {
                    if (d && d.isHoliday) {
                        flat.push({
                            name: d.name || 'تعطیل رسمی',
                            month: Number(monthIndex),
                            day: d.jalali === null ? null : Number(d.jalali),
                            type: 'national',
                            year: Number(currentPersianYear),
                            sourceCalendar: 'calendar-api',
                            raw: d
                        });
                    }
                });
            });

            iranHolidays = flat;
            lastLoadedYear = currentPersianYear;
            console.log(`✅ Calendar months loaded for ${currentPersianYear}. Holidays count derived: ${iranHolidays.length}`);

        } else {
            console.log(`✅ Using cached calendar months for year ${currentPersianYear}. Holidays count: ${iranHolidays.length}`);
        }

        await updateCalendarNavigation();
        generateCalendar();
    }
    
    function generateCalendar() {
        const calendarDays = document.getElementById('calendar-days');
        if (!calendarDays) return;
        
        const fragment = document.createDocumentFragment();

        console.log(`Generating API-driven calendar for ${currentPersianYear}/${currentPersianMonth + 1}`);

        const monthObj = (calendarMonths && calendarMonths[currentPersianMonth]) ? calendarMonths[currentPersianMonth] : null;
        if (!monthObj) {
            console.warn('No month data available from calendar API for current month.');
            // render empty grid with correct number of weeks (fallback to 29)
            const emptyWeeks = 5;
            for (let w = 0; w < emptyWeeks; w++) {
                const weekDiv = document.createElement('div');
                weekDiv.className = 'w-full self-stretch h-14 sm:h-16 inline-flex justify-center items-center gap-1 sm:gap-1.5 md:gap-2';
                for (let c = 0; c < 7; c++) {
                    const cell = document.createElement('div');
                    cell.className = 'calendar-day w-10 sm:w-12 md:w-14 h-12 sm:h-14 md:h-16 flex-shrink-0 relative rounded-lg bg-transparent border-transparent empty';
                    weekDiv.appendChild(cell);
                }
                calendarDays.appendChild(weekDiv);
            }
            return;
        }

        // Build a day-indexed month array so each index corresponds to jalali day-1
        const rawDays = Array.isArray(monthObj.days) ? monthObj.days : [];
        const parsedDays = rawDays.filter(d => typeof d.jalali === 'number' && d.jalali !== null);
        let maxDay = 0;
        parsedDays.forEach(d => { const jd = Number(d.jalali); if (jd > maxDay) maxDay = jd; });
        const monthArray = new Array(maxDay).fill(null);
        parsedDays.forEach(d => { monthArray[Number(d.jalali) - 1] = d; });

        // Compute startDayOfWeek robustly. 
        // We ignore monthObj.startIndex from API as it can be unreliable (e.g. Dey 1404 returns 0/Sat but is Mon).
        // Instead, we calculate it from the first day of the month.
        let computedStart = null;
        
        // Try to find the first day (jalali 1)
        const firstDay = monthArray[0];
        if (firstDay && typeof firstDay.weekdayPersian === 'number') {
            computedStart = firstDay.weekdayPersian;
        } else {
            // If first day missing or no weekday, compute from any valid day
            for (let i = 0; i < monthArray.length; i++) {
                const dd = monthArray[i];
                if (dd && typeof dd.weekdayPersian === 'number' && typeof dd.jalali === 'number') {
                    const jal = Number(dd.jalali);
                    const w = Number(dd.weekdayPersian);
                    // (w - (jal - 1)) % 7 ... handling negative modulo
                    computedStart = (w - ((jal - 1) % 7) + 7) % 7;
                    break;
                }
            }
        }
        
        // Fallback if still null (should not happen if we have days)
        if (computedStart === null) {
             // Try to compute from current year/month/1
             try {
                 const g = persianToGregorian(currentPersianYear, currentPersianMonth + 1, 1);
                 const d = new Date(g + 'T00:00:00');
                 const jsWeek = d.getDay();
                 computedStart = (jsWeek + 1) % 7;
             } catch(e) { computedStart = 0; }
        }
        
        // Use the computed start directly (0=Saturday..6=Friday)
        startDayOfWeek = computedStart;

        console.debug('MonthArray jalali:', monthArray.map(d => d ? d.jalali : null));
        console.debug('startDayOfWeek:', startDayOfWeek);

        const totalDays = monthArray.length;
        const weeks = Math.ceil((totalDays + startDayOfWeek) / 7);

        const today = new Date();
        const todayPersian = gregorianToPersian(today);
        const todayDateZero = new Date(); todayDateZero.setHours(0,0,0,0);

        for (let w = 0; w < weeks; w++) {
            const weekDiv = document.createElement('div');
            weekDiv.className = 'w-full self-stretch h-14 sm:h-16 inline-flex justify-center items-center gap-1 sm:gap-1.5 md:gap-2';

                        for (let c = 0; c < 7; c++) {
                            const cell = document.createElement('div');
                            cell.className = 'calendar-day w-10 sm:w-12 md:w-14 h-12 sm:h-14 md:h-16 flex-shrink-0 relative rounded-lg shadow-[0px_3px_15px_0px_rgba(65,105,225,0.08)] border-b-2';

                // compute whether this slot should be empty or a day
                const globalPos = w * 7 + c;
                const dayPos = globalPos - startDayOfWeek;
                if (dayPos < 0 || dayPos >= totalDays) {
                    cell.className += ' empty bg-transparent border-transparent';
                    weekDiv.appendChild(cell);
                    continue;
                }

                const d = monthArray[dayPos];
                
                // Debug log for the first few days to check gregorian date
                if (dayPos < 3) {
                    console.log(`Day ${dayPos + 1} data:`, d);
                    if (d && d.gregorian) {
                        console.log(`Gregorian from API: ${d.gregorian}`);
                    } else {
                        console.log('No Gregorian date from API, will use fallback conversion');
                    }
                }

                const jalaliDay = d && typeof d.jalali !== 'undefined' ? d.jalali : null;
                const hijriDay = d && typeof d.hijri !== 'undefined' ? d.hijri : null;

                // holiday flags and names
                const isHolidayFlag = d ? !!d.isHoliday : false;
                const holidayName = d && d.name ? d.name : null;
                // prefer explicit holidayType from parsed day, but fallback to raw.events.holidayType
                const holidayType = (d && d.holidayType) ? d.holidayType : (d && d.raw && d.raw.events && d.raw.events.holidayType ? d.raw.events.holidayType : '');

                // For Hijri holidays show the Hijri day number, otherwise show Jalali.
                // Keep jalaliDay as fallback when hijri is missing.
                const displayDay = (holidayType === 'hijri' && hijriDay) ? hijriDay : jalaliDay;

                // Determine holiday class - Unified to red for all types as per user request
                let holidayClass = 'holiday';

                // Determine isToday using jalali numbers
                const isToday = (currentPersianYear === todayPersian.year && (currentPersianMonth + 1) === todayPersian.month && jalaliDay === todayPersian.day);

                // Determine isPast by using gregorian date when available
                let isPastDate = false;
                        if (d && d.gregorian) {
                    try {
                        const gd = new Date(d.gregorian);
                        gd.setHours(0,0,0,0);
                        isPastDate = gd.getTime() < todayDateZero.getTime();
                    } catch (e) { isPastDate = false; }
                        } else {
                            // missing gregorian from parsed data — log for debugging
                            console.warn('Calendar cell missing gregorian date (will attempt fallback when selecting):', d);
                        }

                const isFriday = (c === 6);

                if (isPastDate) {
                    cell.className += ' bg-gradient-to-b from-gray-300/30 to-gray-400/50 border-gray-400 cursor-not-allowed opacity-50';
                    cell.title = 'در روزهای گذشته نمیشه نوبت ثبت کرد';
                    cell.addEventListener('click', function(e) { e.preventDefault(); showTooltip(this, 'در روزهای گذشته نمیشه نوبت ثبت کرد'); });
                } else if (isHolidayFlag) {
                    // Unified Red Style for all holidays
                    cell.className += ' bg-red-100 border-red-500 cursor-pointer';
                    cell.style.backgroundColor = '#fef2f2';
                    cell.style.borderColor = '#ef4444';
                    cell.style.borderWidth = '2px';
                    cell.style.boxShadow = '0 0 0 2px rgba(239, 68, 68, 0.2)';
                    
                    cell.classList.add(holidayClass);
                    const label = holidayName || 'تعطیل رسمی';
                    cell.title = label;
                    cell.setAttribute('aria-label', label + ' - تعطیل رسمی');
                    cell.addEventListener('mouseenter', function() { showTooltip(this, label); });
                    cell.addEventListener('click', function() { selectCalendarDate(this); });
                } else if (isToday) {
                    cell.className += ' bg-gradient-to-b from-blue-500/20 to-blue-500/40 border-blue-500 cursor-pointer ring-2 ring-blue-400';
                } else if (isFriday) {
                    cell.className += ' bg-gradient-to-b from-rose-500/0 to-rose-500/30 border-rose-500 cursor-pointer';
                } else {
                    cell.className += ' bg-gradient-to-b from-black/0 to-black/10 border-zinc-900 hover:bg-gradient-to-b hover:from-teal-500/0 hover:to-teal-500/20 hover:border-teal-500 cursor-pointer';
                }

                const dayNumber = document.createElement('div');
                dayNumber.className = `day-number absolute inset-0 flex items-center justify-center text-center text-2xl sm:text-3xl font-bold font-iranyekan ${isPastDate ? 'text-gray-400' : isHolidayFlag ? 'text-red-700' : isToday ? 'text-blue-600' : isFriday ? 'text-rose-500' : 'text-neutral-700'}`;
                if (isHolidayFlag) dayNumber.style.color = '#b91c1c';

                // Show Jalali day as the main large number (Standard view)
                // Hijri badge removed as per user request
                const mainDayValue = jalaliDay !== null ? jalaliDay : '';
                dayNumber.innerHTML = `${mainDayValue ? toPersianDigits(mainDayValue) : ''}`;

                cell.appendChild(dayNumber);
                cell.dataset.day = jalaliDay;
                cell.dataset.date = `${currentPersianYear}-${currentPersianMonth + 1}-${jalaliDay}`;
                // Ensure we always provide a full ISO gregorian date if possible (fallback to conversion)
                try {
                    if (d && d.gregorian) {
                        cell.dataset.gregorian = d.gregorian;
                    } else if (jalaliDay !== null) {
                        cell.dataset.gregorian = persianToGregorian(currentPersianYear, currentPersianMonth + 1, jalaliDay);
                    } else {
                        cell.dataset.gregorian = '';
                    }
                } catch (e) {
                    cell.dataset.gregorian = '';
                    console.warn('Failed to compute fallback gregorian for cell', currentPersianYear, currentPersianMonth + 1, jalaliDay, e);
                }
                cell.dataset.isPast = isPastDate.toString();

                if (!isPastDate && !isHolidayFlag) {
                    cell.addEventListener('click', function() { selectCalendarDate(this); });
                }

                weekDiv.appendChild(cell);
            }

            fragment.appendChild(weekDiv);
        }
        
        calendarDays.innerHTML = '';
        calendarDays.appendChild(fragment);
    }
    
    // Optimized tooltip - debounced and using RAF
    let tooltipTimeout = null;
    
    function showTooltip(element, message) {
        const existingTooltip = document.querySelector('.calendar-tooltip');
        existingTooltip?.remove();
        
        const tooltip = document.createElement('div');
        tooltip.className = 'calendar-tooltip absolute z-50 bg-gray-800 text-white text-sm px-3 py-2 rounded-lg shadow-lg whitespace-nowrap';
        tooltip.textContent = message;
        tooltip.style.cssText = 'position:fixed;background:#1f2937;color:white;padding:8px 12px;border-radius:8px;font-size:14px;z-index:1000;box-shadow:0 4px 6px rgba(0,0,0,0.1);pointer-events:none';
        
        document.body.appendChild(tooltip);
        
        requestAnimationFrame(() => {
            const rect = element.getBoundingClientRect();
            tooltip.style.left = `${rect.left + rect.width / 2 - tooltip.offsetWidth / 2}px`;
            tooltip.style.top = `${rect.top - tooltip.offsetHeight - 10}px`;
        });
        
        if (tooltipTimeout) clearTimeout(tooltipTimeout);
        tooltipTimeout = setTimeout(() => tooltip.remove(), 3000);
    }
    
    function selectCalendarDate(dayElement) {
        // Check if it's a past date
        if (dayElement.dataset.isPast === 'true') {
            showTooltip(dayElement, 'در روزهای گذشته نمیشه نوبت ثبت کرد');
            return;
        }
        
        // Remove previous selection
        document.querySelectorAll('#calendar-days .selected').forEach(el => {
            el.classList.remove('selected', 'bg-teal-100', 'border-teal-500');
            // Restore original classes based on date type
            if (el.dataset.isPast === 'true') {
                el.className = 'calendar-day w-10 sm:w-12 md:w-14 h-12 sm:h-14 md:h-16 flex-shrink-0 relative rounded-lg shadow-[0px_3px_15px_0px_rgba(65,105,225,0.08)] border-b-2 bg-gradient-to-b from-gray-300/30 to-gray-400/50 border-gray-400 cursor-not-allowed opacity-50';
            } else {
                const day = parseInt(el.dataset.day);
                const dayOfWeek = (startDayOfWeek + (day - 1)) % 7;
                const isFriday = dayOfWeek === 6;
                
                if (isFriday) {
                    el.className = 'calendar-day w-10 sm:w-12 md:w-14 h-12 sm:h-14 md:h-16 flex-shrink-0 relative rounded-lg shadow-[0px_3px_15px_0px_rgba(65,105,225,0.08)] border-b-2 bg-gradient-to-b from-rose-500/0 to-rose-500/30 border-rose-500 cursor-pointer';
                } else {
                    el.className = 'calendar-day w-10 sm:w-12 md:w-14 h-12 sm:h-14 md:h-16 flex-shrink-0 relative rounded-lg shadow-[0px_3px_15px_0px_rgba(65,105,225,0.08)] border-b-2 bg-gradient-to-b from-black/0 to-black/10 border-zinc-900 hover:bg-gradient-to-b hover:from-teal-500/0 hover:to-teal-500/20 hover:border-teal-500 cursor-pointer';
                }
            }
        });
        
        // Add selection to clicked day
        dayElement.classList.add('selected', 'bg-teal-100', 'border-teal-500');
        dayElement.classList.remove('bg-gradient-to-b', 'from-black/0', 'to-black/10', 'border-zinc-900');
        dayElement.classList.remove('from-rose-500/0', 'to-rose-500/30', 'border-rose-500');
        
        let gregStr = dayElement.dataset.gregorian;
        // If the gregorian iso is missing or malformed, try to compute it from Jalali date
        if (!gregStr || gregStr.trim() === '') {
            const pDay = parseInt(dayElement.dataset.day, 10);
            if (isNaN(pDay)) {
                showTooltip(dayElement, 'تاریخ انتخابی ناقص است (شماره روز پیدا نشد)');
                return;
            }
            try {
                gregStr = persianToGregorian(currentPersianYear, currentPersianMonth + 1, pDay);
                console.warn('Reconstructed gregorian date from Jalali:', gregStr);
            } catch (e) {
                console.error('Failed to reconstruct gregorian date', e, dayElement.dataset);
                showTooltip(dayElement, 'تاریخ انتخابی ناقص است (اطلاعات میلادی موجود نیست)');
                return;
            }
        }
        const gDate = new Date(gregStr + 'T00:00:00');
        if (isNaN(gDate.getTime())) {
            showTooltip(dayElement, 'تاریخ انتخابی نامعتبر است');
            return;
        }

        selectedDate = {
            year: currentPersianYear,
            month: currentPersianMonth + 1,
            day: parseInt(dayElement.dataset.day),
            gregorianDate: gDate,
            gregorianDateString: gregStr // Store the raw string to avoid timezone issues
        };

        // Show time slots for selected date
        showTimeSlots(selectedDate.gregorianDateString);
    }
    
    
    function selectTimeSlot(timeSlot, element) {
        // Remove previous selection styling
        document.querySelectorAll('#time-slots-container > div').forEach(el => {
            if (!el.classList.contains('cursor-not-allowed')) {
                el.classList.remove('bg-teal-100', 'border-teal-300', 'ring-2', 'ring-teal-500');
                el.classList.add('bg-white');
            }
        });
        
        // Add selection styling to clicked element
        element.classList.remove('bg-white');
        element.classList.add('bg-teal-100', 'border-teal-300', 'ring-2', 'ring-teal-500');
        
        // Store selected time
        // Use the date string from the element's dataset if available, otherwise fallback to selectedDate
        const dateStr = element.dataset.date || (selectedDate ? selectedDate.gregorianDateString : null);
        
        // Format time to HH:MM (24h)
        const simpleTime = timeSlot.time ? timeSlot.time.split(':').slice(0, 2).join(':') : timeSlot.display_time;

        selectedDateTime = {
            date: dateStr,
            time: timeSlot.time,
            display_time: simpleTime,
            operator: timeSlot.operator_name || 'نامشخص',
            slot_id: timeSlot.slot_id || null
        };
        
        console.log('✅ Time slot selected:', selectedDateTime);
        
        // Show customer info section
        showCustomerInfoSection();
        
        // Scroll to customer info section smoothly (guarded)
        const customerInfoSectionEl = document.getElementById('customer-info-section');
        if (customerInfoSectionEl) customerInfoSectionEl.scrollIntoView({ behavior: 'smooth' });
    }

    function showTimeSlots(gregorianDateStr) {
        const serviceIds = selectedServices.map(s => s.id);
        const timeSlotsSection = document.getElementById('time-slots-section');
        const container = document.getElementById('time-slots-container');
        
        if (!timeSlotsSection || !container) return;
        
        timeSlotsSection.classList.remove('hidden');
        updateSelectedDateDisplay();
        container.innerHTML = '<div class="self-stretch text-center py-4">در حال بارگذاری...</div>';
        
        fetch(`/api/booking/${salonId}/available-times?date=${gregorianDateStr}&service_ids[]=${serviceIds.join('&service_ids[]=')}`, {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        })
        .then(response => response.ok ? response.json() : Promise.reject('Network error'))
        .then(data => {
            console.log('🕐 Available Times:', data);
            
            if (data.success && data.data?.available_times?.length) {
                const fragment = document.createDocumentFragment();
                
                data.data.available_times.forEach(timeSlot => {
                    const div = createTimeSlotElement(timeSlot, gregorianDateStr);
                    fragment.appendChild(div);
                });
                
                container.innerHTML = '';
                container.appendChild(fragment);
            } else {
                container.innerHTML = '<p class="self-stretch text-center text-gray-500 text-sm py-4">در این تاریخ نوبت خالی وجود ندارد</p>';
            }
        })
        .catch(error => {
            console.error('Error loading time slots:', error);
            container.innerHTML = '<p class="self-stretch text-center text-red-500 text-sm py-4">خطا در بارگذاری ساعات</p>';
        });
    }
    
    function createTimeSlotElement(timeSlot, dateStr) {
        const div = document.createElement('div');
        const isBooked = timeSlot.is_booked || false;
        const operatorName = timeSlot.operator_name || 'نامشخص';
        const displayTime = toPersianDigits(timeSlot.time ? timeSlot.time.split(':').slice(0, 2).join(':') : timeSlot.display_time);
        
        div.className = `self-stretch h-16 relative rounded-lg shadow-[0px_3px_15px_0px_rgba(65,105,225,0.08)] ${isBooked ? 'bg-zinc-300 cursor-not-allowed opacity-80' : 'bg-white cursor-pointer hover:bg-gray-50 transition-colors border border-transparent hover:border-gray-200'}`;
        
        div.innerHTML = `
            <div class="flex items-center justify-between h-full px-4">
                <div class="flex items-center gap-2">
                    <svg width="8" height="14" viewBox="0 0 8 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7 13L1 7L7 1" stroke="${isBooked ? '#9D9D9D' : '#171717'}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <div class="${isBooked ? 'text-neutral-500' : 'text-neutral-700'} text-base font-normal font-iranyekan">${isBooked ? 'رزرو شده' : 'رزرو نوبت'}</div>
                </div>
                <div class="flex flex-col justify-center items-end gap-1">
                    <div class="text-right" dir="ltr">
                        <span class="text-neutral-700 text-base font-bold font-iranyekan" dir="ltr">${displayTime}</span>
                        <span class="text-neutral-700 text-base font-bold font-peyda">&nbsp;ساعت</span>
                    </div>
                    <div class="text-right ${isBooked ? 'text-neutral-500' : 'text-neutral-400'} text-sm font-normal font-iranyekan">اپراتور : ${operatorName}</div>
                </div>
            </div>
        `;
        
        if (!isBooked) {
            div.dataset.date = dateStr;
            div.addEventListener('click', () => selectTimeSlot(timeSlot, div));
        }
        
        return div;
    }
    
    function updateSelectedDateDisplay() {
        // Use the global selectedDate object which contains Persian date and api-provided gregorianDate
        if (!selectedDate) return;
        const dayNames = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه', 'شنبه'];
        const gregorianDate = selectedDate.gregorianDate;
        const dayName = gregorianDate ? dayNames[gregorianDate.getDay()] : '';

        // Month name from API if available
        let monthName = '';
        if (calendarMonths && calendarMonths[selectedDate.month - 1]) {
            monthName = extractMonthNameFromHeader(calendarMonths[selectedDate.month - 1].header);
        }

        const selectedDateTextEl = document.getElementById('selected-date-text'); if (selectedDateTextEl) selectedDateTextEl.textContent = (dayName ? dayName + ' ' : '');
        const selectedDateNumberEl = document.getElementById('selected-date-number'); if (selectedDateNumberEl) selectedDateNumberEl.textContent = toPersianDigits(selectedDate.day) + ' ';
        const selectedDateMonthEl = document.getElementById('selected-date-month'); if (selectedDateMonthEl) selectedDateMonthEl.textContent = monthName || '';
    }
    
    // Navigation functions
    function changeMonth(direction) {
        if (direction === 1) {
            // Next month
            currentPersianMonth++;
            if (currentPersianMonth > 11) {
                currentPersianMonth = 0;
                currentPersianYear++;
            }
        } else {
            // Previous month
            currentPersianMonth--;
            if (currentPersianMonth < 0) {
                currentPersianMonth = 11;
                currentPersianYear--;
            }
        }
        updateCalendarWithHolidays();
    }
    
    function changeYear(direction) {
        if (direction === 1) {
            // Next year
            currentPersianYear++;
        } else {
            // Previous year
            currentPersianYear--;
        }
        updateCalendarWithHolidays();
    }
    
    // Show customer info section (new inline form)
    function showCustomerInfoSection() {
        console.log('🎯 showCustomerInfoSection called');
        console.log('selectedServices:', selectedServices);
        console.log('selectedDateTime:', selectedDateTime);
        console.log('selectedDate:', selectedDate);
        
        // Hide calendar and time slots sections when showing customer form (guarded)
        document.getElementById('calendar-section')?.classList.add('hidden');
        document.getElementById('time-slots-section')?.classList.add('hidden');
        
        // Show customer info section
        const customerSection = document.getElementById('customer-info-section');
        console.log('customer-info-section element:', customerSection);
        
        if (customerSection) {
            customerSection.classList.remove('hidden');
            console.log('✅ Customer section shown');
        } else {
            console.error('❌ customer-info-section element not found!');
        }
        
        // Populate selected service and datetime
        if (selectedServices.length > 0) {
            const serviceEl = document.getElementById('form-selected-service');
            if (serviceEl) {
                serviceEl.textContent = selectedServices[0].name;
                console.log('✅ Service populated:', selectedServices[0].name);
            }
        }
        
        if (selectedDateTime && selectedDate) {
            const persianDate = `${toPersianDigits(selectedDate.year)}/${toPersianDigits(String(selectedDate.month).padStart(2, '0'))}/${toPersianDigits(String(selectedDate.day).padStart(2, '0'))}`;
            const persianTime = toPersianDigits(selectedDateTime.display_time);
            const datetimeEl = document.getElementById('form-selected-datetime');
            if (datetimeEl) {
                // Show Persian date and time in the summary card on one line (e.g., '1404/09/08 - 10:30')
                datetimeEl.innerHTML = `<span class="text-neutral-700 text-sm font-normal font-['IRANYekanMobileFN']">${persianDate}&nbsp;-&nbsp;</span><span dir="ltr" class="text-neutral-700 text-base font-bold font-['IRANYekanMobileFN']">${persianTime}</span>`;
                console.log('✅ DateTime populated:', `${persianDate} - ${persianTime} ساعت`);
            }
        }
        
        // Scroll to customer info section instead of top
        setTimeout(() => {
            const customerSection = document.getElementById('customer-info-section');
            if (customerSection) {
                customerSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                console.log('✅ Scrolled to customer section');
            }
        }, 200);
    }
    
    // Show customer form (old modal - kept for compatibility)
    function showCustomerForm() {
        showCustomerInfoSection();
    }
    
    // تابع ارسال اطلاعات مشتری
    function submitCustomerForm(formData, csrfToken) {
        console.log('📤 Submitting booking with data:', formData);
        
        fetch(`/api/booking/${salonId}/reserve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(formData)
        })
        .then(response => {
            console.log('📥 Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📥 Response data:', data);
            if (data.success) {
                // Redirect to success page
                const appointmentId = data.data.appointment_id;
                window.location.href = `/booking/success?appointment_id=${appointmentId}`;
            } else {
                console.error('❌ Booking failed:', data);
                const params = new URLSearchParams();
                params.set('message', data.message || 'خطا در ثبت نوبت');
                if (salonId) params.set('salon_id', salonId);
                window.location.href = `/booking/error?${params.toString()}`;
            }
        })
        .catch(error => {
            console.error('❌ Error submitting reservation:', error);
            const params = new URLSearchParams();
            params.set('message', 'خطا در ثبت نوبت');
            if (salonId) params.set('salon_id', salonId);
            window.location.href = `/booking/error?${params.toString()}`;
        });
    }
    
    // Show success modal
    function showSuccessModal(appointmentData) {
        document.getElementById('customer-info-modal')?.classList.add('hidden');
        document.getElementById('customer-info-section')?.classList.add('hidden');
        
        const modal = document.getElementById('success-modal');
        const details = document.getElementById('success-details');
        
        if (details) details.innerHTML = `
            <div class="text-right">
                <p><strong>نام:</strong> ${appointmentData.customer_name}</p>
                <p><strong>تاریخ:</strong> ${appointmentData.appointment_date}</p>
                <p><strong>زمان:</strong> ${toPersianDigits(appointmentData.start_time)}</p>
                <p><strong>خدمات:</strong> ${appointmentData.services.join(', ')}</p>
                <p><strong>سالن:</strong> ${appointmentData.salon_name}</p>
            </div>
        `;
        
        if (modal) modal.classList.remove('hidden');
    }
    
    // Modal close handlers
    document.getElementById('close-customer-modal')?.addEventListener('click', () => {
        document.getElementById('customer-info-modal')?.classList.add('hidden');
    });
    
    document.getElementById('close-success-modal')?.addEventListener('click', () => {
        document.getElementById('success-modal')?.classList.add('hidden');
        location.reload();
    });
    
    // Change service button
    document.getElementById('change-service-btn')?.addEventListener('click', changeService);
    
    // Search functionality - Optimized with debounce
    let searchTimeout;
    const searchInput = document.getElementById('service-search');
    
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchTerm = e.target.value.toLowerCase();
                const serviceItems = document.querySelectorAll('.service-item');
                
                serviceItems.forEach(item => {
                    const name = item.querySelector('.font-bold')?.textContent.toLowerCase() || '';
                    item.style.display = name.includes(searchTerm) ? 'block' : 'none';
                });
            }, 300);
        }, { passive: true });
    }
    
    // Search button
    document.getElementById('search-btn')?.addEventListener('click', () => {
        document.getElementById('service-search')?.focus();
    });
    
    // Initialize
    console.log('Starting calendar initialization...');
    const _initialMonthName = (calendarMonths && calendarMonths[currentPersianMonth]) ? extractMonthNameFromHeader(calendarMonths[currentPersianMonth].header) : '';
    console.log('Initial Persian date:', currentPersianYear, currentPersianMonth, _initialMonthName);
    
    // Initialize - Optimized startup
    (async function init() {
        try {
            // Load services and calendar in parallel for faster initial load
            await Promise.all([
                loadServices(),
                updateCalendarWithHolidays()
            ]);
        } catch (e) {
            console.error('Initialization error:', e);
            const container = document.getElementById('services-container');
            if (container) container.innerHTML = `<div class="text-center py-8 text-red-500">خطا در بارگذاری: ${e?.message || 'خطای داخلی'}</div>`;
        }
    })();
    
    // Attach event listeners after DOM elements are ready
    console.log('Attaching event listeners...');

    // Global error handlers - Optimized
    const handleError = (msg, error) => {
        console.error(msg, error);
        const container = document.getElementById('services-container');
        if (container) container.innerHTML = `<div class="text-center py-8 text-red-500">${msg}: ${error?.message || 'نامعلوم'}</div>`;
    };

    window.addEventListener('error', e => handleError('خطا در صفحه', e.error || e));
    window.addEventListener('unhandledrejection', e => handleError('خطای برنامه', e.reason));
    
    // Optimized event attachment using event delegation where possible
    function addClickToAll(selector, handler) {
        const els = document.querySelectorAll(selector);
        if (!els.length) return console.log(`No elements found for: ${selector}`);
        
        console.log(`Attaching ${els.length} handlers for: ${selector}`);
        els.forEach(el => el.addEventListener('click', handler, { passive: true }));
    }
    
    addClickToAll('#next-year', async () => {
        console.log('Next year clicked');
        currentPersianYear++;
        await updateCalendarWithHolidays();
    });

    addClickToAll('#prev-year', async () => {
        console.log('Prev year clicked');
        currentPersianYear--;
        await updateCalendarWithHolidays();
    });

    addClickToAll('#next-month', async () => {
        console.log('Next month clicked');
        currentPersianMonth++;
        if (currentPersianMonth > 11) {
            currentPersianMonth = 0;
            currentPersianYear++;
        }
        await updateCalendarWithHolidays();
    });

    addClickToAll('#prev-month', async () => {
        console.log('Prev month clicked');
        currentPersianMonth--;
        if (currentPersianMonth < 0) {
            currentPersianMonth = 11;
            currentPersianYear--;
        }
        await updateCalendarWithHolidays();
    });

    // Add click handler for current-year element to navigate to next year
    addClickToAll('#current-year', async () => {
        console.log('Current year clicked - navigating to next year');
        currentPersianYear++;
        await updateCalendarWithHolidays();
    });

    // Add click handler for current-month element to navigate to next month
    addClickToAll('#current-month', async () => {
        console.log('Current month clicked - navigating to next month');
        currentPersianMonth++;
        if (currentPersianMonth > 11) {
            currentPersianMonth = 0;
            currentPersianYear++;
        }
        await updateCalendarWithHolidays();
    });

    // Add click handler for prev-month-name element to navigate to previous month
    addClickToAll('#prev-month-name', async () => {
        console.log('Previous month name clicked - navigating to previous month');
        currentPersianMonth--;
        if (currentPersianMonth < 0) {
            currentPersianMonth = 11;
            currentPersianYear--;
        }
        await updateCalendarWithHolidays();
    });

    // Add click handler for prev-year-name element to navigate to previous year
    addClickToAll('#prev-year-name', async () => {
        console.log('Previous year name clicked - navigating to previous year');
        currentPersianYear--;
        await updateCalendarWithHolidays();
    });
    
    // Event listeners for new customer info section
    
    // Back to time selection button
    document.getElementById('back-to-time-selection')?.addEventListener('click', function() {
        // Hide customer info section
        document.getElementById('customer-info-section')?.classList.add('hidden');
        
        // Show calendar and time slots sections (guarded)
        document.getElementById('calendar-section')?.classList.remove('hidden');
        document.getElementById('time-slots-section')?.classList.remove('hidden');
        
        // Scroll to time slots
        setTimeout(() => {
            const timeSlotsSection = document.getElementById('time-slots-section');
            if (timeSlotsSection) {
                timeSlotsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 100);
    });
    
    // Delegated click listener for all change-* buttons
    document.addEventListener('click', function(e) {
        const target = e.target;
        
        // Handle all change buttons with single delegated listener
        if (target.id === 'change-service-from-form' || target.closest('#change-service-from-form')) {
            changeService();
            document.getElementById('customer-info-section')?.classList.add('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        else if (target.id === 'change-time-from-form' || target.closest('#change-time-from-form')) {
            document.getElementById('customer-info-section')?.classList.add('hidden');
            showTimeSelection();
        }
        else if (target.id === 'change-service-from-otp' || target.closest('#change-service-from-otp')) {
            document.getElementById('otp-section')?.classList.add('hidden');
            clearInterval(otpTimerInterval);
            document.getElementById('services-container')?.classList.remove('hidden');
            document.getElementById('search-bar-section')?.classList.remove('hidden');
            document.getElementById('step-1-header')?.classList.remove('hidden');
            selectedServices = [];
            selectedDate = null;
            selectedDateTime = null;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        else if (target.id === 'change-time-from-otp' || target.closest('#change-time-from-otp')) {
            document.getElementById('otp-section')?.classList.add('hidden');
            clearInterval(otpTimerInterval);
            document.getElementById('calendar-section')?.classList.remove('hidden');
            document.getElementById('time-slots-section')?.classList.remove('hidden');
            setTimeout(() => {
                document.getElementById('time-slots-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        }
        else if (target.id === 'change-info-from-otp' || target.closest('#change-info-from-otp')) {
            document.getElementById('otp-section')?.classList.add('hidden');
            clearInterval(otpTimerInterval);
            const customerSection = document.getElementById('customer-info-section');
            if (customerSection) {
                customerSection.classList.remove('hidden');
                setTimeout(() => customerSection.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
            }
        }
        else if (target.id === 'change-service-from-calendar' || target.closest('#change-service-from-calendar')) {
            document.getElementById('calendar-section')?.classList.add('hidden');
            document.getElementById('time-slots-section')?.classList.add('hidden');
            document.getElementById('services-container')?.classList.remove('hidden');
            document.getElementById('search-bar-section')?.classList.remove('hidden');
            document.getElementById('step-1-header')?.classList.remove('hidden');
            selectedServices = [];
            selectedDate = null;
            selectedDateTime = null;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }, { passive: false });
    let otpTimerInterval;
    let otpTimeLeft = 87;
    let isSendingOtp = false; // Flag to prevent double sending

    // API Helpers - Optimized with better error handling
    async function callApi(endpoint, method, data) {
        try {
            const response = await fetch(`/api/booking-wizard/${salonId}/${endpoint}`, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            alert('خطا در برقراری ارتباط با سرور');
            return { success: false, message: error.message };
        }
    }

    // Check if mobile exists - Optimized
    async function checkMobile(mobile) {
        const elements = {
            btn: document.getElementById('check-mobile-btn'),
            spinner: document.getElementById('loading-spinner'),
            btnText: document.getElementById('submit-btn-text')
        };
        
        const originalText = elements.btnText?.textContent || '';
        
        if (elements.btn && elements.spinner && elements.btnText) { 
            elements.spinner.classList.remove('hidden');
            elements.btnText.textContent = 'در حال بررسی...';
            elements.btn.disabled = true;
        }

        const result = await callApi('check-customer', 'POST', { mobile });
        
        if (elements.btn && elements.spinner && elements.btnText) { 
            elements.spinner.classList.add('hidden');
            elements.btnText.textContent = originalText;
            elements.btn.disabled = false;
        }

        if (result?.success) {
            if (result.exists) {
                console.log('User exists, sending OTP');
                if (result.customer?.name) {
                    const nameInput = document.getElementById('customer-name-new');
                    if (nameInput) nameInput.value = result.customer.name;
                }
                sendOtp(mobile);
            } else {
                console.log('User is new, showing details fields');
                document.getElementById('new-user-fields')?.classList.remove('hidden');
                if (elements.btnText) elements.btnText.textContent = 'ثبت و ادامه';
                if (elements.btn) elements.btn.dataset.mode = 'submit-details';
            }
        } else {
            alert(result?.message || 'خطا در بررسی شماره موبایل');
        }
    }

    // Send OTP
    async function sendOtp(mobile) {
        if (isSendingOtp) {
            console.log('⏳ Already sending OTP, please wait...');
            return;
        }

        isSendingOtp = true;
        const result = await callApi('send-otp', 'POST', { mobile });
        isSendingOtp = false;
        
        if (result && result.success) {
            console.log('OTP Sent:', result.message);
            showOtpSection();
        } else {
            alert(result?.message || 'خطا در ارسال کد تایید');
        }
    }

    function showOtpSection() {
        document.getElementById('customer-info-section')?.classList.add('hidden');
        document.getElementById('otp-section')?.classList.remove('hidden');
        
        // Populate OTP summary cards
        if (selectedServices.length > 0) {
            const serviceEl = document.getElementById('otp-selected-service');
            if (serviceEl) serviceEl.textContent = selectedServices[0].name;
        }
        
        if (selectedDateTime && selectedDate) {
            const persianDate = `${toPersianDigits(selectedDate.year)}/${toPersianDigits(String(selectedDate.month).padStart(2, '0'))}/${toPersianDigits(String(selectedDate.day).padStart(2, '0'))}`;
            const persianTime = toPersianDigits(selectedDateTime.display_time);
            const datetimeEl = document.getElementById('otp-selected-datetime');
            if (datetimeEl) {
                // Show Persian date and time in the OTP summary card on one line
                datetimeEl.innerHTML = `<span class="text-neutral-700 text-sm font-normal font-['IRANYekanMobileFN']">${persianDate}&nbsp;-&nbsp;</span><span dir="ltr" class="text-neutral-700 text-base font-bold font-['IRANYekanMobileFN']">${persianTime}</span>`;
                console.log('✅ DateTime populated (OTP):', `${persianDate} - ${persianTime} ساعت`);
            }
        }
        
        startOtpTimer();
    }

    function startOtpTimer() {
        if (otpTimerInterval) clearInterval(otpTimerInterval);
        
        otpTimeLeft = 120;
        const resendBtn = document.getElementById('resend-otp-btn');
        const timerEl = document.getElementById('otp-timer');
        
        if (resendBtn) {
            resendBtn.disabled = true;
            resendBtn.classList.add('text-gray-400', 'cursor-not-allowed');
            resendBtn.classList.remove('text-zinc-900', 'hover:text-zinc-700');
        }
        
        if (timerEl) timerEl.textContent = `${otpTimeLeft} ثانیه`;

        otpTimerInterval = setInterval(() => {
            if (--otpTimeLeft <= 0) {
                clearInterval(otpTimerInterval);
                if (resendBtn) {
                    resendBtn.disabled = false;
                    resendBtn.classList.remove('text-gray-400', 'cursor-not-allowed');
                    resendBtn.classList.add('text-zinc-900', 'hover:text-zinc-700');
                }
            }
            if (timerEl) timerEl.textContent = `${otpTimeLeft} ثانیه`;
        }, 1000);
    }

    // Mobile input validation - Optimized with better pattern
    const mobileInput = document.getElementById('customer-mobile-new');
    if (mobileInput) {
        mobileInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 11);
        }, { passive: true });
    }

    // Customer form submission - Optimized
    const customerForm = document.getElementById('customer-form-new');
    if (customerForm) {
        customerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('check-mobile-btn');
            const mobileInput = document.getElementById('customer-mobile-new');
            const mobile = mobileInput?.value || '';
            
            if (!mobile) return alert('لطفا شماره موبایل را وارد کنید');
            if (!/^09[0-9]{9}$/.test(mobile)) return alert('لطفا شماره موبایل معتبر ایرانی وارد کنید (مثال: 09121234567)');

            if (btn?.dataset.mode === 'submit-details') {
                const name = document.getElementById('customer-name-new')?.value || '';
                if (!name) return alert('لطفا نام و نام خانوادگی را وارد کنید');
                await sendOtp(mobile);
            } else {
                await checkMobile(mobile);
            }
        });
    }

    // OTP Section Listeners
    document.getElementById('verify-otp-btn')?.addEventListener('click', async function() {
        const otpInput = document.getElementById('otp-input');
        const otp = otpInput ? otpInput.value : '';
        const mobileInput2 = document.getElementById('customer-mobile-new');
        const mobile = mobileInput2 ? mobileInput2.value : '';

        if (otp.length !== 6) {
            alert('لطفا کد 6 رقمی را کامل وارد کنید');
            return;
        }
        
        const btn = this;
        const spinner = document.getElementById('verify-loading-spinner');
        const btnText = document.getElementById('verify-btn-text');
        const originalText = btnText ? btnText.textContent : '';
        
        if (spinner && btnText) {
            spinner.classList.remove('hidden');
            btnText.textContent = 'در حال بررسی...';
        }
        btn.disabled = true;

        // Verify OTP
        const result = await callApi('verify-otp', 'POST', { mobile, otp });
        
        if (result && result.success) {
            console.log('OTP Verified, submitting booking...');
            
            // Keep loading state and change text
            if (btnText) btnText.textContent = 'در حال ثبت نوبت...';
            
            // Prepare data for submission
            const customerNameInput = document.getElementById('customer-name-new');
            const customerName = (customerNameInput ? customerNameInput.value : '') || 'کاربر مهمان';
            const customerReferralInput = document.getElementById('customer-referral-new');
            const customerReferral = customerReferralInput ? customerReferralInput.value : '';
            
            const formData = {
                salon_id: salonId,
                customer_name: customerName,
                customer_mobile: mobile,
                referral_source: customerReferral,
                service_ids: selectedServices.map(s => s.id),
                appointment_date: selectedDateTime.date,
                start_time: selectedDateTime.time,
                slot_id: selectedDateTime.slot_id
            };
            
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : null;
            // Hide error and show success
            const otpErrorBox = document.getElementById('otp-error-box');
            const otpSuccessBox = document.getElementById('otp-success-box');
            if (otpErrorBox) {
                otpErrorBox.style.display = 'none';
            }
            if (otpSuccessBox) {
                otpSuccessBox.style.display = 'flex';
            }
            submitCustomerForm(formData, csrfToken);
        } else {
            const otpErrorBox = document.getElementById('otp-error-box');
            const otpSuccessBox = document.getElementById('otp-success-box');
            if (otpErrorBox) {
                otpErrorBox.style.display = 'flex';
                otpErrorBox.querySelector('.otp-error-text').textContent = result?.message || 'کد تایید اشتباه یا منقضی شده است';
            }
            if (otpSuccessBox) {
                otpSuccessBox.style.display = 'none';
            }
            if (spinner && btnText) {
                spinner.classList.add('hidden');
                btnText.textContent = originalText;
            }
            btn.disabled = false;
        }
    });

    document.getElementById('resend-otp-btn')?.addEventListener('click', function() {
        if (!this.disabled) {
            const mobileInput3 = document.getElementById('customer-mobile-new');
            const mobile = mobileInput3 ? mobileInput3.value : '';
            console.log('Resending OTP...');
            sendOtp(mobile);
        }
    });

    document.getElementById('back-to-mobile-btn')?.addEventListener('click', function() {
        document.getElementById('otp-section')?.classList.add('hidden');
        document.getElementById('customer-info-section')?.classList.remove('hidden');
        clearInterval(otpTimerInterval);
    });
    
    // New Event Listeners for Summary Cards
    
    // Change service from calendar (Step 2 -> Step 1)
    document.getElementById('change-service-from-calendar')?.addEventListener('click', function() {
        // Hide calendar section
        document.getElementById('calendar-section')?.classList.add('hidden');
        document.getElementById('time-slots-section')?.classList.add('hidden');
        
        // Show services
        document.getElementById('services-container')?.classList.remove('hidden');
        document.getElementById('search-bar-section')?.classList.remove('hidden');
        document.getElementById('step-1-header')?.classList.remove('hidden');
        
        // Clear selections
        selectedServices = [];
        selectedDate = null;
        selectedDateTime = null;
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    
    // Change service from OTP (Step 4 -> Step 1)
    document.getElementById('change-service-from-otp')?.addEventListener('click', function() {
        // Hide OTP section
        document.getElementById('otp-section')?.classList.add('hidden');
        clearInterval(otpTimerInterval);
        
        // Show services
        document.getElementById('services-container')?.classList.remove('hidden');
        document.getElementById('search-bar-section')?.classList.remove('hidden');
        document.getElementById('step-1-header')?.classList.remove('hidden');
        
        // Clear selections
        selectedServices = [];
        selectedDate = null;
        selectedDateTime = null;
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    
    // Change time from OTP (Step 4 -> Step 2)
    document.getElementById('change-time-from-otp')?.addEventListener('click', function() {
        // Hide OTP section
        document.getElementById('otp-section')?.classList.add('hidden');
        clearInterval(otpTimerInterval);
        
        // Show calendar and time slots
        document.getElementById('calendar-section')?.classList.remove('hidden');
        document.getElementById('time-slots-section')?.classList.remove('hidden');
        
        // Scroll to time slots
        setTimeout(() => {
            const timeSlotsSection = document.getElementById('time-slots-section');
            if (timeSlotsSection) {
                timeSlotsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 100);
    });
    
    // Change info from OTP (Step 4 -> Step 3)
    document.getElementById('change-info-from-otp')?.addEventListener('click', function() {
        // Hide OTP section
        document.getElementById('otp-section')?.classList.add('hidden');
        clearInterval(otpTimerInterval);
        
        // Show customer info section
        document.getElementById('customer-info-section')?.classList.remove('hidden');
        
        // Scroll to customer info
        setTimeout(() => {
            const customerSection = document.getElementById('customer-info-section');
            if (customerSection) {
                customerSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 100);
    });

    // Delegated click listener for all change-* buttons so clicks are captured even
    // if the click target is a nested child or the DOM element is replaced later.
    document.addEventListener('click', function(e) {
        const clicked = e.target.closest('#change-service-from-form, #change-time-from-form, #change-service-from-otp, #change-time-from-otp, #change-info-from-otp, #change-service-from-calendar');
        if (!clicked) return;

        const id = clicked.id;
        switch (id) {
            case 'change-service-from-form':
                // Reuse existing helper for consistent behavior
                changeService();
                document.getElementById('customer-info-section')?.classList.add('hidden');
                // focus on services
                window.scrollTo({ top: 0, behavior: 'smooth' });
                break;

            case 'change-time-from-form':
                // Reuse existing helper
                document.getElementById('customer-info-section')?.classList.add('hidden');
                showTimeSelection();
                break;

            case 'change-service-from-otp':
                document.getElementById('otp-section')?.classList.add('hidden');
                clearInterval(otpTimerInterval);
                document.getElementById('services-container')?.classList.remove('hidden');
                document.getElementById('search-bar-section')?.classList.remove('hidden');
                document.getElementById('step-1-header')?.classList.remove('hidden');
                selectedServices = [];
                selectedDate = null;
                selectedDateTime = null;
                window.scrollTo({ top: 0, behavior: 'smooth' });
                break;

            case 'change-time-from-otp':
                document.getElementById('otp-section')?.classList.add('hidden');
                clearInterval(otpTimerInterval);
                document.getElementById('calendar-section')?.classList.remove('hidden');
                document.getElementById('time-slots-section')?.classList.remove('hidden');
                setTimeout(() => {
                    const timeSlotsSection = document.getElementById('time-slots-section');
                    if (timeSlotsSection) timeSlotsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);
                break;

            case 'change-info-from-otp':
                // Hide OTP, clear timer, show customer info
                document.getElementById('otp-section')?.classList.add('hidden');
                clearInterval(otpTimerInterval);
                const customerSection = document.getElementById('customer-info-section');
                if (customerSection) {
                    customerSection.classList.remove('hidden');
                    setTimeout(() => { customerSection.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 100);
                }
                break;
        }
    });
    
    console.log('Calendar initialization completed.');
});
</script>
</body>
</html>