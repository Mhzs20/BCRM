<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قابلیت غیرفعال</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100 font-peyda text-right">

<main class="w-full max-w-md mx-auto bg-gray-100 rounded-3xl p-4 min-h-screen flex items-center justify-center">
    <div class="text-center">
        <div class="w-32 h-32 mx-auto bg-gradient-to-br from-amber-100 to-amber-200 rounded-full border-4 border-white shadow-lg flex items-center justify-center mb-6 relative">
            <svg class="w-16 h-16 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            <div class="absolute -bottom-2 -right-2 bg-teal-900 text-white text-xs font-bold px-3 py-1 rounded-full border-2 border-white">
                PRO
            </div>
        </div>
        
        <h1 class="text-neutral-800 text-2xl font-black mb-3">ارتقا به نسخه پرو</h1>
        
        <div class="bg-white border border-gray-200 rounded-2xl p-6 mb-8 shadow-sm">
            <p class="text-gray-600 text-sm leading-7 mb-4">
                رزرو آنلاین برای این سالن فعال نمی‌باشد.
                <br>
                برای استفاده از قابلیت <span class="font-bold text-teal-900">لینک اختصاصی رزرو آنلاین</span> و مدیریت حرفه‌ای نوبت‌ها، لطفا پکیج خود را ارتقا دهید.
            </p>
            
            <div class="flex flex-col gap-3">
                <div class="flex items-center gap-2 text-xs text-gray-500 bg-gray-50 p-2 rounded-lg">
                    <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    لینک اختصاصی رزرو
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-500 bg-gray-50 p-2 rounded-lg">
                    <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    کاهش تماس‌های تلفنی
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-500 bg-gray-50 p-2 rounded-lg">
                    <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    مدیریت خودکار نوبت‌ها
                </div>
            </div>
        </div>
        
        <div class="flex flex-col gap-3">
            <p  class="w-full bg-teal-900 hover:bg-teal-800 text-white px-6 py-3.5 rounded-xl font-bold transition-colors shadow-lg shadow-teal-900/20">
           مدیر محترم سالن برای فعالسازی از طریق خرید پکیج پرو  در اپلیکیشن زیباکس اقدام نمایید
            </p>

            <button onclick="history.back()" class="text-gray-400 text-sm font-medium hover:text-gray-600 transition-colors">
                بازگشت
            </button>
        </div>
    </div>
</main>

</body>
</html>
