<?php

namespace App\Services\Reports;

use App\Models\Payment;
use App\Models\Expense;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinanceReportService extends BaseReportService
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
    }

    /**
     * Calculate KPIs.
     */
    protected function calculateKPIs(array $filters = [])
    {
        // Total income
        $totalIncome = Payment::where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('date', '<=', $this->dateTo);
            })
            ->sum('amount');

        // Total expenses
        $totalExpense = Expense::where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('date', '<=', $this->dateTo);
            })
            ->sum('amount');

        // Commission paid
        $commissionPaid = Expense::where('salon_id', $this->salonId)
            ->where('expense_type', 'commission')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('date', '<=', $this->dateTo);
            })
            ->sum('amount');

        // Net profit
        $netProfit = $totalIncome - $totalExpense;

        // Profit margin
        $profitMargin = $totalIncome > 0 ? ($netProfit / $totalIncome) * 100 : 0;

        // Top personnel by income
        $topPersonnelByIncome = Payment::where('salon_id', $this->salonId)
            ->whereNotNull('staff_id')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('date', '<=', $this->dateTo);
            })
            ->select('staff_id', DB::raw('SUM(amount) as total_income'))
            ->groupBy('staff_id')
            ->orderByDesc('total_income')
            ->with('staff')
            ->first();

        // Top service by income
        $topServiceByIncome = DB::table('appointment_service')
            ->join('appointments', 'appointment_service.appointment_id', '=', 'appointments.id')
            ->join('payments_received', 'appointments.id', '=', 'payments_received.appointment_id')
            ->join('services', 'appointment_service.service_id', '=', 'services.id')
            ->where('appointments.salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('payments_received.date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('payments_received.date', '<=', $this->dateTo);
            })
            ->select('services.name', DB::raw('SUM(payments_received.amount) as total_income'))
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('total_income')
            ->first();

        // Top customer by payment
        $topCustomerByPayment = Payment::where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('date', '<=', $this->dateTo);
            })
            ->select('customer_id', DB::raw('SUM(amount) as total_paid'))
            ->groupBy('customer_id')
            ->orderByDesc('total_paid')
            ->with('customer')
            ->first();

        // Highest income day
        $highestIncomeDay = Payment::where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('date', '<=', $this->dateTo);
            })
            ->select('date', DB::raw('SUM(amount) as daily_income'))
            ->groupBy('date')
            ->orderByDesc('daily_income')
            ->first();

        // Highest expense
        $highestExpense = Expense::where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('date', '<=', $this->dateTo);
            })
            ->orderByDesc('amount')
            ->first();

        return [
            'total_income' => round($totalIncome, 2),
            'total_expense' => round($totalExpense, 2),
            'net_profit' => round($netProfit, 2),
            'commission_paid' => round($commissionPaid, 2),
            'profit_margin' => round($profitMargin, 1),
            'top_personnel_by_income' => [
                'name' => $topPersonnelByIncome->staff->full_name ?? 'نامشخص',
                'amount' => $topPersonnelByIncome->total_income ?? 0,
            ],
            'top_service_by_income' => [
                'name' => $topServiceByIncome->name ?? 'نامشخص',
                'amount' => $topServiceByIncome->total_income ?? 0,
            ],
            'top_customer_by_payment' => [
                'name' => $topCustomerByPayment->customer->name ?? 'نامشخص',
                'amount' => $topCustomerByPayment->total_paid ?? 0,
            ],
            'highest_income_day' => [
                'date' => $highestIncomeDay->date ?? 'نامشخص',
                'amount' => $highestIncomeDay->daily_income ?? 0,
            ],
            'highest_expense' => [
                'description' => $highestExpense->description ?? 'نامشخص',
                'amount' => $highestExpense->amount ?? 0,
            ],
        ];
    }

    /**
     * Generate charts data.
     */
    protected function generateCharts(array $filters = [])
    {
        // Income and expense by weekday
        $incomeByWeekday = Payment::where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('date', '<=', $this->dateTo);
            })
            ->select(DB::raw('DAYOFWEEK(date) as day_of_week'), DB::raw('SUM(amount) as total'))
            ->groupBy('day_of_week')
            ->get()
            ->pluck('total', 'day_of_week');

        $expenseByWeekday = Expense::where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('date', '<=', $this->dateTo);
            })
            ->select(DB::raw('DAYOFWEEK(date) as day_of_week'), DB::raw('SUM(amount) as total'))
            ->groupBy('day_of_week')
            ->get()
            ->pluck('total', 'day_of_week');

        $weekdays = [];
        $incomeData = [];
        $expenseData = [];

        for ($i = 0; $i < 7; $i++) {
            $weekdays[] = $this->getWeekdayName($i);
            $incomeData[] = $incomeByWeekday[$i + 1] ?? 0;
            $expenseData[] = $expenseByWeekday[$i + 1] ?? 0;
        }

        return [
            'income_expense_by_weekday' => [
                'labels' => $weekdays,
                'datasets' => [
                    [
                        'label' => 'دریافتی‌ها',
                        'data' => $incomeData,
                    ],
                    [
                        'label' => 'پرداختی‌ها',
                        'data' => $expenseData,
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
            'income_by_staff' => $this->getIncomeByStaff(),
            'commission_by_staff' => $this->getCommissionByStaff(),
            'income_by_service' => $this->getIncomeByService(),
            'income_by_customer' => $this->getIncomeByCustomer(),
            'expense_by_category' => $this->getExpenseByCategory(),
        ];
    }

    /**
     * Get income by staff.
     */
    protected function getIncomeByStaff()
    {
        return Payment::where('salon_id', $this->salonId)
            ->whereNotNull('staff_id')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('date', '<=', $this->dateTo);
            })
            ->select('staff_id', DB::raw('SUM(amount) as total_income'))
            ->groupBy('staff_id')
            ->orderByDesc('total_income')
            ->with('staff')
            ->get()
            ->map(function ($item) {
                return [
                    'staff_name' => $item->staff->full_name ?? 'نامشخص',
                    'total_income' => $item->total_income,
                ];
            });
    }

    /**
     * Get commission by staff.
     */
    protected function getCommissionByStaff()
    {
        return Expense::where('salon_id', $this->salonId)
            ->where('expense_type', 'commission')
            ->whereNotNull('staff_id')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('date', '<=', $this->dateTo);
            })
            ->select('staff_id', DB::raw('SUM(amount) as total_commission'))
            ->groupBy('staff_id')
            ->orderByDesc('total_commission')
            ->with('staff')
            ->get()
            ->map(function ($item) {
                return [
                    'staff_name' => $item->staff->full_name ?? 'نامشخص',
                    'total_commission' => $item->total_commission,
                ];
            });
    }

    /**
     * Get income by service.
     */
    protected function getIncomeByService()
    {
        return DB::table('appointment_service')
            ->join('appointments', 'appointment_service.appointment_id', '=', 'appointments.id')
            ->join('payments_received', 'appointments.id', '=', 'payments_received.appointment_id')
            ->join('services', 'appointment_service.service_id', '=', 'services.id')
            ->where('appointments.salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('payments_received.date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('payments_received.date', '<=', $this->dateTo);
            })
            ->select('services.name', DB::raw('SUM(payments_received.amount) as total_income'))
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('total_income')
            ->get()
            ->map(function ($item) {
                return [
                    'service' => $item->name,
                    'total_income' => $item->total_income,
                ];
            });
    }

    /**
     * Get income by customer.
     */
    protected function getIncomeByCustomer()
    {
        return Payment::where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('date', '<=', $this->dateTo);
            })
            ->select('customer_id', DB::raw('SUM(amount) as total_paid'))
            ->groupBy('customer_id')
            ->orderByDesc('total_paid')
            ->with('customer')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'customer_name' => $item->customer->name ?? 'نامشخص',
                    'total_paid' => $item->total_paid,
                ];
            });
    }

    /**
     * Get expense by category.
     */
    protected function getExpenseByCategory()
    {
        return Expense::where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('date', '<=', $this->dateTo);
            })
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category ?? 'سایر',
                    'total' => $item->total,
                ];
            });
    }
}
