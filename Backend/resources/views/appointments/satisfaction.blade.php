<!DOCTYPE html>
<html lang="fa" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظرسنجی رضایت</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .rating .fa-star {
            color: #e4e5e9;
            cursor: pointer;
            font-size: 2.5rem; /* Increased star size */
            transition: color 0.2s;
        }
        .rating .fa-star.selected,
        .rating .fa-star:hover,
        .rating .fa-star:hover ~ .fa-star {
            color: #ffc107;
        
        }
        .rating:hover .fa-star.selected:hover ~ .fa-star {
            color: #e4e5e9;
        }
    </style>
</head>
<body class="bg-gray-100 font-peyda text-right">
@php
    $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
@endphp
<main class="w-full max-w-md mx-auto bg-gray-100 rounded-3xl p-4">
    <div class="flex flex-col items-center justify-center ">
        <header class="relative w-full  bg-emerald-50 rounded-bl-3xl rounded-br-3xl border-b-2 border-teal-900 text-center py-8">
            <div class="w-32 h-full z-10 mx-auto bg-zinc-300 rounded-full border-black overflow-hidden">
                <img class="w-32 h-full z-10 object-cover relative"
                     src="{{ $appointment->salon->image ?? 'https://placehold.co/134x134' }}"
                     alt="{{ $appointment->salon->name }}"/>
            </div>
            <div class="mt-4 z-10">
                <h1 class="text-neutral-700 text-xl font-black">{{ $appointment->salon->name }}</h1>
                <p class="text-teal-900 text-lg font-bold">{{ optional($appointment->customer)->name }} جان</p>
                <div class="inline-flex justify-center items-center gap-1.5">
                    <span class="text-teal-900 text-base font-bold">از خدمات ما راضی بودید؟</span>
                </div>
            </div>
        </header>
    </div>

    @if(isset($success_message))
        <div class="mt-5 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative text-center" role="alert">
            <span class="block sm:inline">{{ $success_message }}</span>
        </div>
    @else
        <section class="mt-5">
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <h3 class="text-neutral-700 text-lg font-bold mb-4">امتیاز شما به ما</h3>
                <div class="rating flex flex-row-reverse justify-center items-center" data-rating="0">
                    <i class="fas fa-star" data-value="5"></i>
                    <i class="fas fa-star" data-value="4"></i>
                    <i class="fas fa-star" data-value="3"></i>
                    <i class="fas fa-star" data-value="2"></i>
                    <i class="fas fa-star" data-value="1"></i>
                </div>
            </div>
        </section>

        <div class="my-5 h-px bg-zinc-300"></div>

        <form action="{{ route('satisfaction.store.hash', ['hash' => $appointment->hash]) }}" method="POST">
            @csrf
            <input type="hidden" name="rating" id="rating-input" value="0">
            <section class="mt-5">
                <div class="flex justify-end items-center">
                    <h3 class="text-neutral-700 text-sm mr-2 font-bold text-right">انتقاد یا پیشنهاد</h3>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="#353535" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 8V12" stroke="#353535" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 16H12.01" stroke="#353535" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="mt-4 bg-white rounded-lg shadow p-4">
                    <textarea dir="rtl" class="w-full text-neutral-700 bg-black/5 text-sm font-normal font-iranyekan leading-normal p-2 rounded-md" rows="5" placeholder="نظر خود را اینجا بنویسید..." name="text_feedback"></textarea>
                </div>
                <button type="submit" id="submit-feedback" class="w-full mt-4 bg-teal-900 text-white py-2 rounded-[10px]">ثبت نظر</button>
            </section>
        </form>
    @endif

    <footer class="mt-8 flex justify-between items-center">
        <div class="text-zinc-400 text-sm font-medium border  rounded-lg py-2 px-4 font-['Peyda']">
            نســخــه {{ str_replace($englishDigits, $persianDigits, '1.0.1') }}</div>
        <div class="flex flex-col justify-end">
<div class="w-28 h-12 relative">
    <div class="left-0 top-0 absolute justify-start"><span class="text-zinc-900 text-2xl font-black font-['Peyda']">زیـ </span><span class="text-orange-400 text-2xl font-black font-['Peyda']">بـــاکــس</span></div>
    <div class="left-[5px] top-[34px] absolute justify-start text-zinc-400 text-[10px] font-normal font-['Peyda']">اپلیکیشن مدیریت مشتریان</div>
</div>

        </div>
    </footer>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const stars = document.querySelectorAll('.rating .fa-star');
        const ratingContainer = document.querySelector('.rating');
        const ratingInput = document.getElementById('rating-input');
        let currentRating = 0;

        stars.forEach(star => {
            star.addEventListener('mouseover', function () {
                resetStars();
                const value = parseInt(this.getAttribute('data-value'));
                for (let i = 0; i < value; i++) {
                    stars[stars.length - 1 - i].classList.add('selected');
                }
            });

            star.addEventListener('mouseout', function () {
                resetStars();
                if (currentRating > 0) {
                    for (let i = 0; i < currentRating; i++) {
                        stars[stars.length - 1 - i].classList.add('selected');
                    }
                }
            });

            star.addEventListener('click', function () {
                currentRating = parseInt(this.getAttribute('data-value'));
                ratingContainer.setAttribute('data-rating', currentRating);
                ratingInput.value = currentRating; // Update hidden input
            });
        });

        function resetStars() {
            stars.forEach(s => s.classList.remove('selected'));
        }
    });
</script>
</body>
</html>
