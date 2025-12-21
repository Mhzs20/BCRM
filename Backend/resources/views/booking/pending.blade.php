<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نوبت شما با موفقیت ثبت شد</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .font-peyda {
            font-family: 'PeydaWeb', 'Peyda', 'IRANYekan', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dir-ltr {
            direction: ltr;
        }
    </style>
</head>
<body class="bg-gray-100 font-peyda antialiased">

    <div class="min-h-screen flex flex-col items-center justify-center p-4">
        
        <!-- Main Card -->
        <div class="w-full max-w-md bg-white rounded-[2.5rem] shadow-xl overflow-hidden relative">
            
            <!-- Top Section with Gradient and Salon Info -->
            <div class="relative bg-gradient-to-b from-orange-400/30 to-gray-100/70 pt-10 pb-6 px-6 text-center rounded-b-[3rem]">
                

                <!-- Salon Image -->
                 <div class="flex relative flex-col items-center justify-center">
                <div class=" w-32 h-32 mx-auto mb-4">
                    <div class="w-32 h-32 rounded-full overflow-hidden border-[6px] border-white shadow-lg relative z-10">
                        @if(isset($salon_image) && $salon_image)
                            <img src="{{ $salon_image }}" alt="{{ $salon_name }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-400">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                        @endif
                    </div>
                    <!-- Decorative Elements behind image -->
                    <div class="absolute top-0 left-0 w-full h-full ">
                    </div>
                </div>
                <!-- Decorative Elements behind image (same as success) -->
                <div class="absolute top-0 left-0 w-full h-full">
                    <img src="{{ asset('assets/img/back.png') }}" alt="back" class="w-full drop-shadow-xl">
                </div>
                <!-- Salon Name -->
                <h1 class="text-xl font-black text-neutral-800 mb-3">{{ $salon_name ?? 'سالن زیبایی' }}</h1>
                
                <!-- Badge -->
                <div class="inline-block bg-orange-100/50 px-4 py-1.5 rounded-2xl border border-orange-100 mb-6">
                    <span class="text-orange-500 font-bold text-sm">رزرو نوبت آنلاین</span>
                </div>

                <!-- Calendar Illustration Image -->
                <div class="relative w-full flex items-center justify-center mb-6">
                    <img src="/assets/img/pending.png" alt="Pending Illustration" class="w-48 h-auto object-contain drop-shadow-xl">
                </div>

                <!-- Success Message Title -->
                <h2 class="text-2xl font-black text-neutral-800 mb-4">
                    نوبت شما <span class="text-orange-500">ثبت شد</span> و در انتظار تایید است!
                </h2>
                
                <div class="space-y-2 text-sm leading-8 text-neutral-500 font-medium px-2">
                    <p>
                        نوبت <span class="text-orange-500 font-bold text-lg mx-1">{{ $service ?? 'خدمات' }}</span>
                        در تاریخ <span class="text-orange-400 font-bold text-lg mx-1 dir-ltr inline-block">{{ $date ?? '-' }}</span>
                        با اپراتور <span class="text-orange-400 font-bold text-lg mx-1">{{ $operator ?? '-' }}</span>
                        ثبت گردید و در انتظار تائید سالن می باشد.
                    </p>
                    <p>
                        پس از تائید نوبت، پیامک رزرو را دریافت خواهید کرد.
                    </p>
                </div>
            </div>

            <!-- Footer Info -->
            <div class="text-center px-6 mb-8">
                <div class="text-neutral-400 font-normal text-sm">
                    کد پیگیری نوبت : <span class="font-bold text-lg text-neutral-600 mx-1">{{ $tracking_code ?? '-' }}</span>
                </div>
            </div>

            <!-- Actions -->
            <div class="px-6 pb-8 space-y-4">
                <a href="{{ isset($salon_id) ? route('booking.show', ['salonId' => $salon_id]) : '#' }}" class="block w-full relative group overflow-hidden rounded-xl shadow-lg shadow-gray-400/20">
                    <div class="absolute inset-0 bg-gradient-to-b from-gray-800 to-black group-hover:scale-105 transition-transform duration-300"></div>
                    <div class="relative py-3.5 text-center">
                        <span class="text-white text-lg font-bold">ثبت یک نوبت دیگر</span>
                    </div>
                </a>
                
                @if(isset($salon_phone) && $salon_phone)
                <a href="tel:{{ $salon_phone }}" class="block w-full text-neutral-800 text-center py-2 font-bold text-lg hover:text-orange-500 transition-colors">
                    تماس با {{ $salon_name ?? 'سالن' }}
                </a>
                @endif
            </div>

            <!-- Branding -->
            <x-app-footer />

        </div>
    </div>

</body>
</html>
