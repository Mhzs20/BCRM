@props(['rating' => 0, 'maxStars' => 5, 'interactive' => false])

<div class="flex justify-center items-center gap-4 sm:gap-6 {{ $interactive ? 'rating-container' : '' }}" dir="ltr">
    @for($i = 1; $i <= $maxStars; $i++)
        @php
            $isFilled = $i <= $rating;
            $size = match($i) {
                1 => 20,
                2 => 24,
                3 => 28,
                4 => 36,
                5 => 40,
                default => 24
            };
        @endphp
        <div class="{{ $interactive ? 'rating-item cursor-pointer active:scale-95 transition-transform' : '' }} w-9 h-9 relative flex items-center justify-center" 
             @if($interactive) data-value="{{ $i }}" @endif>
            <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M19.9948 3.33398L25.1448 13.7673L36.6615 15.4507L28.3281 23.5673L30.2948 35.034L19.9948 29.6173L9.69479 35.034L11.6615 23.5673L3.32812 15.4507L14.8448 13.7673L19.9948 3.33398Z" 
                      stroke="#fb923c" 
                      fill="{{ $isFilled ? '#fb923c' : 'none' }}"
                      stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                      class="transition-all duration-200"/>
            </svg>
        </div>
    @endfor
</div>
