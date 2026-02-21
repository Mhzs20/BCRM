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
    public function generatePresetReport($salonId, $period = null)
    {
        $this->salonId = $salonId;

        if ($period) {
            $dateRange = $this->getPresetDateRange($period);
            $this->dateFrom = $dateRange['from'];
            $this->dateTo = $dateRange['to'];
        } else {
            // Overall mode: no date filtering
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
        $this->filters = $filters;
    }

    /**
     * Calculate KPIs.
     */
    protected function calculateKPIs(array $filters = [])
    {
        // Build base query with filters
        $customerIds = $this->getFilteredCustomerIds($filters);
        
        $baseQuery = Customer::where('salon_id', $this->salonId);
        
        if (!empty($customerIds)) {
            $baseQuery->whereIn('id', $customerIds);
        }
        
        if ($this->dateFrom || $this->dateTo) {
            $baseQuery = $this->applyDateTimeFilters($baseQuery, 'created_at');
        }

        $totalCustomers = $baseQuery->count();
        
        // Use filtered customers for all calculations
        $allCustomersQuery = Customer::where('salon_id', $this->salonId);
        if (!empty($customerIds)) {
            $allCustomersQuery->whereIn('id', $customerIds);
        }
        $allCustomers = $allCustomersQuery->get();

        // Active/Inactive customers (customers with appointments in the last 3 months are active)
        $threeMonthsAgo = Carbon::now()->subMonths(3);
        $activeCustomerIdsQuery = Appointment::where('salon_id', $this->salonId)
            ->where('appointment_date', '>=', $threeMonthsAgo);
            
        if (!empty($customerIds)) {
            $activeCustomerIdsQuery->whereIn('customer_id', $customerIds);
        }
        
        $activeCustomerIds = $activeCustomerIdsQuery->distinct('customer_id')->pluck('customer_id');

        $activeCustomersQuery = Customer::where('salon_id', $this->salonId)
            ->whereIn('id', $activeCustomerIds);
        if (!empty($customerIds)) {
            $activeCustomersQuery->whereIn('id', $customerIds);
        }
        $activeCustomers = $activeCustomersQuery->count();

        $inactiveCustomersQuery = Customer::where('salon_id', $this->salonId);
        if (!empty($customerIds)) {
            $inactiveCustomersQuery->whereIn('id', $customerIds);
        }
        $inactiveCustomers = $inactiveCustomersQuery->count() - $activeCustomers;

        // Average age
        $avgAge = $allCustomers->filter(function ($customer) {
            return $customer->birth_date;
        })->map(function ($customer) {
            return Carbon::parse($customer->birth_date)->age;
        })->average();

        // Repeat visits (customers with more than 1 appointment)
        $repeatCustomersQuery = Customer::where('salon_id', $this->salonId)
            ->has('appointments', '>', 1);
        if (!empty($customerIds)) {
            $repeatCustomersQuery->whereIn('id', $customerIds);
        }
        $repeatCustomers = $repeatCustomersQuery->count();

        // Top customer by revenue
        $topCustomerByRevenueQuery = Payment::where('salon_id', $this->salonId)
            ->select('customer_id', DB::raw('SUM(amount) as total_paid'))
            ->groupBy('customer_id')
            ->orderByDesc('total_paid')
            ->with('customer');
        if (!empty($customerIds)) {
            $topCustomerByRevenueQuery->whereIn('customer_id', $customerIds);
        }
        $topCustomerByRevenue = $topCustomerByRevenueQuery->first();

        // Top job/profession
        $topJobQuery = Customer::where('salon_id', $this->salonId)
            ->whereNotNull('profession_id')
            ->select('profession_id', DB::raw('COUNT(*) as count'))
            ->groupBy('profession_id')
            ->orderByDesc('count')
            ->with('profession');
        if (!empty($customerIds)) {
            $topJobQuery->whereIn('id', $customerIds);
        }
        $topJob = $topJobQuery->first();

        // Top acquisition source
        $topAcquisitionSourceQuery = Customer::where('salon_id', $this->salonId)
            ->whereNotNull('how_introduced_id')
            ->select('how_introduced_id', DB::raw('COUNT(*) as count'))
            ->groupBy('how_introduced_id')
            ->orderByDesc('count')
            ->with('howIntroduced');
        if (!empty($customerIds)) {
            $topAcquisitionSourceQuery->whereIn('id', $customerIds);
        }
        $topAcquisitionSource = $topAcquisitionSourceQuery->first();

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
     * Get filtered customer IDs based on filters.
     */
    protected function getFilteredCustomerIds(array $filters = [])
    {
        $customerIds = null;

        // Start with date filter on customer creation if specified
        if ($this->dateFrom || $this->dateTo) {
            $dateQuery = Customer::where('salon_id', $this->salonId);
            
            if ($this->dateFrom) {
                $dateQuery->whereDate('created_at', '>=', $this->dateFrom);
            }
            
            if ($this->dateTo) {
                $dateQuery->whereDate('created_at', '<=', $this->dateTo);
            }
            
            $customerIds = $dateQuery->pluck('id')->toArray();
        }

        // Filter by services
        if (!empty($filters['service_ids']) && !in_array(0, $filters['service_ids'])) {
            $serviceCustomerIds = Appointment::where('salon_id', $this->salonId)
                ->whereHas('services', function ($q) use ($filters) {
                    $q->whereIn('service_id', $filters['service_ids']);
                })
                ->distinct()
                ->pluck('customer_id')
                ->toArray();
            
            if ($customerIds === null) {
                $customerIds = $serviceCustomerIds;
            } else {
                $customerIds = array_intersect($customerIds, $serviceCustomerIds);
            }
        }

        // Filter by personnel
        if (!empty($filters['personnel_ids']) && !in_array(0, $filters['personnel_ids'])) {
            $personnelCustomerIds = Appointment::where('salon_id', $this->salonId)
                ->whereIn('staff_id', $filters['personnel_ids'])
                ->distinct()
                ->pluck('customer_id')
                ->toArray();
            
            if ($customerIds === null) {
                $customerIds = $personnelCustomerIds;
            } else {
                $customerIds = array_intersect($customerIds, $personnelCustomerIds);
            }
        }

        // Filter by acquisition source
        if (!empty($filters['acquisition_source_ids']) && !in_array(0, $filters['acquisition_source_ids'])) {
            $acquisitionCustomerIds = Customer::where('salon_id', $this->salonId)
                ->whereIn('how_introduced_id', $filters['acquisition_source_ids'])
                ->pluck('id')
                ->toArray();
            
            if ($customerIds === null) {
                $customerIds = $acquisitionCustomerIds;
            } else {
                $customerIds = array_intersect($customerIds, $acquisitionCustomerIds);
            }
        }

        // Filter by paid amount range
        if (isset($filters['min_paid_amount']) || isset($filters['max_paid_amount'])) {
            $paymentQuery = Payment::where('salon_id', $this->salonId)
                ->select('customer_id', DB::raw('SUM(amount) as total_paid'))
                ->groupBy('customer_id');
            
            if (isset($filters['min_paid_amount'])) {
                $paymentQuery->havingRaw('SUM(amount) >= ?', [$filters['min_paid_amount']]);
            }
            
            if (isset($filters['max_paid_amount'])) {
                $paymentQuery->havingRaw('SUM(amount) <= ?', [$filters['max_paid_amount']]);
            }
            
            $paymentCustomerIds = $paymentQuery->pluck('customer_id')->toArray();
            
            if ($customerIds === null) {
                $customerIds = $paymentCustomerIds;
            } else {
                $customerIds = array_intersect($customerIds, $paymentCustomerIds);
            }
        }

        return $customerIds;
    }

    /**
     * Generate charts data.
     */
    protected function generateCharts(array $filters = [])
    {
        // Get filtered customer IDs
        $customerIds = $this->getFilteredCustomerIds($filters);
        
        // Get appropriate grouping based on period
        $grouping = $this->getChartGrouping($filters['period'] ?? null);
        
        $sqlGroup = str_replace('{{column}}', 'created_at', $grouping['sql']);
        
        $newCustomersQuery = Customer::where('salon_id', $this->salonId);
        
        if (!empty($customerIds)) {
            $newCustomersQuery->whereIn('id', $customerIds);
        }
        
        $newCustomers = $newCustomersQuery
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
            'payment_by_customer' => $this->getPaymentByCustomerChart($customerIds),
            'income_by_staff' => $this->getIncomeByStaffChart($customerIds),
        ];
    }

    /**
     * Chart: top customers by total payments (bar chart).
     */
    protected function getPaymentByCustomerChart($customerIds = null)
    {
        $query = Payment::where('salon_id', $this->salonId)
            ->select('customer_id', DB::raw('SUM(amount) as total_paid'))
            ->groupBy('customer_id')
            ->orderByDesc('total_paid')
            ->with('customer')
            ->limit(10);

        if (!empty($customerIds)) {
            $query->whereIn('customer_id', $customerIds);
        }

        $rows = $query->get();

        return [
            'labels' => $rows->map(fn($r) => $r->customer->name ?? 'نامشخص')->values()->all(),
            'data'   => $rows->map(fn($r) => (int) $r->total_paid)->values()->all(),
        ];
    }

    /**
     * Chart: income per staff for appointments involving filtered customers.
     */
    protected function getIncomeByStaffChart($customerIds = null)
    {
        $query = Appointment::where('salon_id', $this->salonId)
            ->where('status', 'completed')
            ->whereNotNull('staff_id')
            ->when($this->dateFrom, fn($q) => $q->whereDate('appointment_date', '>=', $this->dateFrom))
            ->when($this->dateTo,   fn($q) => $q->whereDate('appointment_date', '<=', $this->dateTo));

        if (!empty($customerIds)) {
            $query->whereIn('customer_id', $customerIds);
        }

        $rows = $query
            ->select('staff_id', DB::raw('SUM(total_price) as total_income'))
            ->groupBy('staff_id')
            ->orderByDesc('total_income')
            ->with('staff')
            ->get();

        return [
            'labels' => $rows->map(fn($r) => $r->staff->full_name ?? 'نامشخص')->values()->all(),
            'data'   => $rows->map(fn($r) => (int) $r->total_income)->values()->all(),
        ];
    }

    /**
     * Generate sections (accordion data).
     */
    protected function generateSections(array $filters = [])
    {
        $customerIds = $this->getFilteredCustomerIds($filters);
        
        return [
            'age_distribution' => $this->getAgeDistribution($customerIds),
            'payment_distribution' => $this->getPaymentDistribution($customerIds),
            'profession_distribution' => $this->getProfessionDistribution($customerIds),
            'group_distribution' => $this->getGroupDistribution($customerIds),
            'acquisition_source_distribution' => $this->getAcquisitionSourceDistribution($customerIds),
            'repeat_visits_detail' => $this->getRepeatVisitsDetail($customerIds),
        ];
    }

    /**
     * Get age distribution.
     */
    protected function getAgeDistribution($customerIds = null)
    {
        $customersQuery = Customer::where('salon_id', $this->salonId)
            ->whereNotNull('birth_date');
            
        if (!empty($customerIds)) {
            $customersQuery->whereIn('id', $customerIds);
        }
        
        $customers = $customersQuery->get();

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
    protected function getPaymentDistribution($customerIds = null)
    {
        $paymentQuery = Payment::where('salon_id', $this->salonId)
            ->select('customer_id', DB::raw('SUM(amount) as total_paid'))
            ->groupBy('customer_id')
            ->orderByDesc('total_paid')
            ->with('customer')
            ->limit(10);
            
        if (!empty($customerIds)) {
            $paymentQuery->whereIn('customer_id', $customerIds);
        }
        
        return $paymentQuery->get()
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
    protected function getProfessionDistribution($customerIds = null)
    {
        $professionQuery = Customer::where('salon_id', $this->salonId)
            ->whereNotNull('profession_id')
            ->select('profession_id', DB::raw('COUNT(*) as count'))
            ->groupBy('profession_id')
            ->orderByDesc('count')
            ->with('profession');
            
        if (!empty($customerIds)) {
            $professionQuery->whereIn('id', $customerIds);
        }
        
        return $professionQuery->get()
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
    protected function getGroupDistribution($customerIds = null)
    {
        $groupQuery = DB::table('customer_customer_group')
            ->join('customers', 'customer_customer_group.customer_id', '=', 'customers.id')
            ->join('customer_groups', 'customer_customer_group.customer_group_id', '=', 'customer_groups.id')
            ->where('customers.salon_id', $this->salonId)
            ->select('customer_groups.name', DB::raw('COUNT(*) as count'))
            ->groupBy('customer_groups.id', 'customer_groups.name')
            ->orderByDesc('count');
            
        if (!empty($customerIds)) {
            $groupQuery->whereIn('customer_customer_group.customer_id', $customerIds);
        }
        
        return $groupQuery->get()
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
    protected function getAcquisitionSourceDistribution($customerIds = null)
    {
        $acquisitionQuery = Customer::where('salon_id', $this->salonId)
            ->whereNotNull('how_introduced_id')
            ->select('how_introduced_id', DB::raw('COUNT(*) as count'))
            ->groupBy('how_introduced_id')
            ->orderByDesc('count')
            ->with('howIntroduced');
            
        if (!empty($customerIds)) {
            $acquisitionQuery->whereIn('id', $customerIds);
        }
        
        return $acquisitionQuery->get()
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
    protected function getRepeatVisitsDetail($customerIds = null)
    {
        $repeatVisitsQuery = Customer::where('salon_id', $this->salonId)
            ->withCount('appointments')
            ->having('appointments_count', '>', 1)
            ->orderByDesc('appointments_count')
            ->limit(10);
            
        if (!empty($customerIds)) {
            $repeatVisitsQuery->whereIn('id', $customerIds);
        }
        
        return $repeatVisitsQuery->get()
            ->map(function ($customer) {
                return [
                    'customer_name' => $customer->name,
                    'visit_count' => $customer->appointments_count,
                ];
            });
    }

    /**
     * Build filters summary for display (override).
     */
    protected function buildFiltersSummary($filters)
    {
        $summary = parent::buildFiltersSummary($filters);

        // Add period filter
        if (!empty($filters['period'])) {
            $periodLabels = [
                'daily' => 'روزانه',
                'weekly' => 'هفتگی',
                'monthly' => 'ماهانه',
                'yearly' => 'سالانه',
            ];
            $summary[] = ['label' => 'بازه زمانی', 'value' => $periodLabels[$filters['period']] ?? $filters['period']];
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

        // Add personnel filter
        if (!empty($filters['personnel_ids']) && !in_array(0, $filters['personnel_ids'])) {
            $personnelNames = \App\Models\Staff::whereIn('id', $filters['personnel_ids'])
                ->pluck('full_name')
                ->implode('، ');
            $summary[] = ['label' => 'پرسنل', 'value' => $personnelNames ?: 'نامشخص'];
        } elseif (isset($filters['personnel_ids']) && in_array(0, $filters['personnel_ids'])) {
            $summary[] = ['label' => 'پرسنل', 'value' => 'همه موارد'];
        }

        // Add acquisition source filter
        if (!empty($filters['acquisition_source_ids']) && !in_array(0, $filters['acquisition_source_ids'])) {
            $sourceNames = \App\Models\HowIntroduced::whereIn('id', $filters['acquisition_source_ids'])
                ->pluck('name')
                ->implode('، ');
            $summary[] = ['label' => 'نحوه آشنایی', 'value' => $sourceNames ?: 'نامشخص'];
        } elseif (isset($filters['acquisition_source_ids']) && in_array(0, $filters['acquisition_source_ids'])) {
            $summary[] = ['label' => 'نحوه آشنایی', 'value' => 'همه موارد'];
        }

        // Add payment amount filters
        if (isset($filters['min_paid_amount'])) {
            $summary[] = ['label' => 'حداقل مبلغ پرداختی', 'value' => number_format($filters['min_paid_amount']) . ' تومان'];
        }

        if (isset($filters['max_paid_amount'])) {
            $summary[] = ['label' => 'حداکثر مبلغ پرداختی', 'value' => number_format($filters['max_paid_amount']) . ' تومان'];
        }

        return $summary;
    }
}
