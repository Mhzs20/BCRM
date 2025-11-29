@props(['id', 'buttonId', 'title', 'icon' => 'service'])

<div dir="rtl" class="w-full flex justify-between items-stretch gap-3">
    <div class="flex-1 bg-white rounded-lg shadow-[0px_3px_15px_0px_rgba(65,105,225,0.08)] flex items-center justify-start py-2 px-3 min-h-[40px]">
        <div class="flex items-center justify-start gap-2 w-full overflow-hidden">
            <div class="w-6 h-6 flex-none relative overflow-hidden">
                @if($icon === 'service')
                    <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19.5807 2.75H13.1641V9.16667H19.5807V2.75Z" stroke="#353535" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M9.16667 2.75H2.75V9.16667H9.16667V2.75Z" stroke="#353535" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M19.5807 13.167H13.1641V19.5837H19.5807V13.167Z" stroke="#353535" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M9.16667 13.167H2.75V19.5837H9.16667V13.167Z" stroke="#353535" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                @elseif($icon === 'clock')
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="9" stroke="#353535" stroke-width="2"/>
                        <path d="M12 7V12L15 15" stroke="#353535" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                @elseif($icon === 'user')
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 21C20 19.6044 20 18.9067 19.8278 18.3389C19.44 17.0605 18.4395 16.06 17.1611 15.6722C16.5933 15.5 15.8956 15.5 14.5 15.5H9.5C8.10444 15.5 7.40665 15.5 6.83886 15.6722C5.56045 16.06 4.56004 17.0605 4.17224 18.3389C4 18.9067 4 19.6044 4 21" stroke="#353535" stroke-width="1.5" stroke-linecap="round"/>
                        <circle cx="12" cy="9" r="4" stroke="#353535" stroke-width="1.5"/>
                    </svg>
                @endif
            </div>
            <div class="flex items-center justify-start gap-1 overflow-hidden">
                <span class="text-neutral-700 text-base font-bold font-['IRANYekanMobileFN'] whitespace-nowrap">{{ $title }} : </span>
                <span id="{{ $id }}" class="text-neutral-400 text-base font-normal font-['IRANYekanMobileFN'] text-right truncate whitespace-nowrap" dir="ltr">...</span>
            </div>
        </div>
    </div>
    <div class="w-16 flex-none relative bg-orange-400/5 rounded-lg shadow-[0px_3px_15px_0px_rgba(65,105,225,0.08)] outline outline-1 outline-offset-[-1px] outline-orange-400 cursor-pointer hover:bg-orange-400/10 transition-colors flex items-center justify-center min-h-[40px]">
        <div id="{{ $buttonId }}" class="text-orange-400 text-base font-bold font-iranyekan">تغییر</div>
    </div>
</div>
