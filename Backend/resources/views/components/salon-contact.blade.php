@php
    $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
@endphp

<section class="mt-1 flex flex-col items-center gap-6 w-full">
    <!-- Contact Title -->
    <div class="w-full flex justify-start items-center gap-1.5 px-2" dir="rtl">
        <div class="w-6 h-6 relative overflow-hidden">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M10 13C10.4295 13.5741 10.9774 14.0491 11.6066 14.3929C12.2357 14.7367 12.9315 14.9411 13.6467 14.9923C14.3618 15.0435 15.0796 14.9403 15.7513 14.6897C16.4231 14.4392 17.0331 14.047 17.54 13.54L20.54 10.54C21.4508 9.59695 21.9548 8.33394 21.9434 7.02296C21.932 5.71198 21.4061 4.45791 20.4791 3.53087C19.5521 2.60383 18.298 2.07799 16.987 2.0666C15.676 2.0552 14.413 2.55918 13.47 3.46997L11.75 5.17997" stroke="#353535" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M13.9982 10.9992C13.5688 10.4251 13.0209 9.95007 12.3917 9.60631C11.7625 9.26255 11.0667 9.05813 10.3516 9.00691C9.63645 8.9557 8.91866 9.05888 8.2469 9.30947C7.57514 9.56005 6.96513 9.95218 6.45825 10.4592L3.45825 13.4592C2.54746 14.4023 2.04348 15.6653 2.05488 16.9763C2.06627 18.2872 2.59211 19.5413 3.51915 20.4683C4.44619 21.3954 5.70026 21.9212 7.01124 21.9326C8.32222 21.944 9.58524 21.44 10.5282 20.5292L12.2382 18.8192" stroke="#353535" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="text-neutral-700 text-sm font-bold font-peyda">ارتباط با سالن {{ $salon->name }}</div>
    </div>

    <!-- Contact Grid -->
    <div class="w-full flex flex-col gap-4">
        <div class="grid grid-cols-3 gap-3">
            @php
                $socials = [
                    ['key' => 'whatsapp', 'label' => 'واتس اپ', 'icon' => 'whatsapp.svg', 'url' => 'https://wa.me/+98' . ltrim($salon->whatsapp, '0'), 'val' => $salon->whatsapp],
                    ['key' => 'telegram', 'label' => 'تلگرام', 'icon' => 'telegram.svg', 'url' => 'https://t.me/' . $salon->telegram, 'val' => $salon->telegram],
                    ['key' => 'instagram', 'label' => 'اینستاگرام', 'icon' => 'instagram.svg', 'url' => 'https://instagram.com/' . $salon->instagram, 'val' => $salon->instagram],
                    ];
            @endphp

            @foreach($socials as $social)
                @if($social['val'])
                <a href="{{ $social['url'] }}" class="relative bg-white rounded-xl shadow-[0px_3px_15px_0px_rgba(65,105,225,0.08)] p-4 flex flex-col items-center justify-center gap-2 overflow-hidden h-36 border border-transparent hover:border-zinc-200 transition-all">
                    <!-- Rotating Decorative Border Container -->
                    <div class="w-14 h-14 relative flex items-center justify-center">
                        <div class="w-10 h-10 relative bg-zinc-900/5 rounded-full flex items-center justify-center">
                            <!-- Spinning Border SVG -->
                            <div class="absolute inset-[-4px] w-[calc(100%+8px)] h-[calc(100%+8px)] animate-spin-slow">
                                <img src="{{ asset('assets/img/border.svg') }}" class="w-full h-full object-contain" alt="border"/>
                            </div>
                            
                            <!-- SVG Icon -->
                            <div class="w-6 h-6 flex items-center justify-center overflow-hidden z-10">
                                <img src="{{ asset('assets/img/' . $social['icon']) }}" alt="{{ $social['label'] }}" class="w-full h-full object-contain"/>
                            </div>
                        </div>
                    </div>
                    <div class="text-neutral-700 text-sm font-bold font-peyda mt-1">{{ $social['label'] }}</div>
                    <div class="text-neutral-400 text-[9px] font-light font-peyda truncate w-full text-center" dir="ltr">
                        {{ $social['key'] === 'whatsapp' ? str_replace($englishDigits, $persianDigits, $social['val']) : $social['val'] }}
                    </div>
                </a>
                @endif
            @endforeach
        </div>

        <!-- Phone Support -->
        <a href="tel:{{ $salon->support_phone_number }}" class="w-full h-20 bg-white rounded-xl shadow-[0px_3px_15px_0px_rgba(65,105,225,0.08)] flex items-center justify-center gap-4 px-6 border border-transparent hover:border-zinc-200 transition-all overflow-hidden relative">
            <div class="w-14 h-14 relative flex items-center justify-center">
                <div class="w-10 h-10 relative bg-zinc-900/5 rounded-full flex items-center justify-center">
                    <!-- Spinning Border SVG -->
                    <div class="absolute inset-[-4px] w-[calc(100%+8px)] h-[calc(100%+8px)] animate-spin-slow">
                        <img src="{{ asset('assets/img/border.svg') }}" class="w-full h-full object-contain" alt="border"/>
                    </div>
                    
                    <!-- SVG Icon -->
                    <div class="w-6 h-6 flex items-center justify-center overflow-hidden z-10">
                        <img src="{{ asset('assets/img/phone.svg') }}" alt="phone" class="w-full h-full object-contain"/>
                    </div>
                </div>
            </div>
            <div class="text-neutral-700 text-xl font-bold font-peyda tracking-[.25em]" dir="ltr">
                {{ str_replace($englishDigits, $persianDigits, $salon->support_phone_number) }}
            </div>
        </a>
    </div>
</section>

<style>
    @keyframes spin-slow {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .animate-spin-slow {
        animation: spin-slow 12s linear infinite;
    }
</style>
