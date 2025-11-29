<!DOCTYPE html>
<html lang="fa" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سالن یافت نشد</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100 font-peyda text-right">

<main class="w-full max-w-md mx-auto bg-gray-100 rounded-3xl p-4 min-h-screen flex items-center justify-center">
    <div class="text-center">
        <div class="w-32 h-32 mx-auto bg-zinc-300 rounded-full border-2 border-zinc-900 flex items-center justify-center mb-6">
            <svg class="w-16 h-16 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
        </div>
        
        <h1 class="text-neutral-700 text-xl font-black mb-2">سالن یافت نشد</h1>
        
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center mb-2">
                <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.732 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <p class="text-yellow-700 text-sm font-medium">سالن مورد نظر یافت نشد</p>
            <p class="text-yellow-600 text-xs mt-1">لطفاً آدرس را بررسی کنید یا با پشتیبانی تماس بگیرید</p>
        </div>
        
        <button onclick="history.back()" 
                class="inline-flex items-center gap-2 bg-teal-900 text-white px-6 py-3 rounded-lg font-bold">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            بازگشت
        </button>
    </div>
</main>

</body>
</html>