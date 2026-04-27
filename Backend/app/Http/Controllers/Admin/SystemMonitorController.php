<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

class SystemMonitorController extends Controller
{
    /**
     * Show system monitor dashboard with scheduler status and logs.
     */
    public function index()
    {
        $scheduledTasks = $this->getScheduledTasks();
        $appLog = $this->getAppLog(100);

        return view('admin.system-monitor.index', compact('scheduledTasks', 'appLog'));
    }

    /**
     * API endpoint to get fresh scheduler status (for AJAX refresh).
     */
    public function schedulerStatus()
    {
        $scheduledTasks = $this->getScheduledTasks();
        return response()->json(['tasks' => $scheduledTasks]);
    }

    /**
     * API endpoint to get app log lines (for AJAX refresh).
     */
    public function appLog(Request $request)
    {
        $lines = $request->input('lines', 100);
        $lines = min(max($lines, 10), 500);
        $log = $this->getAppLog($lines);
        return response()->json(['log' => $log]);
    }

    /**
     * Get all scheduled tasks with their status information.
     */
    private function getScheduledTasks(): array
    {
        $tasks = [];

        // Define scheduled tasks from Kernel (cache_key must match Kernel tracking keys)
        $taskDefinitions = [
            [
                'name' => 'ارسال یادآوری SMS',
                'command' => 'sms:send-reminders',
                'cache_key' => 'sms_send_reminders',
                'schedule' => 'هر دقیقه',
                'type' => 'command',
            ],
            [
                'name' => 'لغو نوبت‌های گذشته',
                'command' => 'appointments:cancel-past',
                'cache_key' => 'appointments_cancel_past',
                'schedule' => 'هر دقیقه',
                'type' => 'command',
            ],
            [
                'name' => 'بررسی وضعیت SMS',
                'command' => 'sms:check-status',
                'cache_key' => 'CheckSmsStatus',
                'schedule' => 'هر دقیقه',
                'type' => 'job',
            ],
            [
                'name' => 'یادآوری ترمیم',
                'command' => 'renewal:send-reminders',
                'cache_key' => 'renewal_send_reminders',
                'schedule' => 'هر دقیقه',
                'type' => 'command',
            ],
            [
                'name' => 'یادآوری تولد',
                'command' => 'reminders:send-birthday',
                'cache_key' => 'reminders_send_birthday',
                'schedule' => 'هر دقیقه',
                'type' => 'command',
            ],
            [
                'name' => 'نظرسنجی رضایت',
                'command' => 'satisfaction:process',
                'cache_key' => 'satisfaction_process',
                'schedule' => 'هر دقیقه',
                'type' => 'command',
            ],
            [
                'name' => 'پیگیری خودکار مشتریان',
                'command' => 'followup:process-customers',
                'cache_key' => 'followup_process_customers',
                'schedule' => 'هر دقیقه',
                'type' => 'command',
            ],
        ];

        foreach ($taskDefinitions as $def) {
            $cacheKey = 'scheduler_last_run_' . $def['cache_key'];
            $lastRun = Cache::get($cacheKey);
            $lastError = Cache::get($cacheKey . '_error');
            $lastDuration = Cache::get($cacheKey . '_duration');

            // Determine status based on last run time
            $status = 'unknown';
            $statusClass = 'gray';
            $statusText = 'نامشخص';

            if ($lastRun) {
                $lastRunCarbon = Carbon::parse($lastRun);
                $minutesSinceLastRun = $lastRunCarbon->diffInMinutes(now());

                // Determine expected max interval
                $maxInterval = match (true) {
                    str_contains($def['schedule'], 'دقیقه') && str_contains($def['schedule'], '۵') => 10,
                    str_contains($def['schedule'], 'دقیقه') => 3,
                    str_contains($def['schedule'], 'ساعت') => 120,
                    default => 15,
                };

                if ($lastError) {
                    $status = 'error';
                    $statusClass = 'red';
                    $statusText = 'خطا';
                } elseif ($minutesSinceLastRun > $maxInterval) {
                    $status = 'warning';
                    $statusClass = 'yellow';
                    $statusText = 'تاخیر';
                } else {
                    $status = 'ok';
                    $statusClass = 'green';
                    $statusText = 'عملکرد عادی';
                }
            }

            $tasks[] = [
                'name' => $def['name'],
                'command' => $def['command'],
                'schedule' => $def['schedule'],
                'type' => $def['type'],
                'status' => $status,
                'status_class' => $statusClass,
                'status_text' => $statusText,
                'last_run' => $lastRun ? Jalalian::fromCarbon(Carbon::parse($lastRun))->format('Y/m/d H:i:s') : '-',
                'last_run_ago' => $lastRun ? Carbon::parse($lastRun)->diffForHumans() : '-',
                'last_error' => $lastError,
                'last_duration' => $lastDuration ? round($lastDuration, 2) . 's' : '-',
            ];
        }

        return $tasks;
    }

    /**
     * Read the last N lines from the application log file.
     */
    private function getAppLog(int $lines = 100): array
    {
        $logFile = storage_path('logs/laravel.log');

        if (!File::exists($logFile)) {
            return ['lines' => [], 'file' => $logFile, 'size' => 0, 'exists' => false];
        }

        $fileSize = File::size($logFile);
        $content = '';

        // Read from end of file efficiently
        $fp = fopen($logFile, 'r');
        if (!$fp) {
            return ['lines' => [], 'file' => $logFile, 'size' => $fileSize, 'exists' => true, 'error' => 'Cannot open file'];
        }

        // If file is small, read all
        if ($fileSize <= 65536) {
            $content = fread($fp, $fileSize);
        } else {
            // Read last 64KB
            fseek($fp, -65536, SEEK_END);
            $content = fread($fp, 65536);
        }
        fclose($fp);

        $allLines = explode("\n", $content);
        $lastLines = array_slice($allLines, -$lines);

        // Parse log entries with level detection
        $parsedLines = [];
        foreach ($lastLines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $level = 'info';
            if (preg_match('/\.(ERROR|error)/', $line)) {
                $level = 'error';
            } elseif (preg_match('/\.(WARNING|warning)/', $line)) {
                $level = 'warning';
            } elseif (preg_match('/\.(DEBUG|debug)/', $line)) {
                $level = 'debug';
            } elseif (preg_match('/\.(CRITICAL|critical|EMERGENCY|emergency|ALERT|alert)/', $line)) {
                $level = 'critical';
            }

            $parsedLines[] = [
                'text' => $line,
                'level' => $level,
            ];
        }

        return [
            'lines' => $parsedLines,
            'file' => basename($logFile),
            'size' => $this->formatFileSize($fileSize),
            'exists' => true,
        ];
    }

    /**
     * Format file size to human readable.
     */
    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }

    /**
     * Map command names to cache keys (must match Kernel tracking keys).
     */
    private function getCommandCacheKeyMap(): array
    {
        return [
            'sms:send-reminders' => 'sms_send_reminders',
            'appointments:cancel-past' => 'appointments_cancel_past',
            'sms:check-status' => 'CheckSmsStatus',
            'renewal:send-reminders' => 'renewal_send_reminders',
            'reminders:send-birthday' => 'reminders_send_birthday',
            'satisfaction:process' => 'satisfaction_process',
            'followup:process-customers' => 'followup_process_customers',
        ];
    }

    /**
     * Run a specific scheduler command manually.
     */
    public function runCommand(Request $request)
    {
        $command = $request->input('command');
        
        $allowedCommands = array_keys($this->getCommandCacheKeyMap());

        if (!in_array($command, $allowedCommands)) {
            return response()->json(['error' => 'دستور مجاز نیست'], 403);
        }

        $cacheKeyMap = $this->getCommandCacheKeyMap();
        $cacheKey = $cacheKeyMap[$command] ?? null;

        try {
            $startTime = microtime(true);
            Artisan::call($command);
            $output = Artisan::output();
            $duration = round(microtime(true) - $startTime, 2);

            // Update cache to reflect manual run
            if ($cacheKey) {
                Cache::put("scheduler_last_run_{$cacheKey}", now()->toIso8601String(), 86400);
                Cache::put("scheduler_last_run_{$cacheKey}_duration", $duration, 86400);
                Cache::forget("scheduler_last_run_{$cacheKey}_error");
            }

            return response()->json([
                'success' => true,
                'output' => $output,
                'duration' => $duration . 's',
            ]);
        } catch (\Exception $e) {
            // Track error in cache
            if ($cacheKey) {
                Cache::put("scheduler_last_run_{$cacheKey}", now()->toIso8601String(), 86400);
                Cache::put("scheduler_last_run_{$cacheKey}_error", $e->getMessage(), 86400);
            }

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear the application log file.
     */
    public function clearLog()
    {
        $logFile = storage_path('logs/laravel.log');
        
        if (File::exists($logFile)) {
            File::put($logFile, '');
        }

        return response()->json(['success' => true, 'message' => 'لاگ با موفقیت پاک شد']);
    }
}
