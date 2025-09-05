@extends('admin.layouts.app')

@section('title', 'مدیریت قالب‌های پیامک')

@section('header')
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">مدیریت قالب‌های پیامک (سراسری)</h2>
        <a href="#custom-section" class="text-sm text-indigo-600 hover:text-indigo-800">پرش به قالب‌های سفارشی ↓</a>
    </div>
@endsection

@php
    $eventDescriptions = [
        'appointment_confirmation' => 'ارسال پس از ثبت نوبت',
        'appointment_reminder' => 'یادآوری قبل از نوبت',
        'appointment_cancellation' => 'اعلام لغو نوبت',
        'appointment_modification' => 'تغییر زمان یا جزئیات نوبت',
        'birthday_greeting' => 'تبریک تولد مشتری',
        'service_specific_notes' => 'ارسال نکات اختصاصی خدمت',
    ];
    $placeholders = [
        'customer_name','salon_name','appointment_date','appointment_time','details_url','survey_url','service_name','service_specific_notes','reminder_time_text','timestamp','staff_name','services_list','appointment_cost'
    ];
@endphp

@section('content')
<div class="py-8 space-y-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white border border-indigo-100 shadow-sm rounded-xl p-5 md:p-6 relative overflow-hidden">
            <div class="absolute inset-0 pointer-events-none opacity-[0.04] bg-[radial-gradient(circle_at_30%_30%,#6366f1,transparent_55%)]"></div>
            <div class="relative space-y-4">
                <h3 class="font-semibold text-indigo-700 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l3.5 3.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    راهنمای استفاده از قالب‌ها
                </h3>
                <p class="text-sm leading-6 text-gray-600">از placeholder ها برای تزریق مقادیر پویا استفاده کنید. لینک‌ها را مستقیماً نگذارید؛ فقط <code class="bg-indigo-50 text-indigo-700 px-1 rounded text-xs">{details_url}</code> یا <code class="bg-indigo-50 text-indigo-700 px-1 rounded text-xs">{survey_url}</code> را قرار دهید. اگر placeholder لینک وجود نداشته باشد، لینکی ارسال نمی‌شود.</p>
                <div class="flex flex-wrap gap-1 text-xs">
                    @foreach($placeholders as $ph)
                        <span class="px-2 py-1 bg-gray-100 rounded border border-gray-200 font-mono">{ {{ $ph }} }</span>
                    @endforeach
                </div>
                <p class="text-xs text-gray-500">الگوی دوگانه <code class="bg-gray-100 px-1 rounded text-[10px] font-mono">@{{name}}</code> یا <code class="bg-gray-100 px-1 rounded text-[10px] font-mono">{name}</code> هر دو پشتیبانی می‌شود. طول پیام مستقیماً روی تعداد پارت و هزینه اثر دارد.</p>
            </div>
        </div>
    </div>

    <!-- System Templates -->
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow-sm rounded-xl p-5 md:p-7 space-y-6 border border-gray-100">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    قالب‌های سیستمی (رویدادی)
                </h3>
                <button form="system-templates-form" class="px-4 py-2 bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700 text-sm">ذخیره همه رویدادها</button>
            </div>
            <form id="system-templates-form" method="POST" action="{{ route('admin.sms-templates.system-update') }}" class="space-y-6">
                @csrf
                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($systemTemplates as $tpl)
                        <div class="group relative rounded-lg border border-gray-200 bg-gradient-to-br from-white to-gray-50 hover:shadow-md transition flex flex-col">
                            <div class="absolute top-2 left-2 text-[10px] font-mono bg-indigo-600 text-white rounded px-1.5 py-0.5 tracking-wide">{{ $tpl->event_type }}</div>
                            <div class="p-4 pt-8 flex flex-col gap-3 h-full">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="space-y-1">
                                        <h4 class="font-medium text-sm text-gray-800">{{ $eventDescriptions[$tpl->event_type] ?? $tpl->event_type }}</h4>
                                        <label class="inline-flex items-center gap-1 text-[11px] text-gray-600 bg-gray-100 px-2 py-0.5 rounded">
                                            <input type="checkbox" name="templates[{{ $tpl->event_type }}][is_active]" value="1" {{ $tpl->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            فعال
                                        </label>
                                    </div>
                                    <div class="text-xs text-gray-400 font-mono" data-char-counter>0</div>
                                </div>
                                <textarea data-template-text name="templates[{{ $tpl->event_type }}][template]" rows="4" class="w-full text-xs leading-relaxed font-mono border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 rounded-md bg-white/70 resize-y p-2 shadow-inner min-h-[120px]" dir="auto" required>{{ $tpl->template }}</textarea>
                                <div class="flex justify-between items-center mt-auto pt-1 border-t border-dashed border-gray-200">
                                    <button type="button" class="text-[11px] text-indigo-600 hover:text-indigo-800 flex items-center gap-1" data-copy-btn>
                                        کپی
                                    </button>
                                    <span class="text-[10px] text-gray-500" data-parts-indicator>1 پارت</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </form>
        </div>
    </div>

    <!-- Custom Templates -->
    <div id="custom-section" class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow-sm rounded-xl p-5 md:p-7 space-y-10 border border-gray-100">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    قالب‌های سفارشی
                </h3>
                <a href="{{ route('admin.sms-templates.create') }}" class="px-4 py-2 bg-emerald-600 text-white rounded-lg shadow hover:bg-emerald-700 text-sm">قالب جدید</a>
            </div>

            <!-- Categories Manager -->
            <div class="space-y-4">
                <h4 class="font-medium text-sm text-gray-700 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7"/></svg>
                    مدیریت دسته‌ها
                </h4>
                <form method="POST" action="{{ route('admin.sms-template-categories.store') }}" class="flex flex-col sm:flex-row gap-3">
                    @csrf
                    <input type="text" name="name" placeholder="نام دسته جدید" class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" required>
                    <button class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm hover:bg-black transition">افزودن دسته</button>
                </form>
                <div class="flex flex-wrap gap-2">
                    @forelse($categories as $cat)
                        <form method="POST" action="{{ route('admin.sms-template-categories.destroy', $cat) }}" onsubmit="return confirm('حذف دسته و انتقال قالب‌هایش به حالت بدون دسته؟')" class="inline-block">
                            @csrf @method('DELETE')
                            <span class="inline-flex items-center bg-gray-100 text-gray-700 px-2 py-1 rounded-full text-xs border border-gray-200">
                                {{ $cat->name }}
                                <button class="ml-1 text-red-500 leading-none" title="حذف">×</button>
                            </span>
                        </form>
                    @empty
                        <span class="text-xs text-gray-500">دسته‌ای تعریف نشده.</span>
                    @endforelse
                </div>
            </div>

            <!-- Custom Templates Grid -->
            <div class="space-y-10">
                @foreach($categories as $cat)
                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            <h5 class="font-semibold text-sm text-gray-800">{{ $cat->name }}</h5>
                            <span class="text-[10px] bg-gray-200 text-gray-700 px-1.5 py-0.5 rounded">{{ $cat->templates->count() }} قالب</span>
                        </div>
                        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                            @forelse($cat->templates as $t)
                                <div class="relative border border-gray-200 rounded-lg p-4 bg-white/70 hover:shadow transition flex flex-col">
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <span class="font-medium text-sm text-gray-800 line-clamp-1" title="{{ $t->title }}">{{ $t->title }}</span>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded {{ $t->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600' }}">{{ $t->is_active ? 'فعال' : 'غیرفعال' }}</span>
                                    </div>
                                    <pre class="whitespace-pre-wrap text-[11px] font-mono bg-gray-50 border border-gray-200 rounded p-2 h-32 overflow-auto leading-relaxed">{{ $t->template }}</pre>
                                    <div class="mt-3 flex justify-between items-center text-[11px]">
                                        <a href="{{ route('admin.sms-templates.edit', $t->id) }}" class="text-indigo-600 hover:text-indigo-800">ویرایش</a>
                                        <form action="{{ route('admin.sms-templates.destroy', $t->id) }}" method="POST" onsubmit="return confirm('حذف قالب؟')">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600 hover:text-red-800">حذف</button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-gray-500">قالبی داخل این دسته ثبت نشده.</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
                @if($categories->isEmpty())
                    <div class="text-xs text-gray-500">برای شروع یک دسته ایجاد کنید.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    (function(){
        const partLimitFa = 70; // تقریبی
        document.querySelectorAll('[data-template-text]').forEach(area => {
            const wrapper = area.closest('div');
            const counterEl = wrapper.querySelector('[data-char-counter]');
            const partsEl = wrapper.querySelector('[data-parts-indicator]');
            const update = () => {
                const val = area.value || '';
                const length = [...val].length; // handle unicode
                if(counterEl) counterEl.textContent = length + ' ch';
                const parts = length === 0 ? 0 : Math.ceil(length / partLimitFa);
                if(partsEl) partsEl.textContent = parts + ' پارت';
                // highlight unknown placeholders
                const phRegex = /\{\{?([a-zA-Z0-9_]+)\}?\}/g;
                const known = new Set(@json($placeholders));
                let m, unknown = new Set();
                while((m = phRegex.exec(val))){ if(!known.has(m[1])) unknown.add(m[1]); }
                area.classList.toggle('ring-2', unknown.size>0);
                area.classList.toggle('ring-red-400', unknown.size>0);
                area.title = unknown.size>0 ? 'Placeholder ناشناخته: ' + Array.from(unknown).join(', ') : '';
            };
            area.addEventListener('input', update); update();
        });
        document.querySelectorAll('[data-copy-btn]').forEach(btn =>{
            btn.addEventListener('click', () => {
                const ta = btn.closest('[data-template-text]') ? btn.closest('[data-template-text]') : btn.closest('.group').querySelector('[data-template-text]');
                if(!ta) return;
                navigator.clipboard.writeText(ta.value).then(()=>{ btn.textContent='کپی شد'; setTimeout(()=>btn.textContent='کپی',1600); });
            });
        });
    })();
</script>
@endsection

