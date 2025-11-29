@props(['version' => null])

<footer class="mt-8">
    <div class="bg-gray-50 py-5 flex justify-between items-center px-8 border-t border-gray-100">
        <div class="text-right">
            <div class="text-xl font-black text-neutral-800 tracking-widest">Zi<span class="text-orange-500">Box</span></div>
            <div class="text-[10px] text-neutral-400 font-light tracking-wider mt-0.5">CRM Application</div>
        </div>
        <div class="text-left">
            <div class="text-xl font-black text-neutral-800">زیـ<span class="text-orange-500">باکس</span></div>
            <div class="text-[10px] text-neutral-400 font-light mt-0.5">اپلیکیشن مدیریت مشتریان</div>
        </div>
        @if($version)
            <div class="ml-6 text-neutral-400 text-sm font-medium border rounded-lg py-2 px-4" dir="ltr">
                نسخه {{ $version }}
            </div>
        @endif
    </div>
</footer>
