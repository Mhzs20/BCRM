<?php

namespace App\Services\Reports;

use App\Models\Appointment;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReservationReportService extends BaseReportService
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
        $baseQuery = Appointment::where('salon_id', $this->salonId);

        if ($this->dateFrom || $this->dateTo) {
            $baseQuery = $this->applyDateTimeFilters(clone $baseQuery, 'appointment_date', 'start_time');
        }

        // Apply service filter
        if (!empty($filters['service_ids']) && !in_array(0, $filters['service_ids'])) {
            $baseQuery->whereHas('services', function ($q) use ($filters) {
                $q->whereIn('services.id', $filters['service_ids']);
            });
        }

        // Apply personnel filter
        if (!empty($filters['personnel_ids']) && !in_array(0, $filters['personnel_ids'])) {
            $baseQuery->whereIn('staff_id', $filters['personnel_ids']);
        }

        $totalAppointments = (clone $baseQuery)->count();
        $canceledAppointments = (clone $baseQuery)->whereIn('status', ['canceled', 'cancelled'])->count();
        $completedAppointments = (clone $baseQuery)->where('status', 'completed')->count();

        // Average appointments per day
        $daysCount = $this->dateFrom && $this->dateTo 
            ? $this->dateFrom->diffInDays($this->dateTo) + 1 
            : 30;
        $avgPerDay = $totalAppointments / max($daysCount, 1);

        // Peak time slot
        $peakTimeSlot = Appointment::where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointment_date', '<=', $this->dateTo);
            })
            ->select('start_time', DB::raw('COUNT(*) as count'))
            ->groupBy('start_time')
            ->orderByDesc('count')
            ->first();

        // Off-peak time slot
        $offPeakTimeSlot = Appointment::where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointment_date', '<=', $this->dateTo);
            })
            ->select('start_time', DB::raw('COUNT(*) as count'))
            ->groupBy('start_time')
            ->orderBy('count', 'asc')
            ->first();

        // Top service
        $topService = DB::table('appointment_service')
            ->join('appointments', 'appointment_service.appointment_id', '=', 'appointments.id')
            ->join('services', 'appointment_service.service_id', '=', 'services.id')
            ->where('appointments.salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointments.appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointments.appointment_date', '<=', $this->dateTo);
            })
            ->select('services.name', DB::raw('COUNT(*) as count'))
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('count')
            ->first();

        return [
            'total_appointments' => $totalAppointments,
            'canceled_appointments' => $canceledAppointments,
            'completed_appointments' => $completedAppointments,
            'cancellation_rate' => $totalAppointments > 0 ? round(($canceledAppointments / $totalAppointments) * 100, 1) : 0,
            'avg_appointments_per_day' => round($avgPerDay, 1),
            'peak_time_slot' => $peakTimeSlot->start_time ?? 'نامشخص',
            'off_peak_time_slot' => $offPeakTimeSlot->start_time ?? 'نامشخص',
            'top_service' => $topService->name ?? 'نامشخص',
        ];
    }

    /**
     * Generate charts data.
     */
    protected function generateCharts(array $filters = [])
    {
        // Appointments by weekday
        $appointmentsByWeekday = Appointment::where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointment_date', '<=', $this->dateTo);
            })
            ->select(
                DB::raw('DAYOFWEEK(appointment_date) as day_of_week'),
                DB::raw('COUNT(CASE WHEN status NOT IN ("canceled", "cancelled") THEN 1 END) as booked'),
                DB::raw('COUNT(CASE WHEN status IN ("canceled", "cancelled") THEN 1 END) as canceled')
            )
            ->groupBy('day_of_week')
            ->get();

        $weekdays = [];
        $bookedData = [];
        $canceledData = [];

        foreach ($appointmentsByWeekday as $item) {
            $dayName = $this->getWeekdayName($item->day_of_week - 1);
            $weekdays[] = $dayName;
            $bookedData[] = $item->booked;
            $canceledData[] = $item->canceled;
        }

        return [
            'appointments_by_weekday' => [
                'labels' => $weekdays,
                'datasets' => [
                    [
                        'label' => 'اخذ شده',
                        'data' => $bookedData,
                    ],
                    [
                        'label' => 'لغو شده',
                        'data' => $canceledData,
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
            'service_popularity' => $this->getServicePopularity(),
            'staff_performance' => $this->getStaffPerformance(),
            'time_slot_distribution' => $this->getTimeSlotDistribution(),
            'status_breakdown' => $this->getStatusBreakdown(),
        ];
    }

    /**
     * Get service popularity.
     */
    protected function getServicePopularity()
    {
        return DB::table('appointment_service')
            ->join('appointments', 'appointment_service.appointment_id', '=', 'appointments.id')
            ->join('services', 'appointment_service.service_id', '=', 'services.id')
            ->where('appointments.salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointments.appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointments.appointment_date', '<=', $this->dateTo);
            })
            ->select('services.name', DB::raw('COUNT(*) as count'))
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'service' => $item->name,
                    'count' => $item->count,
                ];
            });
    }

    /**
     * Get staff performance.
     */
    protected function getStaffPerformance()
    {
        return Appointment::where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointment_date', '<=', $this->dateTo);
            })
            ->select('staff_id', DB::raw('COUNT(*) as total'), DB::raw('COUNT(CASE WHEN status = "completed" THEN 1 END) as completed'))
            ->groupBy('staff_id')
            ->with('staff')
            ->get()
            ->map(function ($item) {
                return [
                    'staff_name' => $item->staff->full_name ?? 'نامشخص',
                    'total' => $item->total,
                    'completed' => $item->completed,
                ];
            });
    }

    /**
     * Get time slot distribution.
     */
    protected function getTimeSlotDistribution()
    {
        return Appointment::where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointment_date', '<=', $this->dateTo);
            })
            ->select('start_time', DB::raw('COUNT(*) as count'))
            ->groupBy('start_time')
            ->orderBy('start_time')
            ->get()
            ->map(function ($item) {
                return [
                    'time' => $item->start_time,
                    'count' => $item->count,
                ];
            });
    }

    /**
     * Get status breakdown.
     */
    protected function getStatusBreakdown()
    {
        return Appointment::where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointment_date', '<=', $this->dateTo);
            })
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => $item->status,
                    'count' => $item->count,
                ];
            });
    }
}
