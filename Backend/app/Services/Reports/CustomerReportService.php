<?php

namespace App\Services\Reports;

use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CustomerReportService extends BaseReportService
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
        $this->dateFrom = $filters['date_from'] ?? null;
        $this->dateTo = $filters['date_to'] ?? null;
        $this->timeFrom = $filters['time_from'] ?? null;
        $this->timeTo = $filters['time_to'] ?? null;
    }

    /**
     * Calculate KPIs.
     */
    protected function calculateKPIs(array $filters = [])
    {
        $baseQuery = Customer::where('salon_id', $this->salonId);
        
        if ($this->dateFrom || $this->dateTo) {
            $baseQuery = $this->applyDateTimeFilters($baseQuery, 'created_at');
        }

        $totalCustomers = $baseQuery->count();
        $allCustomers = Customer::where('salon_id', $this->salonId)->get();

        // Active/Inactive customers (customers with appointments in the last 3 months are active)
        $threeMonthsAgo = Carbon::now()->subMonths(3);
        $activeCustomerIds = Appointment::where('salon_id', $this->salonId)
            ->where('appointment_date', '>=', $threeMonthsAgo)
            ->distinct('customer_id')
            ->pluck('customer_id');

        $activeCustomers = Customer::where('salon_id', $this->salonId)
            ->whereIn('id', $activeCustomerIds)
            ->count();

        $inactiveCustomers = Customer::where('salon_id', $this->salonId)->count() - $activeCustomers;

        // Average age
        $avgAge = $allCustomers->filter(function ($customer) {
            return $customer->birth_date;
        })->map(function ($customer) {
            return Carbon::parse($customer->birth_date)->age;
        })->average();

        // Repeat visits (customers with more than 1 appointment)
        $repeatCustomers = Customer::where('salon_id', $this->salonId)
            ->has('appointments', '>', 1)
            ->count();

        // Top customer by revenue
        $topCustomerByRevenue = Payment::where('salon_id', $this->salonId)
            ->select('customer_id', DB::raw('SUM(amount) as total_paid'))
            ->groupBy('customer_id')
            ->orderByDesc('total_paid')
            ->with('customer')
            ->first();

        // Top job/profession
        $topJob = Customer::where('salon_id', $this->salonId)
            ->whereNotNull('profession_id')
            ->select('profession_id', DB::raw('COUNT(*) as count'))
            ->groupBy('profession_id')
            ->orderByDesc('count')
            ->with('profession')
            ->first();

        // Top acquisition source
        $topAcquisitionSource = Customer::where('salon_id', $this->salonId)
            ->whereNotNull('how_introduced_id')
            ->select('how_introduced_id', DB::raw('COUNT(*) as count'))
            ->groupBy('how_introduced_id')
            ->orderByDesc('count')
            ->with('howIntroduced')
            ->first();

        return [
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'inactive_customers' => $inactiveCustomers,
            'average_age' => round($avgAge ?? 0, 1),
            'repeat_visits' => $repeatCustomers,
            'top_customer_by_revenue' => [
                'name' => $topCustomerByRevenue->customer->name ?? 'نامشخص',
                'amount' => $topCustomerByRevenue->total_paid ?? 0,
            ],
            'top_job' => $topJob->profession->name ?? 'نامشخص',
            'top_acquisition_source' => $topAcquisitionSource->howIntroduced->name ?? 'نامشخص',
            'highest_payer' => [
                'name' => $topCustomerByRevenue->customer->name ?? 'نامشخص',
                'amount' => $topCustomerByRevenue->total_paid ?? 0,
            ],
        ];
    }

    /**
     * Generate charts data.
     */
    protected function generateCharts(array $filters = [])
    {
        // Get appropriate grouping based on period
        $grouping = $this->getChartGrouping($filters['period'] ?? null);
        
        $sqlGroup = str_replace('{{column}}', 'created_at', $grouping['sql']);
        
        $newCustomers = Customer::where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('created_at', '<=', $this->dateTo);
            })
            ->select(DB::raw($sqlGroup), DB::raw('COUNT(*) as count'))
            ->groupBy('group_key')
            ->get()
            ->keyBy('group_key');

        // Prepare full dataset with all labels
        $labels = $grouping['labels'];
        $data = [];
        
        if ($grouping['type'] === 'weekday') {
            // For weekday: map 1-7 to labels
            foreach ([7, 1, 2, 3, 4, 5, 6] as $idx => $key) {
                $data[] = $newCustomers->get($key)->count ?? 0;
            }
        } elseif ($grouping['type'] === 'day') {
            // For day: 1-31
            foreach (range(1, 31) as $day) {
                $data[] = $newCustomers->get($day)->count ?? 0;
            }
        } else {
            // For month: group by year-month and convert to Persian month names
            $monthlyData = [];
            foreach ($newCustomers as $item) {
                $label = $grouping['formatter']($item->group_key);
                $monthlyData[$label] = $item->count;
            }
            // Return only months that have data
            $labels = array_keys($monthlyData);
            $data = array_values($monthlyData);
        }

        return [
            'new_customers_by_period' => [
                'labels' => $labels,
                'data' => $data,
            ],
        ];
    }

    /**
     * Generate sections (accordion data).
     */
    protected function generateSections(array $filters = [])
    {
        return [
            'age_distribution' => $this->getAgeDistribution(),
            'payment_distribution' => $this->getPaymentDistribution(),
            'profession_distribution' => $this->getProfessionDistribution(),
            'group_distribution' => $this->getGroupDistribution(),
            'acquisition_source_distribution' => $this->getAcquisitionSourceDistribution(),
            'repeat_visits_detail' => $this->getRepeatVisitsDetail(),
        ];
    }

    /**
     * Get age distribution.
     */
    protected function getAgeDistribution()
    {
        $customers = Customer::where('salon_id', $this->salonId)
            ->whereNotNull('birth_date')
            ->get();

        $ageRanges = [
            '18-25' => 0,
            '26-35' => 0,
            '36-45' => 0,
            '46-55' => 0,
            '56+' => 0,
        ];

        foreach ($customers as $customer) {
            $age = Carbon::parse($customer->birth_date)->age;
            if ($age >= 18 && $age <= 25) {
                $ageRanges['18-25']++;
            } elseif ($age >= 26 && $age <= 35) {
                $ageRanges['26-35']++;
            } elseif ($age >= 36 && $age <= 45) {
                $ageRanges['36-45']++;
            } elseif ($age >= 46 && $age <= 55) {
                $ageRanges['46-55']++;
            } else {
                $ageRanges['56+']++;
            }
        }

        return $ageRanges;
    }

    /**
     * Get payment distribution by customer.
     */
    protected function getPaymentDistribution()
    {
        return Payment::where('salon_id', $this->salonId)
            ->select('customer_id', DB::raw('SUM(amount) as total_paid'))
            ->groupBy('customer_id')
            ->orderByDesc('total_paid')
            ->with('customer')
            ->limit(10)
            ->get()
            ->map(function ($payment) {
                return [
                    'customer_name' => $payment->customer->name ?? 'نامشخص',
                    'total_paid' => $payment->total_paid,
                ];
            });
    }

    /**
     * Get profession distribution.
     */
    protected function getProfessionDistribution()
    {
        return Customer::where('salon_id', $this->salonId)
            ->whereNotNull('profession_id')
            ->select('profession_id', DB::raw('COUNT(*) as count'))
            ->groupBy('profession_id')
            ->orderByDesc('count')
            ->with('profession')
            ->get()
            ->map(function ($item) {
                return [
                    'profession' => $item->profession->name ?? 'نامشخص',
                    'count' => $item->count,
                ];
            });
    }

    /**
     * Get group distribution.
     */
    protected function getGroupDistribution()
    {
        return DB::table('customer_customer_group')
            ->join('customers', 'customer_customer_group.customer_id', '=', 'customers.id')
            ->join('customer_groups', 'customer_customer_group.customer_group_id', '=', 'customer_groups.id')
            ->where('customers.salon_id', $this->salonId)
            ->select('customer_groups.name', DB::raw('COUNT(*) as count'))
            ->groupBy('customer_groups.id', 'customer_groups.name')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) {
                return [
                    'group' => $item->name,
                    'count' => $item->count,
                ];
            });
    }

    /**
     * Get acquisition source distribution.
     */
    protected function getAcquisitionSourceDistribution()
    {
        return Customer::where('salon_id', $this->salonId)
            ->whereNotNull('how_introduced_id')
            ->select('how_introduced_id', DB::raw('COUNT(*) as count'))
            ->groupBy('how_introduced_id')
            ->orderByDesc('count')
            ->with('howIntroduced')
            ->get()
            ->map(function ($item) {
                return [
                    'source' => $item->howIntroduced->name ?? 'نامشخص',
                    'count' => $item->count,
                ];
            });
    }

    /**
     * Get repeat visits detail.
     */
    protected function getRepeatVisitsDetail()
    {
        return Customer::where('salon_id', $this->salonId)
            ->withCount('appointments')
            ->having('appointments_count', '>', 1)
            ->orderByDesc('appointments_count')
            ->limit(10)
            ->get()
            ->map(function ($customer) {
                return [
                    'customer_name' => $customer->name,
                    'visit_count' => $customer->appointments_count,
                ];
            });
    }
}
