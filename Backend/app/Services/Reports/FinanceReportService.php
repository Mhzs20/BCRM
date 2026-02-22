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

        // Top personnel by income (via appointment's staff_id)
        $topPersonnelByIncome = DB::table('payments_received')
            ->join('appointments', 'payments_received.appointment_id', '=', 'appointments.id')
            ->join('salon_staff', 'appointments.staff_id', '=', 'salon_staff.id')
            ->where('payments_received.salon_id', $this->salonId)
            ->whereNotNull('appointments.staff_id')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('payments_received.date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('payments_received.date', '<=', $this->dateTo);
            })
            ->select('appointments.staff_id', 'salon_staff.full_name', DB::raw('SUM(payments_received.amount) as total_income'))
            ->groupBy('appointments.staff_id', 'salon_staff.full_name')
            ->orderByDesc('total_income')
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
                'name' => $topPersonnelByIncome->full_name ?? 'نامشخص',
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

        // ---- Total Income Pie Chart (مجموع دریافتی) ----
        // Staff income = sum of payments linked to appointments with a staff
        $staffIncome = DB::table('payments_received')
            ->join('appointments', 'payments_received.appointment_id', '=', 'appointments.id')
            ->where('payments_received.salon_id', $this->salonId)
            ->whereNotNull('appointments.staff_id')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('payments_received.date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('payments_received.date', '<=', $this->dateTo);
            })
            ->sum('payments_received.amount');

        $totalIncome = Payment::where('salon_id', $this->salonId)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('date', '<=', $this->dateTo);
            })
            ->sum('amount');

        $salonIncome = $totalIncome - $staffIncome;

        // ---- Income Distribution by Staff Pie Chart (توزیع درآمد پرسنل) ----
        $incomeByStaff = DB::table('payments_received')
            ->join('appointments', 'payments_received.appointment_id', '=', 'appointments.id')
            ->join('salon_staff', 'appointments.staff_id', '=', 'salon_staff.id')
            ->where('payments_received.salon_id', $this->salonId)
            ->whereNotNull('appointments.staff_id')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('payments_received.date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('payments_received.date', '<=', $this->dateTo);
            })
            ->select('salon_staff.full_name', DB::raw('SUM(payments_received.amount) as total_income'))
            ->groupBy('appointments.staff_id', 'salon_staff.full_name')
            ->orderByDesc('total_income')
            ->get();

        // ---- Commission by Staff Bar Chart (پورسانت پرسنل) ----
        $commissionByStaff = DB::table('staff_commission_transactions')
            ->join('salon_staff', 'staff_commission_transactions.staff_id', '=', 'salon_staff.id')
            ->where('staff_commission_transactions.salon_id', $this->salonId)
            ->where('staff_commission_transactions.amount', '>', 0)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('staff_commission_transactions.created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('staff_commission_transactions.created_at', '<=', $this->dateTo);
            })
            ->select('salon_staff.full_name', DB::raw('SUM(staff_commission_transactions.amount) as total_commission'))
            ->groupBy('staff_commission_transactions.staff_id', 'salon_staff.full_name')
            ->orderByDesc('total_commission')
            ->get();

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
            'total_income_distribution' => [
                'type' => 'pie',
                'title' => 'مجموع دریافتی',
                'total_income' => $totalIncome,
                'staff_income' => $staffIncome,
                'salon_income' => $salonIncome,
                'labels' => ['درآمد سالن', 'درآمد خدمات'],
                'data' => [$salonIncome, $staffIncome],
            ],
            'income_by_staff_chart' => [
                'type' => 'pie',
                'title' => 'توزیع درآمد پرسنل',
                'labels' => $incomeByStaff->pluck('full_name')->toArray(),
                'data' => $incomeByStaff->pluck('total_income')->toArray(),
            ],
            'commission_by_staff_chart' => [
                'type' => 'bar',
                'title' => 'پورسانت پرسنل',
                'labels' => $commissionByStaff->pluck('full_name')->toArray(),
                'datasets' => [
                    [
                        'label' => 'میزان پورسانت',
                        'data' => $commissionByStaff->pluck('total_commission')->toArray(),
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
        return DB::table('payments_received')
            ->join('appointments', 'payments_received.appointment_id', '=', 'appointments.id')
            ->join('salon_staff', 'appointments.staff_id', '=', 'salon_staff.id')
            ->where('payments_received.salon_id', $this->salonId)
            ->whereNotNull('appointments.staff_id')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('payments_received.date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('payments_received.date', '<=', $this->dateTo);
            })
            ->select('appointments.staff_id', 'salon_staff.full_name', DB::raw('SUM(payments_received.amount) as total_income'))
            ->groupBy('appointments.staff_id', 'salon_staff.full_name')
            ->orderByDesc('total_income')
            ->get()
            ->map(function ($item) {
                return [
                    'staff_name' => $item->full_name ?? 'نامشخص',
                    'total_income' => $item->total_income,
                ];
            });
    }

    /**
     * Get commission by staff.
     * Uses staff_commission_transactions table as primary source,
     * falls back to expenses with commission_payment type.
     */
    protected function getCommissionByStaff()
    {
        return DB::table('staff_commission_transactions')
            ->join('salon_staff', 'staff_commission_transactions.staff_id', '=', 'salon_staff.id')
            ->where('staff_commission_transactions.salon_id', $this->salonId)
            ->where('staff_commission_transactions.amount', '>', 0)
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('staff_commission_transactions.created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('staff_commission_transactions.created_at', '<=', $this->dateTo);
            })
            ->select('staff_commission_transactions.staff_id', 'salon_staff.full_name', DB::raw('SUM(staff_commission_transactions.amount) as total_commission'))
            ->groupBy('staff_commission_transactions.staff_id', 'salon_staff.full_name')
            ->orderByDesc('total_commission')
            ->get()
            ->map(function ($item) {
                return [
                    'staff_name' => $item->full_name ?? 'نامشخص',
                    'total_commission' => $item->total_commission,
                ];
            });
    }

    /**
     * Get income by service.
     */
    protected function getIncomeByService()
    {
        // Calculate each service's share of the appointment payment
        // When an appointment has multiple services, divide the payment equally
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
            ->select(
                'services.name',
                DB::raw('SUM(payments_received.amount / (SELECT COUNT(*) FROM appointment_service AS as2 WHERE as2.appointment_id = appointments.id)) as total_income')
            )
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('total_income')
            ->get()
            ->map(function ($item) {
                return [
                    'service' => $item->name,
                    'total_income' => round($item->total_income, 2),
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
