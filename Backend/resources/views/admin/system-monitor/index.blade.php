@extends('admin.layouts.app')

@section('title', 'مانیتورینگ سیستم')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <i class="ri-pulse-line text-indigo-600 ml-2"></i>
            مانیتورینگ سیستم
        </h2>
        <div class="flex items-center gap-2">
            <span id="lastRefresh" class="text-xs text-gray-500"></span>
            <button onclick="refreshAll()" class="inline-flex items-center px-3 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                <i class="ri-refresh-line ml-1" id="refreshIcon"></i>
                بروزرسانی
            </button>
        </div>
    </div>
@endsection

@section('content')
<div class="py-4 sm:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- ===== Scheduler Status Section ===== --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 sm:p-6 border-b border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="ri-timer-line text-xl text-indigo-600 ml-2"></i>
                        وضعیت زمان‌بندها (Schedulers)
                    </h3>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-3 text-xs">
                            <span class="flex items-center"><span class="w-2.5 h-2.5 rounded-full bg-green-500 ml-1"></span> عادی</span>
                            <span class="flex items-center"><span class="w-2.5 h-2.5 rounded-full bg-yellow-500 ml-1"></span> تاخیر</span>
                            <span class="flex items-center"><span class="w-2.5 h-2.5 rounded-full bg-red-500 ml-1"></span> خطا</span>
                            <span class="flex items-center"><span class="w-2.5 h-2.5 rounded-full bg-gray-400 ml-1"></span> نامشخص</span>
                        </div>
                        <button onclick="runAllCommands()" 
                                class="inline-flex items-center px-3 py-1.5 text-xs bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                                id="runAllBtn">
                            <i class="ri-play-circle-line ml-1"></i> اجرای همه
                        </button>
                    </div>
                </div>

                {{-- Desktop Table --}}
                <div class="hidden sm:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="schedulerTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">نام تسک</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">دستور</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">زمان‌بندی</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">وضعیت</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">آخرین اجرا</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">مدت اجرا</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">عملیات</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($scheduledTasks as $task)
                            <tr class="hover:bg-gray-50 transition-colors" id="task-row-{{ $loop->index }}">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="w-2.5 h-2.5 rounded-full bg-{{ $task['status_class'] }}-500 ml-2 flex-shrink-0"></span>
                                        <span class="text-sm font-medium text-gray-900">{{ $task['name'] }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <code class="text-xs bg-gray-100 px-2 py-1 rounded font-mono" dir="ltr">{{ $task['command'] }}</code>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">{{ $task['schedule'] }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $task['status_class'] }}-100 text-{{ $task['status_class'] }}-800">
                                        {{ $task['status_text'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" dir="ltr">{{ $task['last_run'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $task['last_run_ago'] }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">{{ $task['last_duration'] }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button onclick="runCommand('{{ $task['command'] }}', this)" 
                                                class="inline-flex items-center px-2 py-1 text-xs bg-blue-50 text-blue-700 rounded hover:bg-blue-100 transition-colors"
                                                title="اجرای دستی">
                                            <i class="ri-play-line ml-1"></i> اجرا
                                        </button>
                                        @if($task['last_error'])
                                        <button onclick="showError({{ $loop->index }})" 
                                                class="inline-flex items-center px-2 py-1 text-xs bg-red-50 text-red-700 rounded hover:bg-red-100 transition-colors"
                                                title="مشاهده خطا">
                                            <i class="ri-error-warning-line ml-1"></i> خطا
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @if($task['last_error'])
                            <tr id="error-row-{{ $loop->index }}" class="hidden">
                                <td colspan="7" class="px-4 py-3 bg-red-50">
                                    <div class="text-sm">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="font-medium text-red-800"><i class="ri-error-warning-fill ml-1"></i> آخرین خطا:</span>
                                            <button onclick="hideError({{ $loop->index }})" class="text-red-400 hover:text-red-600">
                                                <i class="ri-close-line"></i>
                                            </button>
                                        </div>
                                        <pre class="text-xs text-red-700 bg-red-100 rounded p-3 overflow-x-auto whitespace-pre-wrap max-h-48 overflow-y-auto" dir="ltr">{{ $task['last_error'] }}</pre>
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Cards --}}
                <div class="sm:hidden space-y-3">
                    @foreach($scheduledTasks as $task)
                    <div class="border rounded-lg p-4 {{ $task['status'] === 'error' ? 'border-red-200 bg-red-50' : ($task['status'] === 'warning' ? 'border-yellow-200 bg-yellow-50' : 'border-gray-200') }}">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center">
                                <span class="w-3 h-3 rounded-full bg-{{ $task['status_class'] }}-500 ml-2"></span>
                                <span class="font-medium text-gray-900">{{ $task['name'] }}</span>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $task['status_class'] }}-100 text-{{ $task['status_class'] }}-800">
                                {{ $task['status_text'] }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 mt-2">
                            <div><span class="text-gray-400">زمان‌بندی:</span> {{ $task['schedule'] }}</div>
                            <div><span class="text-gray-400">مدت:</span> {{ $task['last_duration'] }}</div>
                            <div class="col-span-2"><span class="text-gray-400">آخرین اجرا:</span> <span dir="ltr">{{ $task['last_run'] }}</span></div>
                        </div>
                        <div class="flex gap-2 mt-3">
                            <button onclick="runCommand('{{ $task['command'] }}', this)" 
                                    class="flex-1 text-center px-2 py-1.5 text-xs bg-blue-50 text-blue-700 rounded hover:bg-blue-100 transition-colors">
                                <i class="ri-play-line ml-1"></i> اجرای دستی
                            </button>
                            @if($task['last_error'])
                            <button onclick="alert('{{ addslashes($task['last_error']) }}')" 
                                    class="flex-1 text-center px-2 py-1.5 text-xs bg-red-50 text-red-700 rounded hover:bg-red-100 transition-colors">
                                <i class="ri-error-warning-line ml-1"></i> مشاهده خطا
                            </button>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ===== Command Output Modal ===== --}}
        <div id="commandOutputModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" onclick="closeCommandModal()"></div>
                <div class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6 z-10">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900" id="commandModalTitle">نتیجه اجرا</h3>
                        <button onclick="closeCommandModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="ri-close-line text-xl"></i>
                        </button>
                    </div>
                    <div id="commandModalBody" class="bg-gray-900 rounded-lg p-4 text-sm font-mono text-green-400 max-h-96 overflow-y-auto" dir="ltr">
                        در حال اجرا...
                    </div>
                    <div class="mt-3 text-xs text-gray-500" id="commandModalFooter"></div>
                </div>
            </div>
        </div>

        {{-- ===== Application Log Section ===== --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 sm:p-6 border-b border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="ri-file-text-line text-xl text-indigo-600 ml-2"></i>
                        لاگ اپلیکیشن
                    </h3>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">
                            فایل: <code dir="ltr" class="bg-gray-100 px-1 rounded">{{ $appLog['file'] ?? 'laravel.log' }}</code>
                            @if(isset($appLog['size']))
                                | حجم: {{ $appLog['size'] }}
                            @endif
                        </span>
                        <select id="logLevelFilter" onchange="filterLogs()" class="text-xs border rounded px-2 py-1">
                            <option value="all">همه سطوح</option>
                            <option value="error">فقط خطاها</option>
                            <option value="warning">هشدارها</option>
                            <option value="critical">بحرانی</option>
                            <option value="info">اطلاعات</option>
                            <option value="debug">دیباگ</option>
                        </select>
                        <button onclick="refreshLog()" class="inline-flex items-center px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition-colors">
                            <i class="ri-refresh-line ml-1"></i> بروزرسانی
                        </button>
                        <button onclick="clearLog()" class="inline-flex items-center px-2 py-1 text-xs bg-red-50 text-red-700 rounded hover:bg-red-100 transition-colors">
                            <i class="ri-delete-bin-line ml-1"></i> پاک کردن
                        </button>
                    </div>
                </div>

                @if(!($appLog['exists'] ?? false))
                    <div class="text-center py-8 text-gray-500">
                        <i class="ri-file-unknow-line text-4xl mb-2"></i>
                        <p>فایل لاگ یافت نشد</p>
                    </div>
                @else
                    <div id="logContainer" class="bg-gray-900 rounded-lg p-4 font-mono text-sm max-h-[600px] overflow-y-auto overflow-x-auto" dir="ltr" style="direction: ltr;">
                        @forelse($appLog['lines'] as $line)
                            <div class="log-line log-{{ $line['level'] }} py-0.5 border-b border-gray-800 last:border-0 hover:bg-gray-800/50 transition-colors
                                {{ $line['level'] === 'error' ? 'text-red-400' : '' }}
                                {{ $line['level'] === 'warning' ? 'text-yellow-400' : '' }}
                                {{ $line['level'] === 'critical' ? 'text-red-500 font-bold' : '' }}
                                {{ $line['level'] === 'debug' ? 'text-gray-500' : '' }}
                                {{ $line['level'] === 'info' ? 'text-green-400' : '' }}
                            ">{{ $line['text'] }}</div>
                        @empty
                            <div class="text-gray-500 text-center py-4">لاگی موجود نیست</div>
                        @endforelse
                    </div>
                @endif
            </div>
        </div>

        {{-- ===== Quick System Info ===== --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i class="ri-server-line text-blue-600 text-xl"></i>
                    </div>
                    <div class="mr-3">
                        <p class="text-xs text-gray-500">نسخه PHP</p>
                        <p class="text-sm font-semibold text-gray-900" dir="ltr">{{ PHP_VERSION }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-red-100 rounded-lg">
                        <i class="ri-laravel-line text-red-600 text-xl"></i>
                    </div>
                    <div class="mr-3">
                        <p class="text-xs text-gray-500">نسخه Laravel</p>
                        <p class="text-sm font-semibold text-gray-900" dir="ltr">{{ app()->version() }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <i class="ri-database-2-line text-green-600 text-xl"></i>
                    </div>
                    <div class="mr-3">
                        <p class="text-xs text-gray-500">محیط</p>
                        <p class="text-sm font-semibold text-gray-900" dir="ltr">{{ app()->environment() }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <i class="ri-hard-drive-2-line text-purple-600 text-xl"></i>
                    </div>
                    <div class="mr-3">
                        <p class="text-xs text-gray-500">درایور کش</p>
                        <p class="text-sm font-semibold text-gray-900" dir="ltr">{{ config('cache.default') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Auto-scroll log to bottom
    document.addEventListener('DOMContentLoaded', function() {
        const logContainer = document.getElementById('logContainer');
        if (logContainer) {
            logContainer.scrollTop = logContainer.scrollHeight;
        }
        updateRefreshTime();
    });

    function updateRefreshTime() {
        const now = new Date();
        document.getElementById('lastRefresh').textContent = 'آخرین بروزرسانی: ' + now.toLocaleTimeString('fa-IR');
    }

    function showError(index) {
        const row = document.getElementById('error-row-' + index);
        if (row) row.classList.toggle('hidden');
    }

    function hideError(index) {
        const row = document.getElementById('error-row-' + index);
        if (row) row.classList.add('hidden');
    }

    function runCommand(command, btn) {
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="ri-loader-4-line animate-spin ml-1"></i> ...';
        btn.disabled = true;

        // Show modal
        document.getElementById('commandOutputModal').classList.remove('hidden');
        document.getElementById('commandModalTitle').textContent = 'اجرای: ' + command;
        document.getElementById('commandModalBody').textContent = 'در حال اجرا...';
        document.getElementById('commandModalFooter').textContent = '';

        fetch('{{ route("admin.system-monitor.run-command") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ command: command })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('commandModalBody').textContent = data.output || 'بدون خروجی';
                document.getElementById('commandModalFooter').textContent = 'مدت اجرا: ' + data.duration;
            } else {
                document.getElementById('commandModalBody').innerHTML = '<span class="text-red-400">' + (data.error || 'خطای ناشناخته') + '</span>';
            }
        })
        .catch(err => {
            document.getElementById('commandModalBody').innerHTML = '<span class="text-red-400">خطا در ارتباط: ' + err.message + '</span>';
        })
        .finally(() => {
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        });
    }

    function closeCommandModal() {
        document.getElementById('commandOutputModal').classList.add('hidden');
    }

    async function runAllCommands() {
        const commands = @json(array_column($scheduledTasks, 'command'));
        const btn = document.getElementById('runAllBtn');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="ri-loader-4-line animate-spin ml-1"></i> در حال اجرا...';
        btn.disabled = true;

        document.getElementById('commandOutputModal').classList.remove('hidden');
        document.getElementById('commandModalTitle').textContent = 'اجرای همه تسک‌ها';
        document.getElementById('commandModalBody').textContent = '';
        document.getElementById('commandModalFooter').textContent = '';

        const body = document.getElementById('commandModalBody');
        let results = [];

        for (const command of commands) {
            body.innerHTML += '<div class="text-yellow-400">⏳ ' + command + ' ...</div>';
            
            try {
                const res = await fetch('{{ route("admin.system-monitor.run-command") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ command: command })
                });
                const data = await res.json();
                
                if (data.success) {
                    body.innerHTML += '<div class="text-green-400">✅ ' + command + ' — ' + data.duration + '</div>';
                    results.push({ command, success: true });
                } else {
                    body.innerHTML += '<div class="text-red-400">❌ ' + command + ' — ' + (data.error || 'خطا') + '</div>';
                    results.push({ command, success: false });
                }
            } catch(e) {
                body.innerHTML += '<div class="text-red-400">❌ ' + command + ' — ' + e.message + '</div>';
                results.push({ command, success: false });
            }
        }

        const ok = results.filter(r => r.success).length;
        const fail = results.filter(r => !r.success).length;
        document.getElementById('commandModalFooter').textContent = `نتیجه: ${ok} موفق / ${fail} ناموفق از ${commands.length} تسک`;
        
        btn.innerHTML = originalHTML;
        btn.disabled = false;

        // Reload page after 2 seconds to show updated statuses
        setTimeout(() => location.reload(), 2000);
    }

    function filterLogs() {
        const filter = document.getElementById('logLevelFilter').value;
        const lines = document.querySelectorAll('.log-line');
        lines.forEach(line => {
            if (filter === 'all') {
                line.style.display = '';
            } else {
                line.style.display = line.classList.contains('log-' + filter) ? '' : 'none';
            }
        });
    }

    function refreshLog() {
        fetch('{{ route("admin.system-monitor.app-log") }}')
            .then(r => r.json())
            .then(data => {
                const container = document.getElementById('logContainer');
                if (!container || !data.log.lines) return;

                container.innerHTML = '';
                data.log.lines.forEach(line => {
                    const div = document.createElement('div');
                    div.className = 'log-line log-' + line.level + ' py-0.5 border-b border-gray-800 last:border-0 hover:bg-gray-800/50 transition-colors';
                    
                    if (line.level === 'error') div.classList.add('text-red-400');
                    else if (line.level === 'warning') div.classList.add('text-yellow-400');
                    else if (line.level === 'critical') div.classList.add('text-red-500', 'font-bold');
                    else if (line.level === 'debug') div.classList.add('text-gray-500');
                    else div.classList.add('text-green-400');

                    div.textContent = line.text;
                    container.appendChild(div);
                });
                container.scrollTop = container.scrollHeight;
                updateRefreshTime();

                // Reapply filter
                filterLogs();
            });
    }

    function clearLog() {
        if (!confirm('آیا مطمئن هستید که می‌خواهید فایل لاگ را پاک کنید؟')) return;

        fetch('{{ route("admin.system-monitor.clear-log") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('logContainer');
                if (container) container.innerHTML = '<div class="text-gray-500 text-center py-4">لاگ پاک شد</div>';
            }
        });
    }

    function refreshAll() {
        const icon = document.getElementById('refreshIcon');
        icon.classList.add('animate-spin');
        refreshLog();
        setTimeout(() => {
            icon.classList.remove('animate-spin');
            updateRefreshTime();
        }, 1500);
    }

    // Auto-refresh every 30 seconds
    setInterval(refreshLog, 30000);
</script>
@endpush
@endsection
