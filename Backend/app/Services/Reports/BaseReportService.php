<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

abstract class BaseReportService
{
    protected $salonId;
    protected $dateFrom;
    protected $dateTo;
    protected $timeFrom;
    protected $timeTo;

    /**
     * Apply date and time filters to query.
     */
    protected function applyDateTimeFilters($query, $dateColumn = 'created_at', $timeColumn = null)
    {
        if ($this->dateFrom) {
            $query->whereDate($dateColumn, '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate($dateColumn, '<=', $this->dateTo);
        }

        if ($timeColumn && $this->timeFrom) {
            $query->whereTime($timeColumn, '>=', $this->timeFrom);
        }

        if ($timeColumn && $this->timeTo) {
            $query->whereTime($timeColumn, '<=', $this->timeTo);
        }

        return $query;
    }

    /**
     * Get date range based on preset period.
     */
    protected function getPresetDateRange($period)
    {
        $now = Carbon::now();

        switch ($period) {
            case 'today':
                return [
                    'from' => $now->copy()->startOfDay(),
                    'to' => $now->copy()->endOfDay(),
                ];
            case 'yesterday':
                return [
                    'from' => $now->copy()->subDay()->startOfDay(),
                    'to' => $now->copy()->subDay()->endOfDay(),
                ];
            case 'weekly':
            case 'week':
                return [
                    'from' => $now->copy()->startOfWeek(),
                    'to' => $now->copy()->endOfWeek(),
                ];
            case 'last_week':
                return [
                    'from' => $now->copy()->subWeek()->startOfWeek(),
                    'to' => $now->copy()->subWeek()->endOfWeek(),
                ];
            case 'monthly':
            case 'month':
                return [
                    'from' => $now->copy()->startOfMonth(),
                    'to' => $now->copy()->endOfMonth(),
                ];
            case 'last_month':
                return [
                    'from' => $now->copy()->subMonth()->startOfMonth(),
                    'to' => $now->copy()->subMonth()->endOfMonth(),
                ];
            case 'yearly':
            case 'year':
                // Rolling 12 months: از همین موقع سال قبل تا الان
                return [
                    'from' => $now->copy()->subYear()->startOfDay(),
                    'to' => $now->copy()->endOfDay(),
                ];
            case 'last_year':
                // سال شمسی قبل کامل
                $verta = \Hekmatinasser\Verta\Verta::now();
                $lastYearStart = \Hekmatinasser\Verta\Verta::jalaliToGregorian($verta->year - 1, 1, 1);
                $lastYearEnd = \Hekmatinasser\Verta\Verta::jalaliToGregorian($verta->year - 1, 12, 29);
                return [
                    'from' => Carbon::createFromDate($lastYearStart[0], $lastYearStart[1], $lastYearStart[2])->startOfDay(),
                    'to' => Carbon::createFromDate($lastYearEnd[0], $lastYearEnd[1], $lastYearEnd[2])->endOfDay(),
                ];
            default:
                return [
                    'from' => $now->copy()->startOfMonth(),
                    'to' => $now->copy()->endOfMonth(),
                ];
        }
    }

    /**
     * Get weekday names in Persian.
     */
    protected function getWeekdayName($dayNumber)
    {
        $days = [
            0 => 'یکشنبه',
            1 => 'دوشنبه',
            2 => 'سه‌شنبه',
            3 => 'چهارشنبه',
            4 => 'پنج‌شنبه',
            5 => 'جمعه',
            6 => 'شنبه',
        ];

        return $days[$dayNumber] ?? 'نامشخص';
    }

    /**
     * Get chart grouping configuration based on period.
     * Returns SQL grouping and label generation logic.
     */
    protected function getChartGrouping($period = null)
    {
        // Determine period from date range if not provided
        if (!$period && $this->dateFrom && $this->dateTo) {
            $days = Carbon::parse($this->dateFrom)->diffInDays(Carbon::parse($this->dateTo));
            if ($days <= 7) {
                $period = 'weekly';
            } elseif ($days <= 31) {
                $period = 'monthly';
            } else {
                $period = 'yearly';
            }
        }

        switch ($period) {
            case 'today':
            case 'yesterday':
            case 'weekly':
            case 'last_week':
                // Group by day of week (روزهای هفته)
                return [
                    'sql' => 'DAYOFWEEK({{column}}) as group_key',
                    'type' => 'weekday',
                    'labels' => ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه'],
                    'formatter' => function($key) {
                        // DAYOFWEEK: 1=Sunday, so Saturday=7
                        $mapping = [7 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6];
                        $index = $mapping[$key] ?? 0;
                        return ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه'][$index];
                    }
                ];

            case 'monthly':
            case 'last_month':
                // Group by day of month (1 تا 31)
                return [
                    'sql' => 'DAY({{column}}) as group_key',
                    'type' => 'day',
                    'labels' => range(1, 31),
                    'formatter' => function($key) {
                        return $key . ' ام';
                    }
                ];

            case 'yearly':
            case 'last_year':
                // Group by Persian month (فروردین تا اسفند)
                return [
                    'sql' => 'DATE_FORMAT({{column}}, "%Y-%m") as group_key',
                    'type' => 'month',
                    'labels' => ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'],
                    'formatter' => function($key) {
                        // $key is in format "2025-12"
                        try {
                            $date = Carbon::parse($key . '-01');
                            $verta = new \Hekmatinasser\Verta\Verta($date);
                            $persianMonths = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
                            return $persianMonths[$verta->month - 1] ?? $key;
                        } catch (\Exception $e) {
                            return $key;
                        }
                    }
                ];

            default:
                // Default to weekly
                return $this->getChartGrouping('weekly');
        }
    }

    /**
     * Format number for display.
     */
    protected function formatNumber($number, $decimals = 0)
    {
        return number_format($number, $decimals, '.', ',');
    }

    /**
     * Calculate percentage change.
     */
    protected function calculatePercentageChange($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return (($current - $previous) / $previous) * 100;
    }

    /**
     * Build filters summary for display.
     */
    protected function buildFiltersSummary($filters)
    {
        $summary = [];

        if (isset($filters['date_from']) && $filters['date_from']) {
            // Convert to Persian if it's a valid date
            $dateValue = $filters['date_from'];
            try {
                $dateValue = $this->toPersianDate($dateValue);
            } catch (\Exception $e) {
                // Keep original if conversion fails
            }
            $summary[] = ['label' => 'از تاریخ', 'value' => $dateValue];
        }

        if (isset($filters['date_to']) && $filters['date_to']) {
            // Convert to Persian if it's a valid date
            $dateValue = $filters['date_to'];
            try {
                $dateValue = $this->toPersianDate($dateValue);
            } catch (\Exception $e) {
                // Keep original if conversion fails
            }
            $summary[] = ['label' => 'تا تاریخ', 'value' => $dateValue];
        }

        if (isset($filters['time_from']) && $filters['time_from']) {
            $summary[] = ['label' => 'از ساعت', 'value' => $filters['time_from']];
        }

        if (isset($filters['time_to']) && $filters['time_to']) {
            $summary[] = ['label' => 'تا ساعت', 'value' => $filters['time_to']];
        }

        return $summary;
    }
    
    /**
     * Convert date to Persian format for display.
     * 
     * @param \Carbon\Carbon|string $date
     * @return string
     */
    protected function toPersianDate($date)
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }
        
        return \Hekmatinasser\Verta\Verta::instance($date)->format('Y/m/d');
    }
    
    /**
     * Get date range in Persian format.
     * 
     * @param \Carbon\Carbon $from
     * @param \Carbon\Carbon $to
     * @return array
     */
    protected function getPersianDateRange($from, $to)
    {
        return [
            'from' => $this->toPersianDate($from),
            'to' => $this->toPersianDate($to),
            'from_gregorian' => $from->format('Y-m-d'),
            'to_gregorian' => $to->format('Y-m-d'),
        ];
    }
}
