<?php

namespace App\Services\Reports;

use App\Models\Staff;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\CustomerFeedback;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PersonnelReportService extends BaseReportService
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
        // Only override date/time if explicitly provided in filters
        if (isset($filters['date_from'])) {
            $this->dateFrom = $filters['date_from'];
        }
        if (isset($filters['date_to'])) {
            $this->dateTo = $filters['date_to'];
        }
        if (isset($filters['time_from'])) {
            $this->timeFrom = $filters['time_from'];
        }
        if (isset($filters['time_to'])) {
            $this->timeTo = $filters['time_to'];
        }
        $this->filters = $filters;
    }

    /**
     * Calculate KPIs.
     */
    protected function calculateKPIs(array $filters = [])
    {
        $this->applyFilters($filters);
        
        $staffQuery = Staff::where('salon_id', $this->salonId);
        
        // Apply personnel filter
        if (!empty($filters['personnel_ids']) && !in_array(0, $filters['personnel_ids'])) {
            $staffQuery->whereIn('id', $filters['personnel_ids']);
        }
        
        $totalStaff = $staffQuery->count();

        // Total completed appointments
        $totalCompletedQuery = Appointment::where('salon_id', $this->salonId)
            ->where('status', 'completed')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointment_date', '<=', $this->dateTo);
            });
            
        // Apply personnel filter
        if (!empty($filters['personnel_ids']) && !in_array(0, $filters['personnel_ids'])) {
            $totalCompletedQuery->whereIn('staff_id', $filters['personnel_ids']);
        }
        
        // Apply service filter
        if (!empty($filters['service_ids']) && !in_array(0, $filters['service_ids'])) {
            $totalCompletedQuery->whereHas('services', function ($q) use ($filters) {
                $q->whereIn('service_id', $filters['service_ids']);
            });
        }
        
        $totalCompleted = $totalCompletedQuery->count();

        // Top personnel by income: sum of total_price for completed appointments
        $topByIncomeQuery = Appointment::where('salon_id', $this->salonId)
            ->where('status', 'completed')
            ->whereNotNull('staff_id')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointment_date', '<=', $this->dateTo);
            });

        // Apply personnel filter
        if (!empty($filters['personnel_ids']) && !in_array(0, $filters['personnel_ids'])) {
            $topByIncomeQuery->whereIn('staff_id', $filters['personnel_ids']);
        }

        $topByIncomeRow = $topByIncomeQuery
            ->select('staff_id', DB::raw('SUM(total_price) as total_income'))
            ->groupBy('staff_id')
            ->orderByDesc('total_income')
            ->first();

        $topByIncomeStaff = $topByIncomeRow ? Staff::find($topByIncomeRow->staff_id) : null;
        $topByIncome = $topByIncomeRow ? (object)[
            'staff' => $topByIncomeStaff,
            'total_income' => $topByIncomeRow->total_income,
        ] : null;

        // Total commission
        $totalCommissionQuery = DB::table('expenses')
            ->where('salon_id', $this->salonId)
            ->where('expense_type', 'commission')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('date', '<=', $this->dateTo);
            });
            
        // Apply personnel filter
        if (!empty($filters['personnel_ids']) && !in_array(0, $filters['personnel_ids'])) {
            $totalCommissionQuery->whereIn('staff_id', $filters['personnel_ids']);
        }
        
        $totalCommission = $totalCommissionQuery->sum('amount');

        // Most customers personnel
        $mostCustomersQuery = Appointment::where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointment_date', '<=', $this->dateTo);
            });
            
        // Apply personnel filter
        if (!empty($filters['personnel_ids']) && !in_array(0, $filters['personnel_ids'])) {
            $mostCustomersQuery->whereIn('staff_id', $filters['personnel_ids']);
        }
        
        // Apply service filter
        if (!empty($filters['service_ids']) && !in_array(0, $filters['service_ids'])) {
            $mostCustomersQuery->whereHas('services', function ($q) use ($filters) {
                $q->whereIn('service_id', $filters['service_ids']);
            });
        }
        
        $mostCustomers = $mostCustomersQuery
            ->select('staff_id', DB::raw('COUNT(DISTINCT customer_id) as unique_customers'))
            ->groupBy('staff_id')
            ->orderByDesc('unique_customers')
            ->with('staff')
            ->first();

        // Best personnel by satisfaction (via appointment JOIN since cf.staff_id is not populated)
        $bestBySatisfactionQuery = DB::table('customer_feedback as cf')
            ->join('appointments as a', 'cf.appointment_id', '=', 'a.id')
            ->where('a.salon_id', $this->salonId)
            ->whereNotNull('a.staff_id')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('a.appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('a.appointment_date', '<=', $this->dateTo);
            });

        // Apply personnel filter
        if (!empty($filters['personnel_ids']) && !in_array(0, $filters['personnel_ids'])) {
            $bestBySatisfactionQuery->whereIn('a.staff_id', $filters['personnel_ids']);
        }

        // Apply service filter
        if (!empty($filters['service_ids']) && !in_array(0, $filters['service_ids'])) {
            $bestBySatisfactionQuery->whereIn('cf.service_id', $filters['service_ids']);
        }

        $bestBySatisfactionRow = $bestBySatisfactionQuery
            ->select('a.staff_id', DB::raw('AVG(cf.rating) as avg_rating'))
            ->groupBy('a.staff_id')
            ->orderByDesc('avg_rating')
            ->first();

        $bestBySatisfactionStaff = $bestBySatisfactionRow ? Staff::find($bestBySatisfactionRow->staff_id) : null;
        $bestBySatisfaction = $bestBySatisfactionRow ? (object)[
            'staff' => $bestBySatisfactionStaff,
            'avg_rating' => $bestBySatisfactionRow->avg_rating,
        ] : null;

        // Rehire rate - customers who returned to same staff
        $rehireRate = $this->calculateRehireRate();

        return [
            'total_staff' => $totalStaff,
            'total_completed_appointments' => $totalCompleted,
            'rehire_rate' => round($rehireRate, 1),
            'top_personnel_by_income' => [
                'name' => $topByIncome->staff->full_name ?? 'نامشخص',
                'amount' => (int) ($topByIncome->total_income ?? 0),
            ],
            'total_commission' => round($totalCommission, 2),
            'most_customers_personnel' => [
                'name' => $mostCustomers->staff->full_name ?? 'نامشخص',
                'count' => $mostCustomers->unique_customers ?? 0,
            ],
            'best_personnel_by_satisfaction' => [
                'name' => $bestBySatisfaction->staff->full_name ?? 'نامشخص',
                'rating' => round($bestBySatisfaction->avg_rating ?? 0, 1),
            ],
        ];
    }

    /**
     * Calculate rehire rate.
     */
    protected function calculateRehireRate()
    {
        $staffWithReturningCustomers = DB::table('appointments')
            ->select('staff_id', 'customer_id', DB::raw('COUNT(*) as visit_count'))
            ->where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointment_date', '<=', $this->dateTo);
            })
            ->groupBy('staff_id', 'customer_id')
            ->having('visit_count', '>', 1)
            ->get();

        $totalUniqueCustomers = DB::table('appointments')
            ->where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointment_date', '<=', $this->dateTo);
            })
            ->distinct('customer_id')
            ->count('customer_id');

        if ($totalUniqueCustomers == 0) {
            return 0;
        }

        return ($staffWithReturningCustomers->count() / $totalUniqueCustomers) * 100;
    }

    /**
     * Generate charts data.
     */
    protected function generateCharts(array $filters = [])
    {
        // Completed appointments by staff
        $completedByStaff = Appointment::where('salon_id', $this->salonId)
            ->where('status', 'completed')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointment_date', '<=', $this->dateTo);
            })
            ->select('staff_id', DB::raw('COUNT(*) as count'))
            ->groupBy('staff_id')
            ->with('staff')
            ->get();

        $staffNames = [];
        $counts = [];

        foreach ($completedByStaff as $item) {
            $staffNames[] = $item->staff->full_name ?? 'نامشخص';
            $counts[] = $item->count;
        }

        return [
            'completed_by_staff' => [
                'labels' => $staffNames,
                'data' => $counts,
            ],
        ];
    }

    /**
     * Generate sections.
     */
    protected function generateSections(array $filters = [])
    {
        return [
            'income_by_staff' => $this->getIncomeByStaff(),
            'satisfaction_by_staff' => $this->getSatisfactionByStaff(),
            'completion_rate_by_staff' => $this->getCompletionRateByStaff(),
            'returning_customers' => $this->getReturningCustomers(),
        ];
    }

    /**
     * Get income by staff.
     */
    protected function getIncomeByStaff()
    {
        // Income = sum of total_price for completed appointments per staff
        $rows = Appointment::where('salon_id', $this->salonId)
            ->where('status', 'completed')
            ->whereNotNull('staff_id')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointment_date', '<=', $this->dateTo);
            })
            ->select('staff_id', DB::raw('SUM(total_price) as total_income'))
            ->groupBy('staff_id')
            ->orderByDesc('total_income')
            ->get();

        return $rows->map(function ($item) {
            $staff = Staff::find($item->staff_id);
            return [
                'staff_name' => $staff->full_name ?? 'نامشخص',
                'total_income' => (int) $item->total_income,
            ];
        });
    }

    /**
     * Get satisfaction by staff.
     */
    protected function getSatisfactionByStaff()
    {
        // customer_feedback.staff_id is not populated; use appointments JOIN instead
        $rows = DB::table('customer_feedback as cf')
            ->join('appointments as a', 'cf.appointment_id', '=', 'a.id')
            ->where('a.salon_id', $this->salonId)
            ->whereNotNull('a.staff_id')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('a.appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('a.appointment_date', '<=', $this->dateTo);
            })
            ->select('a.staff_id', DB::raw('AVG(cf.rating) as avg_rating'), DB::raw('COUNT(*) as total_ratings'))
            ->groupBy('a.staff_id')
            ->orderByDesc('avg_rating')
            ->get();

        return $rows->map(function ($item) {
            $staff = Staff::find($item->staff_id);
            return [
                'staff_name'    => $staff->full_name ?? 'نامشخص',
                'avg_rating'    => round($item->avg_rating, 1),
                'total_ratings' => $item->total_ratings,
            ];
        });
    }

    /**
     * Get completion rate by staff.
     */
    protected function getCompletionRateByStaff()
    {
        return Appointment::where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointment_date', '<=', $this->dateTo);
            })
            ->select(
                'staff_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed')
            )
            ->groupBy('staff_id')
            ->with('staff')
            ->get()
            ->map(function ($item) {
                $completionRate = $item->total > 0 ? ($item->completed / $item->total) * 100 : 0;
                return [
                    'staff_name' => $item->staff->full_name ?? 'نامشخص',
                    'total' => $item->total,
                    'completed' => $item->completed,
                    'completion_rate' => round($completionRate, 1),
                ];
            });
    }

    /**
     * Get returning customers by staff.
     */
    protected function getReturningCustomers()
    {
        return DB::table('appointments')
            ->select('staff_id', DB::raw('COUNT(DISTINCT customer_id) as unique_customers'), DB::raw('COUNT(*) as total_visits'))
            ->where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointment_date', '<=', $this->dateTo);
            })
            ->groupBy('staff_id')
            ->orderByDesc('unique_customers')
            ->get()
            ->map(function ($item) {
                $staff = Staff::find($item->staff_id);
                return [
                    'staff_name' => $staff->full_name ?? 'نامشخص',
                    'unique_customers' => $item->unique_customers,
                    'total_visits' => $item->total_visits,
                ];
            });
    }
    
    /**
     * Build filters summary for display (override).
     */
    protected function buildFiltersSummary($filters)
    {
        $summary = parent::buildFiltersSummary($filters);

        // Add personnel filter
        if (!empty($filters['personnel_ids']) && !in_array(0, $filters['personnel_ids'])) {
            $personnelNames = \App\Models\Staff::whereIn('id', $filters['personnel_ids'])
                ->pluck('full_name')
                ->implode('، ');
            $summary[] = ['label' => 'پرسنل', 'value' => $personnelNames ?: 'نامشخص'];
        } elseif (isset($filters['personnel_ids']) && in_array(0, $filters['personnel_ids'])) {
            $summary[] = ['label' => 'پرسنل', 'value' => 'همه موارد'];
        }

        // Add service filter
        if (!empty($filters['service_ids']) && !in_array(0, $filters['service_ids'])) {
            $serviceNames = \App\Models\Service::whereIn('id', $filters['service_ids'])
                ->pluck('name')
                ->implode('، ');
            $summary[] = ['label' => 'خدمات', 'value' => $serviceNames ?: 'نامشخص'];
        } elseif (isset($filters['service_ids']) && in_array(0, $filters['service_ids'])) {
            $summary[] = ['label' => 'خدمات', 'value' => 'همه موارد'];
        }

        return $summary;
    }
}
