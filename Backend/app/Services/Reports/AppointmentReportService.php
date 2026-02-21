<?php

namespace App\Services\Reports;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AppointmentReportService extends BaseReportService
{
    protected $filters = [];

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
        
        // Build base query
        $baseQuery = Appointment::where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointment_date', '<=', $this->dateTo);
            })
            ->when($this->timeFrom, function ($q) {
                $q->whereTime('start_time', '>=', $this->timeFrom);
            })
            ->when($this->timeTo, function ($q) {
                $q->whereTime('start_time', '<=', $this->timeTo);
            });

        // Apply status filter
        if (!empty($filters['status']) && !in_array('all', $filters['status'])) {
            $baseQuery->whereIn('status', $filters['status']);
        }

        // Apply personnel filter
        if (!empty($filters['personnel_ids']) && !in_array(0, $filters['personnel_ids'])) {
            $baseQuery->whereIn('staff_id', $filters['personnel_ids']);
        }

        // Apply customer filter
        if (!empty($filters['customer_ids']) && !in_array(0, $filters['customer_ids'])) {
            $baseQuery->whereIn('customer_id', $filters['customer_ids']);
        }

        // Apply service filter
        if (!empty($filters['service_ids']) && !in_array(0, $filters['service_ids'])) {
            $baseQuery->whereHas('services', function ($q) use ($filters) {
                $q->whereIn('service_id', $filters['service_ids']);
            });
        }

        // Total appointments
        $totalAppointments = (clone $baseQuery)->count();

        // Completed appointments
        $completedAppointments = (clone $baseQuery)->where('status', 'completed')->count();

        // Cancelled appointments
        $cancelledAppointments = (clone $baseQuery)->whereIn('status', ['canceled', 'cancelled'])->count();

        // Pending appointments
        $pendingAppointments = (clone $baseQuery)->where('status', 'pending')->count();

        // Confirmed appointments
        $confirmedAppointments = (clone $baseQuery)->where('status', 'confirmed')->count();

        // No-show appointments
        $noShowAppointments = (clone $baseQuery)->where('status', 'no_show')->count();

        // Completion rate
        $completionRate = $totalAppointments > 0 ? ($completedAppointments / $totalAppointments) * 100 : 0;

        // Cancellation rate
        $cancellationRate = $totalAppointments > 0 ? ($cancelledAppointments / $totalAppointments) * 100 : 0;

        // Average appointments per day
        if ($this->dateFrom && $this->dateTo) {
            $daysDiff = Carbon::parse($this->dateFrom)->diffInDays(Carbon::parse($this->dateTo)) + 1;
            $avgPerDay = $daysDiff > 0 ? $totalAppointments / $daysDiff : 0;
        } else {
            $avgPerDay = 0;
        }

        // Peak hour (most appointments)
        $peakHour = (clone $baseQuery)
            ->select(DB::raw('HOUR(start_time) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderByDesc('count')
            ->first();

        // Off-peak hour (least appointments)
        $offPeakHour = (clone $baseQuery)
            ->select(DB::raw('HOUR(start_time) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('count', 'asc')
            ->first();

        // Most requested service
        $mostRequestedService = DB::table('appointment_service')
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

        // Most active staff
        $mostActiveStaff = (clone $baseQuery)
            ->select('staff_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('staff_id')
            ->groupBy('staff_id')
            ->orderByDesc('count')
            ->with('staff:id,full_name')
            ->first();

        // Total revenue
        $totalRevenue = (clone $baseQuery)
            ->where('status', 'completed')
            ->sum('total_price');

        // Average revenue per appointment
        $avgRevenue = $completedAppointments > 0 ? $totalRevenue / $completedAppointments : 0;

        // Unique customers
        $uniqueCustomers = (clone $baseQuery)->distinct('customer_id')->count('customer_id');

        return [
            'total_appointments' => $totalAppointments,
            'completed_appointments' => $completedAppointments,
            'cancelled_appointments' => $cancelledAppointments,
            'pending_appointments' => $pendingAppointments,
            'confirmed_appointments' => $confirmedAppointments,
            'no_show_appointments' => $noShowAppointments,
            'completion_rate' => round($completionRate, 1),
            'cancellation_rate' => round($cancellationRate, 1),
            'average_per_day' => round($avgPerDay, 1),
            'peak_hour' => $peakHour ? $peakHour->hour . ':00' : 'نامشخص',
            'off_peak_hour' => $offPeakHour ? $offPeakHour->hour . ':00' : 'نامشخص',
            'most_requested_service' => $mostRequestedService ? $mostRequestedService->name : 'نامشخص',
            'most_active_staff' => $mostActiveStaff && $mostActiveStaff->staff ? 
                $mostActiveStaff->staff->full_name : 'نامشخص',
            'total_revenue' => round($totalRevenue, 2),
            'average_revenue_per_appointment' => round($avgRevenue, 2),
            'unique_customers' => $uniqueCustomers,
        ];
    }

    /**
     * Generate charts data.
     */
    protected function generateCharts(array $filters = [])
    {
        $grouping = $this->getChartGrouping($filters['period'] ?? null);
        
        // Appointments by time period
        $appointmentsByPeriod = Appointment::where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointment_date', '<=', $this->dateTo);
            })
            ->selectRaw(str_replace('{{column}}', 'appointment_date', $grouping['sql']) . ', COUNT(*) as count')
            ->groupBy('group_key')
            ->orderBy('group_key')
            ->get();

        $labels = $grouping['labels'];
        $data = array_fill(0, count($labels), 0);

        foreach ($appointmentsByPeriod as $item) {
            if ($grouping['type'] === 'weekday') {
                $mapping = [7 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6];
                $index = $mapping[$item->group_key] ?? 0;
                $data[$index] = $item->count;
            } elseif ($grouping['type'] === 'day') {
                $index = $item->group_key - 1;
                if ($index >= 0 && $index < count($data)) {
                    $data[$index] = $item->count;
                }
            } elseif ($grouping['type'] === 'month') {
                // For monthly grouping, we need to map the date to Persian month
                try {
                    $date = Carbon::parse($item->group_key . '-01');
                    $verta = new \Hekmatinasser\Verta\Verta($date);
                    $monthIndex = $verta->month - 1;
                    if ($monthIndex >= 0 && $monthIndex < 12) {
                        $data[$monthIndex] += $item->count;
                    }
                } catch (\Exception $e) {
                    // Skip invalid dates
                }
            }
        }

        // Appointments by status
        $appointmentsByStatus = Appointment::where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointment_date', '<=', $this->dateTo);
            })
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        $statusLabels = [];
        $statusData = [];
        $statusMapping = [
            'pending' => 'در انتظار',
            'confirmed' => 'تایید شده',
            'completed' => 'تکمیل شده',
            'canceled' => 'لغو شده',
            'cancelled' => 'لغو شده',
            'no_show' => 'عدم حضور',
        ];

        foreach ($appointmentsByStatus as $item) {
            $statusLabels[] = $statusMapping[$item->status] ?? $item->status;
            $statusData[] = $item->count;
        }

        // Appointments by hour
        $appointmentsByHour = Appointment::where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointment_date', '<=', $this->dateTo);
            })
            ->select(DB::raw('HOUR(start_time) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $hourLabels = [];
        $hourData = [];
        
        foreach ($appointmentsByHour as $item) {
            $hourLabels[] = $item->hour . ':00';
            $hourData[] = $item->count;
        }

        return [
            'appointments_by_period' => [
                'labels' => $labels,
                'data' => $data,
            ],
            'appointments_by_status' => [
                'labels' => $statusLabels,
                'data' => $statusData,
            ],
            'appointments_by_hour' => [
                'labels' => $hourLabels,
                'data' => $hourData,
            ],
        ];
    }

    /**
     * Generate sections.
     */
    protected function generateSections(array $filters = [])
    {
        return [
            'appointments_by_service' => $this->getAppointmentsByService(),
            'appointments_by_staff' => $this->getAppointmentsByStaff(),
            'cancellation_analysis' => $this->getCancellationAnalysis(),
            'revenue_analysis' => $this->getRevenueAnalysis(),
        ];
    }

    /**
     * Get appointments grouped by service.
     */
    protected function getAppointmentsByService()
    {
        return DB::table('appointment_service')
            ->join('appointments', 'appointment_service.appointment_id', '=', 'appointments.id')
            ->join('services', 'appointment_service.service_id', '=', 'services.id')
            ->where('appointments.salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointments.appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointments.appointment_date', '>=', $this->dateTo);
            })
            ->select('services.name', DB::raw('COUNT(*) as total_appointments'))
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('total_appointments')
            ->get()
            ->map(function ($item) {
                return [
                    'service_name' => $item->name,
                    'total_appointments' => $item->total_appointments,
                ];
            });
    }

    /**
     * Get appointments grouped by staff.
     */
    protected function getAppointmentsByStaff()
    {
        return Appointment::where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointment_date', '<=', $this->dateTo);
            })
            ->whereNotNull('staff_id')
            ->select('staff_id', DB::raw('COUNT(*) as total_appointments'))
            ->groupBy('staff_id')
            ->orderByDesc('total_appointments')
            ->with('staff:id,full_name')
            ->get()
            ->map(function ($item) {
                return [
                    'staff_name' => $item->staff ? $item->staff->full_name : 'نامشخص',
                    'total_appointments' => $item->total_appointments,
                ];
            });
    }

    /**
     * Get cancellation analysis.
     */
    protected function getCancellationAnalysis()
    {
        // Cancellations by weekday
        $byWeekday = Appointment::where('salon_id', $this->salonId)
            ->whereIn('status', ['canceled', 'cancelled'])
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointment_date', '<=', $this->dateTo);
            })
            ->select(DB::raw('DAYOFWEEK(appointment_date) as day'), DB::raw('COUNT(*) as count'))
            ->groupBy('day')
            ->orderByDesc('count')
            ->get();

        $dayMapping = [
            7 => 'شنبه',
            1 => 'یکشنبه',
            2 => 'دوشنبه',
            3 => 'سه‌شنبه',
            4 => 'چهارشنبه',
            5 => 'پنج‌شنبه',
            6 => 'جمعه',
        ];

        $byWeekdayFormatted = $byWeekday->map(function ($item) use ($dayMapping) {
            return [
                'day' => $dayMapping[$item->day] ?? 'نامشخص',
                'count' => $item->count,
            ];
        });

        // Cancellations by hour
        $byHour = Appointment::where('salon_id', $this->salonId)
            ->whereIn('status', ['canceled', 'cancelled'])
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointment_date', '<=', $this->dateTo);
            })
            ->select(DB::raw('HOUR(start_time) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) {
                return [
                    'hour' => $item->hour . ':00',
                    'count' => $item->count,
                ];
            });

        return [
            'by_weekday' => $byWeekdayFormatted,
            'by_hour' => $byHour,
        ];
    }

    /**
     * Get revenue analysis.
     */
    protected function getRevenueAnalysis()
    {
        $revenueByService = DB::table('appointment_service')
            ->join('appointments', 'appointment_service.appointment_id', '=', 'appointments.id')
            ->join('services', 'appointment_service.service_id', '=', 'services.id')
            ->where('appointments.salon_id', $this->salonId)
            ->where('appointments.status', 'completed')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointments.appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointments.appointment_date', '<=', $this->dateTo);
            })
            ->select('services.name', DB::raw('SUM(appointments.total_price) as total_revenue'))
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(function ($item) {
                return [
                    'service_name' => $item->name,
                    'total_revenue' => round($item->total_revenue, 2),
                ];
            });

        return [
            'by_service' => $revenueByService,
        ];
    }

    /**
     * Build filters summary for display (override).
     */
    protected function buildFiltersSummary($filters)
    {
        $summary = parent::buildFiltersSummary($filters);

        // Add status filter
        if (!empty($filters['status']) && !in_array('all', $filters['status'])) {
            $statusMapping = [
                'pending' => 'در انتظار',
                'confirmed' => 'تایید شده',
                'completed' => 'تکمیل شده',
                'canceled' => 'لغو شده',
                'cancelled' => 'لغو شده',
                'no_show' => 'عدم حضور',
            ];
            $statusNames = array_map(function($status) use ($statusMapping) {
                return $statusMapping[$status] ?? $status;
            }, $filters['status']);
            $summary[] = ['label' => 'وضعیت', 'value' => implode('، ', $statusNames)];
        } elseif (isset($filters['status']) && in_array('all', $filters['status'])) {
            $summary[] = ['label' => 'وضعیت', 'value' => 'همه موارد'];
        }

        // Add personnel filter
        if (!empty($filters['personnel_ids']) && !in_array(0, $filters['personnel_ids'])) {
            $personnelNames = Staff::whereIn('id', $filters['personnel_ids'])
                ->pluck('full_name')
                ->implode('، ');
            $summary[] = ['label' => 'پرسنل', 'value' => $personnelNames ?: 'نامشخص'];
        } elseif (isset($filters['personnel_ids']) && in_array(0, $filters['personnel_ids'])) {
            $summary[] = ['label' => 'پرسنل', 'value' => 'همه موارد'];
        }

        // Add customer filter
        if (!empty($filters['customer_ids']) && !in_array(0, $filters['customer_ids'])) {
            $customerNames = Customer::whereIn('id', $filters['customer_ids'])
                ->pluck('full_name')
                ->implode('، ');
            $summary[] = ['label' => 'مشتریان', 'value' => $customerNames ?: 'نامشخص'];
        } elseif (isset($filters['customer_ids']) && in_array(0, $filters['customer_ids'])) {
            $summary[] = ['label' => 'مشتریان', 'value' => 'همه موارد'];
        }

        // Add service filter
        if (!empty($filters['service_ids']) && !in_array(0, $filters['service_ids'])) {
            $serviceNames = Service::whereIn('id', $filters['service_ids'])
                ->pluck('name')
                ->implode('، ');
            $summary[] = ['label' => 'خدمات', 'value' => $serviceNames ?: 'نامشخص'];
        } elseif (isset($filters['service_ids']) && in_array(0, $filters['service_ids'])) {
            $summary[] = ['label' => 'خدمات', 'value' => 'همه موارد'];
        }

        return $summary;
    }
}
