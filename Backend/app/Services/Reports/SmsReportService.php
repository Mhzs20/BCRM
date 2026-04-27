<?php

namespace App\Services\Reports;

use App\Models\SmsTransaction;
use App\Models\SalonSmsBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SmsReportService extends BaseReportService
{
    /**
     * Generate preset report.
     */
    public function generatePresetReport($salonId, $period = null)
    {
        $this->salonId = $salonId;

        if ($period) {
            $dateRange = $this->getPresetDateRange($period);
            $this->dateFrom = $dateRange['from'];
            $this->dateTo = $dateRange['to'];
        } else {
            $this->dateFrom = null;
            $this->dateTo = null;
        }

        return [
            'period' => $period ?? 'overall',
            'date_range' => $this->dateFrom && $this->dateTo
                ? $this->getPersianDateRange($this->dateFrom, $this->dateTo)
                : null,
            'kpis' => $this->calculateKPIs(),
            'charts' => $this->generateCharts(['period' => $period]),
            'sections' => $this->generateSections(),
        ];
    }

    /**
     * Generate custom report.
     */
    public function generateCustomReport($salonId, array $filters)
    {
        $this->salonId = $salonId;
        $this->applyFilters($filters);

        return [
            'filters_applied' => $this->buildFiltersSummary($filters),
            'kpis' => $this->calculateKPIs($filters),
            'charts' => $this->generateCharts($filters),
            'sections' => $this->generateSections($filters),
        ];
    }

    /**
     * Apply custom filters.
     */
    protected function applyFilters(array $filters)
    {
        $this->dateFrom = isset($filters['date_from']) ? \Carbon\Carbon::parse($filters['date_from']) : null;
        $this->dateTo = isset($filters['date_to']) ? \Carbon\Carbon::parse($filters['date_to']) : null;
        $this->timeFrom = $filters['time_from'] ?? null;
        $this->timeTo = $filters['time_to'] ?? null;
    }

    /**
     * Calculate KPIs.
     */
    protected function calculateKPIs(array $filters = [])
    {
        // Base query for sent/consumed SMS (excluding purchase transactions)
        $baseQuery = SmsTransaction::where('salon_id', $this->salonId)
            ->where(function ($q) {
                $q->where('type', '!=', 'purchase')
                  ->orWhereNull('type');
            })
            ->where('sms_type', '!=', 'purchase');

        // Total SMS consumed (sent)
        $totalSms = (clone $baseQuery)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('sent_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('sent_at', '<=', $this->dateTo);
            })
            ->whereNotNull('sent_at')
            ->sum('sms_count');

        // Manual SMS (manual_sms, manual_reminder, bulk campaigns)
        $manualSms = (clone $baseQuery)
            ->whereIn('sms_type', ['manual_sms', 'manual_reminder', 'bulk'])
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('sent_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('sent_at', '<=', $this->dateTo);
            })
            ->whereNotNull('sent_at')
            ->sum('sms_count');

        // System SMS (appointment-related and automated types)
        $systemSms = (clone $baseQuery)
            ->whereIn('sms_type', [
                'appointment_confirmation', 'appointment_modification',
                'appointment_cancellation', 'appointment_reminder',
                'satisfaction_survey', 'exclusive_link',
            ])
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('sent_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('sent_at', '<=', $this->dateTo);
            })
            ->whereNotNull('sent_at')
            ->sum('sms_count');

        // Total consumed SMS (same as total_sms, but for clarity)
        $totalConsumedSms = $totalSms;

        // Approved SMS
        $approvedSms = (clone $baseQuery)
            ->where('approval_status', 'approved')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('sent_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('sent_at', '<=', $this->dateTo);
            })
            ->whereNotNull('sent_at')
            ->sum('sms_count');

        // Rejected SMS
        $rejectedSms = (clone $baseQuery)
            ->where('approval_status', 'rejected')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('sent_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('sent_at', '<=', $this->dateTo);
            })
            ->whereNotNull('sent_at')
            ->sum('sms_count');

        // Average daily consumption based on actual date range of sent SMS
        if ($this->dateFrom && $this->dateTo) {
            $daysCount = $this->dateFrom->diffInDays($this->dateTo) + 1;
        } else {
            $firstDate = (clone $baseQuery)->whereNotNull('sent_at')->min('sent_at');
            $lastDate  = (clone $baseQuery)->whereNotNull('sent_at')->max('sent_at');
            if ($firstDate && $lastDate) {
                $daysCount = \Carbon\Carbon::parse($firstDate)->diffInDays(\Carbon\Carbon::parse($lastDate)) + 1;
            } else {
                $daysCount = 1;
            }
        }
        $avgDailyConsumption = $totalSms / max($daysCount, 1);

        // Current balance (remaining SMS in account)
        $currentBalance = SalonSmsBalance::where('salon_id', $this->salonId)->first();

        return [
            'total_sms' => $totalSms,
            'manual_sms' => $manualSms,
            'system_sms' => $systemSms,
            'total_cost' => $totalConsumedSms, // تعداد پیامک مصرف شده (نه مبلغ)
            'approved_sms' => $approvedSms,
            'rejected_sms' => $rejectedSms,
            'avg_daily_consumption' => round($avgDailyConsumption, 1),
            'current_balance' => $currentBalance->balance ?? 0,
        ];
    }

    /**
     * Generate charts data.
     */
    protected function generateCharts(array $filters = [])
    {
        $grouping = $this->getChartGrouping($filters['period'] ?? null);
        $sentGroupSql = str_replace('{{column}}', 'sent_at', $grouping['sql']);
        $createdGroupSql = str_replace('{{column}}', 'created_at', $grouping['sql']);

        // SMS sent by period (excluding purchase transactions)
        $smsByPeriod = SmsTransaction::where('salon_id', $this->salonId)
            ->where(function ($q) {
                $q->where('type', '!=', 'purchase')
                  ->orWhereNull('type');
            })
            ->where('sms_type', '!=', 'purchase')
            ->whereNotNull('sent_at')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('sent_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('sent_at', '<=', $this->dateTo);
            })
            ->selectRaw($sentGroupSql . ', SUM(sms_count) as count')
            ->groupBy('group_key')
            ->orderBy('group_key')
            ->get()
            ->pluck('count', 'group_key');

        $labels = $grouping['labels'];
        $smsData = array_fill(0, count($labels), 0);

        foreach ($smsByPeriod as $key => $count) {
            if ($grouping['type'] === 'weekday') {
                $mapping = [7 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6];
                $index = $mapping[$key] ?? null;
                if ($index !== null) $smsData[$index] = $count;
            } elseif ($grouping['type'] === 'day') {
                $index = $key - 1;
                if ($index >= 0 && $index < count($smsData)) $smsData[$index] = $count;
            } elseif ($grouping['type'] === 'month') {
                try {
                    $date = Carbon::parse($key . '-01');
                    $verta = new \Hekmatinasser\Verta\Verta($date);
                    $monthIndex = $verta->month - 1;
                    if ($monthIndex >= 0 && $monthIndex < 12) $smsData[$monthIndex] += $count;
                } catch (\Exception $e) {}
            }
        }

        // SMS packages purchased over time
        $packagesByPeriod = SmsTransaction::where('salon_id', $this->salonId)
            ->where('sms_type', 'purchase')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('created_at', '<=', $this->dateTo);
            })
            ->selectRaw($createdGroupSql . ', SUM(sms_count) as total_sms, COUNT(*) as package_count')
            ->groupBy('group_key')
            ->orderBy('group_key')
            ->get();

        $packageSmsData = array_fill(0, count($labels), 0);
        $packageCountData = array_fill(0, count($labels), 0);

        foreach ($packagesByPeriod as $item) {
            if ($grouping['type'] === 'weekday') {
                $mapping = [7 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6];
                $index = $mapping[$item->group_key] ?? null;
                if ($index !== null) {
                    $packageSmsData[$index] = $item->total_sms;
                    $packageCountData[$index] = $item->package_count;
                }
            } elseif ($grouping['type'] === 'day') {
                $index = $item->group_key - 1;
                if ($index >= 0 && $index < count($packageSmsData)) {
                    $packageSmsData[$index] = $item->total_sms;
                    $packageCountData[$index] = $item->package_count;
                }
            } elseif ($grouping['type'] === 'month') {
                try {
                    $date = Carbon::parse($item->group_key . '-01');
                    $verta = new \Hekmatinasser\Verta\Verta($date);
                    $monthIndex = $verta->month - 1;
                    if ($monthIndex >= 0 && $monthIndex < 12) {
                        $packageSmsData[$monthIndex] += $item->total_sms;
                        $packageCountData[$monthIndex] += $item->package_count;
                    }
                } catch (\Exception $e) {}
            }
        }

        return [
            'sms_by_day' => [
                'labels' => $labels,
                'data' => $smsData,
            ],
            'packages_purchased' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'تعداد پیامک',
                        'data' => $packageSmsData,
                    ],
                    [
                        'label' => 'تعداد بسته',
                        'data' => $packageCountData,
                    ],
                ],
            ],
        ];
    }

    /**
     * Generate sections.
     */
    protected function generateSections(array $filters = [])
    {
        return [
            'sms_by_type' => $this->getSmsByType(),
            'sms_by_status' => $this->getSmsByStatus(),
            'sms_by_template' => $this->getSmsByTemplate(),
            'daily_consumption' => $this->getDailyConsumption(),
            'purchased_packages' => $this->getPurchasedPackages(),
        ];
    }

    /**
     * Get SMS by type (excluding purchase transactions).
     */
    protected function getSmsByType()
    {
        return SmsTransaction::where('salon_id', $this->salonId)
            ->where(function ($q) {
                $q->where('type', '!=', 'purchase')
                  ->orWhereNull('type');
            })
            ->where('sms_type', '!=', 'purchase')
            ->whereNotNull('sent_at')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('sent_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('sent_at', '<=', $this->dateTo);
            })
            ->select('sms_type', DB::raw('SUM(sms_count) as count'))
            ->groupBy('sms_type')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->sms_type ?? 'نامشخص',
                    'count' => $item->count,
                ];
            });
    }

    /**
     * Get SMS by status (excluding purchase transactions).
     */
    protected function getSmsByStatus()
    {
        return SmsTransaction::where('salon_id', $this->salonId)
            ->where(function ($q) {
                $q->where('type', '!=', 'purchase')
                  ->orWhereNull('type');
            })
            ->where('sms_type', '!=', 'purchase')
            ->whereNotNull('sent_at')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('sent_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('sent_at', '<=', $this->dateTo);
            })
            ->select('status', DB::raw('SUM(sms_count) as count'))
            ->groupBy('status')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => $item->status ?? 'نامشخص',
                    'count' => $item->count,
                ];
            });
    }

    /**
     * Get SMS by template (excluding purchase transactions).
     */
    protected function getSmsByTemplate()
    {
        return SmsTransaction::where('salon_id', $this->salonId)
            ->where(function ($q) {
                $q->where('type', '!=', 'purchase')
                  ->orWhereNull('type');
            })
            ->where('sms_type', '!=', 'purchase')
            ->whereNotNull('template_id')
            ->whereNotNull('sent_at')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('sent_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('sent_at', '<=', $this->dateTo);
            })
            ->select('template_id', DB::raw('SUM(sms_count) as count'))
            ->groupBy('template_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'template_id' => $item->template_id,
                    'count' => $item->count,
                ];
            });
    }

    /**
     * Get daily consumption (excluding purchase transactions).
     */
    protected function getDailyConsumption()
    {
        return SmsTransaction::where('salon_id', $this->salonId)
            ->where(function ($q) {
                $q->where('type', '!=', 'purchase')
                  ->orWhereNull('type');
            })
            ->where('sms_type', '!=', 'purchase')
            ->whereNotNull('sent_at')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('sent_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('sent_at', '<=', $this->dateTo);
            })
            ->select(DB::raw('DATE(sent_at) as date'), DB::raw('SUM(sms_count) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => $item->count,
                ];
            });
    }

    /**
     * Get purchased packages details.
     */
    protected function getPurchasedPackages()
    {
        return SmsTransaction::where('salon_id', $this->salonId)
            ->where('sms_type', 'purchase')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('created_at', '<=', $this->dateTo);
            })
            ->with('smsPackage:id,name,sms_count,price')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->created_at->format('Y-m-d'),
                    'package_name' => $item->smsPackage->name ?? 'نامشخص',
                    'sms_count' => $item->sms_count,
                    'amount' => $item->amount,
                    'description' => $item->description,
                ];
            });
    }
}
