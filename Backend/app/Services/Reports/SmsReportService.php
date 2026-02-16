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
    public function generatePresetReport($salonId, $period = 'weekly')
    {
        $this->salonId = $salonId;
        $dateRange = $this->getPresetDateRange($period);
        $this->dateFrom = $dateRange['from'];
        $this->dateTo = $dateRange['to'];

        return [
            'period' => $period,
            'date_range' => $this->getPersianDateRange($this->dateFrom, $this->dateTo),
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

        // Manual (promotional) SMS
        $manualSms = (clone $baseQuery)
            ->where('sms_type', 'manual')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('sent_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('sent_at', '<=', $this->dateTo);
            })
            ->whereNotNull('sent_at')
            ->sum('sms_count');

        // System SMS (reservation, cancellation, reminders)
        $systemSms = (clone $baseQuery)
            ->whereIn('sms_type', ['reservation', 'reminder', 'cancellation', 'confirmation', 'satisfaction'])
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

        // Average daily consumption
        $daysCount = $this->dateFrom && $this->dateTo 
            ? $this->dateFrom->diffInDays($this->dateTo) + 1 
            : 30;
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
        // SMS sent by day (excluding purchase transactions)
        $smsByDay = SmsTransaction::where('salon_id', $this->salonId)
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
            ->get();

        $dates = [];
        $counts = [];

        foreach ($smsByDay as $item) {
            $dates[] = $item->date;
            $counts[] = $item->count;
        }

        // SMS packages purchased over time
        $packagesPurchased = SmsTransaction::where('salon_id', $this->salonId)
            ->where('sms_type', 'purchase')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('created_at', '<=', $this->dateTo);
            })
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(sms_count) as total_sms'), DB::raw('COUNT(*) as package_count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $packageDates = [];
        $packageSmsCount = [];
        $packageCount = [];

        foreach ($packagesPurchased as $item) {
            $packageDates[] = $item->date;
            $packageSmsCount[] = $item->total_sms;
            $packageCount[] = $item->package_count;
        }

        return [
            'sms_by_day' => [
                'labels' => $dates,
                'data' => $counts,
            ],
            'packages_purchased' => [
                'labels' => $packageDates,
                'datasets' => [
                    [
                        'label' => 'تعداد پیامک',
                        'data' => $packageSmsCount,
                    ],
                    [
                        'label' => 'تعداد بسته',
                        'data' => $packageCount,
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
