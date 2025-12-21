@php
    $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $date = verta($appointment->start_time);
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظرسنجی رضایت</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .font-peyda { font-family: 'Peyda', sans-serif; }
        .font-iranyekan { font-family: 'IRANYekanMobileFN', sans-serif; }
        
        /* Star/Square Rating Styles */
        .rating-item {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .rating-item.active .outline-box {
            outline-color: #fb923c !important; /* orange-400 */
            background-color: rgba(251, 146, 60, 0.1);
        }
        
        /* Tag Selection Styles */
        .tag-item {
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            background-color: transparent;
        }
        /* Strength Theme */
        #strength-tags .tag-item { 
            border-color: #18181b !important; 
        }
        #strength-tags .tag-item .tag-text {
            color: #18181b !important;
        }
        #strength-tags .tag-item.selected { 
            background-color: rgba(24, 24, 27, 0.1) !important; 
        }

        /* Weakness Theme */
        #weakness-tags .tag-item { 
            border-color: #f43f5e !important; 
        }
        #weakness-tags .tag-item .tag-text { 
            color: #f43f5e !important; 
        }
        #weakness-tags .tag-item.selected { 
            background-color: rgba(244, 63, 94, 0.1) !important; 
        }
    </style>
</head>
<body class="bg-gray-100 font-peyda min-h-screen">

<main class="w-full max-w-[420px] mx-auto bg-gray-100 rounded-3xl p-3 sm:p-4 min-h-screen text-center">

    <!-- Header -->
    <!-- Header Area -->
    @php
        $feedback = $appointment->feedback;
        $strengths = "";
        $weaknesses = "";
        if ($feedback) {
            if ($feedback->strengths_selected && is_array($feedback->strengths_selected)) {
                $strengths = implode('، ', $feedback->strengths_selected);
            }
            if ($feedback->weaknesses_selected && is_array($feedback->weaknesses_selected)) {
                $weaknesses = implode('، ', $feedback->weaknesses_selected);
            }

            // Fallback to parsing text_feedback if structured data is missing
            if (!$strengths && !$weaknesses && $feedback->text_feedback) {
                $parts = explode("\n", $feedback->text_feedback);
                foreach ($parts as $part) {
                    if (str_contains($part, "نقاط قوت:")) {
                        $strengths = trim(str_replace("نقاط قوت:", "", $part));
                    }
                    if (str_contains($part, "نقاط ضعف:")) {
                        $weaknesses = trim(str_replace("نقاط ضعف:", "", $part));
                    }
                }
            }
        }
    @endphp

    <header class="relative w-full bg-white rounded-bl-3xl rounded-br-3xl border-b-2 border-teal-900 text-center py-6 sm:py-8 mb-6 overflow-hidden">
        <x-header-background />

        <div class="relative z-10 flex flex-col items-center">
            @if(isset($success_message) || $appointment->feedback)
                <!-- Tick for Success -->
                <div class="w-32 h-32 sm:w-36 sm:h-36 relative mb-4 flex items-center justify-center">
                    <svg width="141" height="141" viewBox="0 0 141 141" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M117.5 35.25L52.875 99.875L23.5 70.5" stroke="#1C1C1E" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h2 class="text-neutral-700 text-xl font-black font-peyda mb-2">بازخورد خدمات</h2>
                <div class="text-center px-6">
                    <p class="text-zinc-900 text-base font-bold font-peyda leading-6">
                        {{ optional($appointment->customer)->name }} جان <br/>
                        بازخورد شما با موفقیت ثبت گردید !
                    </p>
                </div>
            @else
                <!-- Salon Image for Survey -->
                <div class="w-28 h-28 sm:w-32 sm:h-32 mx-auto bg-zinc-300 rounded-full border-2 border-zinc-900 overflow-hidden relative shadow-lg mb-4">
                    <img class="w-full h-full object-cover"
                         src="{{ $appointment->salon->image ?? asset('assets/img/default-salon.png') }}"
                         alt="{{ $appointment->salon->name }}"/>
                    <div class="absolute bottom-2 right-2 w-4 h-4 bg-orange-400 rounded-full border-2 border-white"></div>
                </div>
                <h2 class="text-neutral-700 text-xl font-black font-peyda mb-2">بازخورد خدمات</h2>
                <div class="text-center mt-2 px-4">
                    <p class="text-zinc-900 text-base font-bold font-peyda leading-6">
                        {{ optional($appointment->customer)->name }} جان <br/>
                        <span class="text-orange-400">{{ $appointment->services->first()->name ?? 'خدمات' }}</span>
                        <span> در روز </span>
                        <span>{{ $date->format('%A') }}</span>
                        <span class="font-iranyekan">{{ $date->format('d') }}</span>
                        <span> {{ $date->format('%B') }} </span>
                        <span class="font-iranyekan">{{ $date->format('Y') }}</span>
                        <span> چطور بود ؟</span>
                    </p>
                </div>
            @endif
        </div>
    </header>

    @if(isset($success_message) || $appointment->feedback)
        <div class="flex flex-col items-center gap-5 w-full px-1">
            <!-- Stars Card -->
            <div class="w-full h-14 bg-white rounded-lg shadow-[0px_3px_15px_0px_rgba(65,105,225,0.08)] flex justify-center items-center">
                <x-star-rating :rating="$feedback->rating ?? 0" />
            </div>

            <!-- Feedback Tags Summary -->
            @if($strengths || $weaknesses)
            <div class="w-full min-h-[120px] py-6 bg-white rounded-lg shadow-[0px_3px_15px_0px_rgba(65,105,225,0.08)] flex flex-col justify-center gap-4 px-4 mb-4">
                @if($strengths)
                <div class="flex flex-col items-center gap-1">
                    <div class="text-zinc-400 text-[10px] font-normal font-iranyekan">از کدام موارد رضایت داشتید ؟</div>
                    <div class="text-zinc-900 text-sm font-bold font-iranyekan leading-relaxed text-center">{{ $strengths }}</div>
                </div>
                @endif
                
                @if($weaknesses)
                <div class="flex flex-col items-center gap-1">
                    <div class="text-zinc-400 text-[10px] font-normal font-iranyekan">از کدام موارد رضایت نداشتید ؟</div>
                    <div class="text-rose-500 text-sm font-bold font-iranyekan leading-relaxed text-center">{{ $weaknesses }}</div>
                </div>
                @endif
            </div>
            @endif

            <!-- Salon Contact Component -->
            <x-salon-contact :salon="$appointment->salon" />
        </div>
    @else
        <!-- Rating & Form Section -->
        <form action="{{ route('satisfaction.store.hash', ['hash' => $appointment->hash]) }}" method="POST" id="feedback-form">
            @csrf
            <input type="hidden" name="rating" id="rating-input" value="0">
            <input type="hidden" name="text_feedback" id="text-feedback" value="">
            <input type="hidden" name="strengths_selected" id="strengths-selected" value="[]">
            <input type="hidden" name="weaknesses_selected" id="weaknesses-selected" value="[]">
            
            <!-- Rating Card -->
            <div class="flex flex-col items-center gap-4 mb-6">
                <div class="inline-flex justify-center items-center gap-2">
                    <div class="w-6 h-6 relative flex items-center justify-center">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="#353535" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 8V12" stroke="#353535" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 16H12.01" stroke="#353535" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <span class="text-neutral-700 text-sm font-bold font-peyda">به سالن {{ $appointment->salon->name }} امتیاز دهید :</span>
                </div>

                <div class="w-full h-16 bg-white rounded-xl shadow-[0px_3px_15px_0px_rgba(65,105,225,0.08)] flex justify-center items-center">
                    <x-star-rating :interactive="true" />
                </div>
            </div>

            <div class="w-full h-px bg-zinc-300 mb-6"></div>

            <!-- Tags Section -->
            <div class="flex flex-col items-center w-full gap-4">
                <!-- Tabs -->
                <div class="w-full h-14 relative bg-white rounded-xl shadow-[0px_3px_15px_0px_rgba(65,105,225,0.08)] overflow-hidden flex items-center justify-between px-2">
                    <div class="flex-1 text-center py-2.5 cursor-pointer text-neutral-700 text-base font-bold font-peyda rounded-md transition-all duration-200" id="tab-strength">نقاط قوت</div>
                    <div class="w-px h-5 bg-zinc-300 mx-1"></div>
                    <div class="flex-1 text-center py-2.5 cursor-pointer text-rose-500 text-base font-bold font-peyda bg-rose-500/10 rounded-md transition-all duration-200" id="tab-weakness">نقاط ضعف</div>
                </div>

                <!-- Tags Container -->
                <div class="w-full bg-white rounded-xl shadow-[0px_3px_15px_0px_rgba(65,105,225,0.08)] p-4 relative min-h-[280px]">
                     <div class="text-center mb-4">
                         <span class="text-zinc-400 text-sm font-normal font-iranyekan" id="question-text">از کدام موارد رضایت نداشتید ؟</span>
                     </div>

                     <div id="strength-tags" class="hidden grid grid-cols-2 gap-3 mb-4 transition-opacity duration-300">
                        @foreach(['پرسنل با دقت و با حوصله', 'مواد مصرفی با کیفیت', 'نوبت دهی آسان', 'قیمت منصفانه'] as $tag)
                             <div class="tag-item flex items-center justify-center p-2 h-10 rounded-md shadow-[0px_3px_15px_0px_rgba(65,105,225,0.08)]" data-tag="{{ $tag }}">
                                  <span class="tag-text text-zinc-900 text-xs sm:text-xs font-bold font-iranyekan">{{ $tag }}</span>
                             </div>
                        @endforeach
                     </div>

                     <div id="weakness-tags" class="grid grid-cols-2 gap-3 mb-4 transition-opacity duration-300 w-full">
                        @foreach(['محیط شلوغ و بی نظم', 'کیفیت پایین خدمات', 'رفتار نامناسب پرسنل', 'عدم راهنمایی مناسب'] as $tag)
                             <div class="tag-item flex items-center justify-center p-2 h-10 rounded-md shadow-[0px_3px_15px_0px_rgba(65,105,225,0.08)]" data-tag="{{ $tag }}">
                                  <span class="tag-text text-rose-500 text-xs sm:text-xs font-bold font-iranyekan">{{ $tag }}</span>
                             </div>
                        @endforeach
                     </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="w-full mt-6 flex flex-col items-center gap-4">
                <button type="submit" class="w-full h-12 bg-gradient-to-b from-zinc-900 via-neutral-700 to-zinc-900 rounded-xl flex items-center justify-center shadow-lg active:scale-95 transition-transform">
                    <span class="text-white text-lg font-bold font-peyda">ثبت بازخورد</span>
                </button>
                <button type="button" id="clear-form" class="text-zinc-900 text-base font-normal font-peyda">پاک کردن</button>
            </div>
        </form>
    @endif

    <!-- Footer -->
    <x-app-footer />

</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ratingItems = document.querySelectorAll('.rating-item');
        const ratingInput = document.getElementById('rating-input');
        const tagItems = document.querySelectorAll('.tag-item');
        const textFeedbackInput = document.getElementById('text-feedback');
        const strengthsSelectedInput = document.getElementById('strengths-selected');
        const weaknessesSelectedInput = document.getElementById('weaknesses-selected');
        const weaknessTextarea = document.getElementById('weakness-textarea');
        const tabWeakness = document.getElementById('tab-weakness');
        const tabStrength = document.getElementById('tab-strength');
        const strengthTags = document.getElementById('strength-tags');
        const weaknessTags = document.getElementById('weakness-tags');
        const questionText = document.getElementById('question-text');
        const form = document.getElementById('feedback-form');
        const clearBtn = document.getElementById('clear-form');

        let selectedRating = 0;
        let selectedStrengths = new Set();
        let selectedWeaknesses = new Set();
        let activeTab = 'weakness'; // strength or weakness

        // Rating Logic
        ratingItems.forEach(item => {
            item.addEventListener('click', function() {
                const val = parseInt(this.getAttribute('data-value'));
                selectedRating = val;
                ratingInput.value = val;
                updateRatingUI(val);
            });
        });

        // Initial UI reset
        function updateRatingUI(val) {
             ratingItems.forEach(ri => {
                   const v = parseInt(ri.getAttribute('data-value'));
                   const path = ri.querySelector('path');
                   if(v <= val) {
                       path.setAttribute('stroke', '#fb923c');
                       path.setAttribute('fill', '#fb923c');
                   } else {
                       path.setAttribute('stroke', '#fb923c');
                       path.setAttribute('fill', 'none');
                   }
                });
        }

        // Tag Logic
        tagItems.forEach(tag => {
            tag.addEventListener('click', function() {
                const text = this.getAttribute('data-tag');
                const isStrength = this.closest('#strength-tags') !== null;
                const set = isStrength ? selectedStrengths : selectedWeaknesses;

                if(set.has(text)) {
                    set.delete(text);
                    this.classList.remove('selected');
                } else {
                    set.add(text);
                    this.classList.add('selected');
                }
                updateFeedbackInput();
            });
        });

        function updateFeedbackInput() {
            let feedback = "";
            if(selectedStrengths.size > 0) {
                feedback += "نقاط قوت: " + Array.from(selectedStrengths).join('، ') + "\n";
            }
            if(selectedWeaknesses.size > 0) {
                feedback += "نقاط ضعف: " + Array.from(selectedWeaknesses).join('، ');
            }
            textFeedbackInput.value = feedback;
            strengthsSelectedInput.value = JSON.stringify(Array.from(selectedStrengths));
            weaknessesSelectedInput.value = JSON.stringify(Array.from(selectedWeaknesses));
        }

        // Tabs Logic
        const setActiveTab = (tab) => {
            activeTab = tab;
            if(tab === 'strength') {
                tabStrength.classList.add('bg-zinc-900/10', 'text-zinc-900');
                tabStrength.classList.remove('text-neutral-700', 'bg-rose-500/10', 'text-rose-500');
                
                tabWeakness.classList.remove('bg-zinc-900/10', 'text-zinc-900', 'bg-rose-500/10', 'text-rose-500');
                tabWeakness.classList.add('text-neutral-700');

                strengthTags.classList.remove('hidden');
                weaknessTags.classList.add('hidden');
                questionText.innerText = "از کدام موارد رضایت داشتید ؟";
            } else {
                tabWeakness.classList.add('bg-rose-500/10', 'text-rose-500');
                tabWeakness.classList.remove('text-neutral-700', 'bg-zinc-900/10', 'text-zinc-900');
                
                tabStrength.classList.remove('bg-zinc-900/10', 'text-zinc-900', 'bg-rose-500/10', 'text-rose-500');
                tabStrength.classList.add('text-neutral-700');

                strengthTags.classList.add('hidden');
                weaknessTags.classList.remove('hidden');
                questionText.innerText = "از کدام موارد رضایت نداشتید ؟";
            }
        };

        tabStrength.addEventListener('click', () => setActiveTab('strength'));
        tabWeakness.addEventListener('click', () => setActiveTab('weakness'));

        // Clear Form
        if(clearBtn) {
            clearBtn.addEventListener('click', function() {
                selectedRating = 0;
                ratingInput.value = 0;
                selectedStrengths.clear();
                selectedWeaknesses.clear();
                tagItems.forEach(t => t.classList.remove('selected'));
                updateFeedbackInput();
                updateRatingUI(0);
            });
        }
    });
</script>
</body>
</html>
