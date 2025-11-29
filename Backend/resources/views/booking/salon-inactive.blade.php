<!DOCTYPE html>
<html lang="fa" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سالن غیرفعال</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100 font-peyda text-right">

<main class="w-full max-w-md mx-auto bg-gray-100 rounded-3xl p-4 min-h-screen flex items-center justify-center">
    <div class="text-center">
        <div class="w-32 h-32 mx-auto bg-zinc-300 rounded-full border-2 border-zinc-900 overflow-hidden mb-6">
            <img class="w-full h-full object-cover opacity-50"
                 src="{{ $salon->image ?? 'https://placehold.co/134x134' }}"
                 alt="{{ $salon->name }}"/>
        </div>
        
        <h1 class="text-neutral-700 text-xl font-black mb-2">{{ $salon->name }}</h1>
        
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center mb-2">
                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.732 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <p class="text-red-700 text-sm font-medium">متأسفانه این سالن در حال حاضر فعال نیست</p>
            <p class="text-red-600 text-xs mt-1">لطفاً بعداً مراجعه فرمایید یا با سالن تماس بگیرید</p>
        </div>
        
        @if($salon->support_phone_number)
        <a href="tel:{{ $salon->support_phone_number }}" 
           class="inline-flex items-center gap-2 bg-teal-900 text-white px-6 py-3 rounded-lg font-bold">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
            </svg>
            تماس با سالن
        </a>
        @endif
    </div>
</main>

</body>
</html>