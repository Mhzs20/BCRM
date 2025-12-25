<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رزرو آنلاین غیرفعال است | {{ $salon->name }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @font-face {
            font-family: 'Peyda';
            src: url('/fonts/Peyda-Medium.woff2') format('woff2');
        }
        .font-peyda { font-family: 'Peyda', 'IRANYekan', Tahoma, Verdana, sans-serif; }
        
        body {
            background: radial-gradient(circle at top right, #f8fafc, #f1f5f9);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.05);
        }

        .status-dot {
            width: 12px;
            height: 12px;
            background-color: #ef4444;
            border-radius: 50%;
            display: inline-block;
            margin-left: 8px;
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.5);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
    </style>
</head>
<body class="bg-gray-100 font-peyda antialiased text-right">

<main class="w-full max-w-md mx-auto bg-gray-100 rounded-3xl p-3 sm:p-4 min-h-screen">
    <!-- Header -->
    <div id="salon-header" class="flex flex-col items-center justify-center">
        <header class="relative w-full bg-white rounded-bl-3xl rounded-br-3xl border-b-2 border-teal-900 text-center py-6 sm:py-8">
            <x-header-background />
            
            <div class="relative z-10">
                <!-- Salon Image -->
                <div class="w-28 h-28 sm:w-32 sm:h-32 mx-auto bg-zinc-300 rounded-full border-2 border-zinc-900 overflow-hidden relative">
                    <img class="w-full h-full object-cover filter grayscale opacity-60"
                         src="{{ $salon->image ?? 'https://placehold.co/134x134' }}"
                         alt="{{ $salon->name }}"/>
                </div>
                
                <div class="mt-4">
                    <h1 class="text-neutral-700 text-lg sm:text-xl font-black">{{ $salon->name }}</h1>
                    <div class="inline-flex justify-center items-center gap-1.5 bg-red-500/10 rounded-[10px] px-4 py-1 mt-2 border border-red-500/20">
                        <span class="status-dot"></span>
                        <span class="text-red-600 text-sm font-bold font-iranyekan">رزرو آنلاین غیرفعال</span>
                    </div>
                </div>
            </div>
        </header>
    </div>

    <!-- Main Content -->
    <div class="mt-10 mb-10">
        <div class="glass-card rounded-[2.5rem] p-8 text-center relative overflow-hidden">
            <!-- Decorative Circle -->
            <div class="absolute -top-12 -right-12 w-32 h-32 bg-red-100 rounded-full opacity-30 blur-2xl"></div>
            
            <div class="relative z-10">
                <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-6 border border-red-100">
                    <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.732 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>

                <h2 class="text-neutral-800 text-xl font-black mb-4">با عرض پوزش</h2>
                <p class="text-neutral-600 text-base leading-7 mb-8">
                    در حال حاضر امکان رزرو نوبت آنلاین برای این مجموعه وجود ندارد.
                </p>
                
                @php
                    $phone = $salon->support_phone_number ?? $salon->mobile ?? $salon->phone;
                @endphp

                @if($phone)
                <a href="tel:{{ $phone }}" 
                   class="flex items-center justify-center gap-3 bg-neutral-900 text-white w-full py-4 rounded-2xl font-bold text-lg transition-transform active:scale-[0.98] shadow-lg shadow-black/10">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                    تماس با مجموعه
                </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Footer -->
    <x-app-footer />
</main>

</body>
</html>