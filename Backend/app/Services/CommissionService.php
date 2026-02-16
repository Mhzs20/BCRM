<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Staff;
use App\Models\StaffServiceCommission;
use App\Models\StaffCommissionTransaction;
use App\Models\Expense;
use App\Models\CashboxTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

/**
 * سرویس محاسبه و مدیریت پورسانت کارکنان
 */
class CommissionService
{
    /**
     * محاسبه و ثبت پورسانت برای یک نوبت انجام شده
     */
    public function processAppointmentCommission(Appointment $appointment): array
    {
        $results = [];

        // بررسی وضعیت نوبت
        if ($appointment->status !== 'completed') {
            return [
                'success' => false,
                'message' => 'نوبت هنوز انجام نشده است',
                'transactions' => []
            ];
        }

        // بررسی وجود کارکن
        if (!$appointment->staff_id) {
            return [
                'success' => false,
                'message' => 'کارکنی برای این نوبت تعیین نشده است',
                'transactions' => []
            ];
        }

        $staff = Staff::find($appointment->staff_id);
        if (!$staff) {
            return [
                'success' => false,
                'message' => 'کارکن یافت نشد',
                'transactions' => []
            ];
        }

        // بررسی اینکه قبلاً پورسانت ثبت نشده باشد
        $existingTransactions = StaffCommissionTransaction::where('appointment_id', $appointment->id)
            ->where('transaction_type', StaffCommissionTransaction::TYPE_COMMISSION)
            ->count();

        if ($existingTransactions > 0) {
            return [
                'success' => false,
                'message' => 'پورسانت این نوبت قبلاً ثبت شده است',
                'transactions' => []
            ];
        }

        DB::beginTransaction();

        try {
            $transactions = [];
            $services = $appointment->services()->withPivot('price_at_booking')->get();

            foreach ($services as $service) {
                $servicePrice = (float) ($service->pivot->price_at_booking ?? $service->price ?? 0);
                
                if ($servicePrice <= 0) {
                    continue;
                }

                // دریافت تنظیمات پورسانت برای این خدمت و کارکن
                $commissionSetting = StaffServiceCommission::where('staff_id', $staff->id)
                    ->where('service_id', $service->id)
                    ->where('is_active', true)
                    ->first();

                // اگر تنظیمات اختصاصی وجود نداشت، از تنظیمات پیش‌فرض کارکن استفاده کن
                if (!$commissionSetting) {
                    $commissionType = $staff->commission_type ?? 'percentage';
                    $commissionValue = (float) ($staff->commission_value ?? 0);
                } else {
                    $commissionType = $commissionSetting->commission_type;
                    $commissionValue = (float) $commissionSetting->commission_value;
                }

                if ($commissionValue <= 0) {
                    continue;
                }

                // محاسبه تخفیف (پروراتا بر اساس قیمت خدمت)
                $totalPrice = (float) $appointment->total_price;
                $discountAmount = 0;

                // اگر مجموع قیمت خدمات بیشتر از قیمت نهایی باشد، تخفیف داده شده
                $totalServicesPrice = $services->sum(function ($s) {
                    return (float) ($s->pivot->price_at_booking ?? $s->price ?? 0);
                });

                if ($totalServicesPrice > $totalPrice && $totalServicesPrice > 0) {
                    $totalDiscount = $totalServicesPrice - $totalPrice;
                    // محاسبه سهم این خدمت از تخفیف (به نسبت قیمت)
                    $discountAmount = round(($servicePrice / $totalServicesPrice) * $totalDiscount, 2);
                }

                // تعیین مبلغ پایه برای محاسبه پورسانت
                $baseAmount = $servicePrice;
                if ($staff->apply_discount_to_commission && $discountAmount > 0) {
                    $baseAmount = $servicePrice - $discountAmount;
                }
                // محاسبه مبلغ پورسانت
                $commissionAmount = $this->calculateCommissionAmount($commissionType, $commissionValue, $baseAmount);

                if ($commissionAmount <= 0) {
                    continue;
                }

                // بررسی سقف ماهانه
                $cappedAmount = $this->applyMonthlyCap($staff, $commissionAmount);

                // ایجاد تراکنش پورسانت
                $transaction = StaffCommissionTransaction::create([
                    'salon_id' => $appointment->salon_id,
                    'staff_id' => $staff->id,
                    'appointment_id' => $appointment->id,
                    'service_id' => $service->id,
                    'transaction_type' => StaffCommissionTransaction::TYPE_COMMISSION,
                    'service_price' => $servicePrice,
                    'discount_amount' => $discountAmount,
                    'base_amount' => $baseAmount,
                    'commission_rate' => $commissionValue,
                    'commission_type' => $commissionType,
                    'amount' => $cappedAmount,
                    'payment_status' => StaffCommissionTransaction::STATUS_PENDING,
                    'description' => $cappedAmount < $commissionAmount 
                        ? 'پورسانت به دلیل رسیدن به سقف ماهانه محدود شد' 
                        : null,
                ]);

                $transactions[] = $transaction;
            }

            DB::commit();

            return [
                'success' => true,
                'message' => count($transactions) > 0 
                    ? sprintf('%d تراکنش پورسانت ثبت شد', count($transactions))
                    : 'پورسانتی برای ثبت وجود نداشت',
                'transactions' => $transactions
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('خطا در محاسبه پورسانت: ' . $e->getMessage(), [
                'appointment_id' => $appointment->id,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در محاسبه پورسانت: ' . $e->getMessage(),
                'transactions' => []
            ];
        }
    }

    /**
     * محاسبه مبلغ پورسانت
     */
    protected function calculateCommissionAmount(string $type, float $rate, float $amount): float
    {
        if ($type === 'percentage') {
            return round(($amount * $rate) / 100, 2);
        }
        return $rate; // مبلغ ثابت
    }

    /**
     * اعمال سقف ماهانه پورسانت
     */
    protected function applyMonthlyCap(Staff $staff, float $commissionAmount): float
    {
        if (!$staff->monthly_commission_cap || $staff->monthly_commission_cap <= 0) {
            return $commissionAmount;
        }

        // محاسبه پورسانت ماه جاری
        $currentMonthTotal = $this->getStaffMonthlyCommission($staff->id);

        $remainingCap = $staff->monthly_commission_cap - $currentMonthTotal;

        if ($remainingCap <= 0) {
            return 0;
        }

        return min($commissionAmount, $remainingCap);
    }

    /**
     * دریافت مجموع پورسانت ماه جاری کارکن
     */
    public function getStaffMonthlyCommission(int $staffId, ?int $year = null, ?int $month = null): float
    {
        // اگر سال و ماه مشخص نشده، ماه جاری شمسی
        if (!$year || !$month) {
            $now = Jalalian::now();
            $year = $now->getYear();
            $month = $now->getMonth();
        }

        return StaffCommissionTransaction::where('staff_id', $staffId)
            ->where('transaction_type', StaffCommissionTransaction::TYPE_COMMISSION)
            ->inJalaliMonth($year, $month)
            ->sum('amount');
    }

    /**
     * ثبت اصلاح پورسانت (کسر یا اضافه)
     */
    public function createAdjustment(
        int $salonId,
        int $staffId,
        float $amount,
        string $description,
        ?int $createdBy = null
    ): StaffCommissionTransaction {
        return StaffCommissionTransaction::create([
            'salon_id' => $salonId,
            'staff_id' => $staffId,
            'transaction_type' => StaffCommissionTransaction::TYPE_ADJUSTMENT,
            'amount' => $amount, // می‌تواند منفی باشد
            'payment_status' => StaffCommissionTransaction::STATUS_PENDING,
            'description' => $description,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * ثبت پرداخت پورسانت
     */
    public function recordPayment(
        int $salonId,
        int $staffId,
        float $amount,
        string $description,
        ?int $createdBy = null,
        bool $createExpense = true,
        ?int $cashboxId = null,
        ?string $paymentDate = null,
        ?string $paymentTime = null,
        ?int $forMonth = null,
        ?int $forYear = null
    ): array {
        DB::beginTransaction();

        try {
            // تعیین تاریخ و زمان پرداخت
            $paidAtDateTime = now();
            if ($paymentDate) {
                try {
                    // تلاش برای تبدیل تاریخ شمسی به میلادی
                    if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $paymentDate)) {
                        // اگر تاریخ شمسی است، تبدیل به میلادی
                        $dateParts = explode('-', $paymentDate);
                        if (count($dateParts) === 3 && (int)$dateParts[0] >= 1300) {
                            $jalalian = Jalalian::fromFormat('Y-m-d', $paymentDate);
                            $paidAtDateTime = $jalalian->toCarbon();
                        } else {
                            // تاریخ میلادی است
                            $paidAtDateTime = \Carbon\Carbon::parse($paymentDate);
                        }
                    } else {
                        $paidAtDateTime = \Carbon\Carbon::parse($paymentDate);
                    }
                    
                    if ($paymentTime) {
                        $timeParts = explode(':', $paymentTime);
                        if (count($timeParts) >= 2) {
                            $paidAtDateTime->setTime((int)$timeParts[0], (int)$timeParts[1], $timeParts[2] ?? 0);
                        }
                    }
                } catch (\Exception $e) {
                    // در صورت خطا از زمان فعلی استفاده می‌شود
                    Log::warning('Error parsing payment date: ' . $e->getMessage());
                    $paidAtDateTime = now();
                }
            }

            // ایجاد تراکنش پرداخت (با مقدار منفی)
            $transactionData = [
                'salon_id' => $salonId,
                'staff_id' => $staffId,
                'transaction_type' => StaffCommissionTransaction::TYPE_PAYMENT,
                'amount' => -abs($amount), // پرداخت همیشه منفی
                'payment_status' => StaffCommissionTransaction::STATUS_PAID,
                'paid_at' => $paidAtDateTime,
                'description' => $description,
                'created_by' => $createdBy,
            ];

            // اضافه کردن ماه و سال در صورت وجود
            if ($forMonth !== null && $forYear !== null) {
                $transactionData['for_month'] = $forMonth;
                $transactionData['for_year'] = $forYear;
            }

            $paymentTransaction = StaffCommissionTransaction::create($transactionData);

            // به‌روزرسانی مجموع پورسانت پرداخت شده کارکن
            $staff = Staff::find($staffId);
            if ($staff) {
                $staff->increment('total_commission_paid', abs($amount));
            }

            // ایجاد هزینه در ماژول مالی
            $expense = null;
            if ($createExpense) {
                $expenseDate = $paymentDate ? \Carbon\Carbon::parse($paymentDate)->toDateString() : now()->toDateString();
                
                $expense = Expense::create([
                    'salon_id' => $salonId,
                    'date' => $expenseDate,
                    'description' => 'پرداخت پورسانت: ' . $description,
                    'amount' => abs($amount),
                    'category' => 'حقوق و دستمزد',
                    'expense_type' => 'commission_payment',
                    'staff_id' => $staffId,
                    'cashbox_id' => $cashboxId, // اضافه شد: ارتباط با صندوق
                ]);

                // اگر صندوق مشخص شده، تراکنش صندوق هم ثبت می‌شود
                if ($cashboxId) {
                    $cashboxService = app(CashboxService::class);
                    $transactionDate = $paymentDate ? \Carbon\Carbon::parse($paymentDate)->toDateString() : now()->toDateString();
                    $transactionTime = $paymentTime ?: now()->format('H:i:s');
                    
                    $cashboxService->recordExpense([
                        'cashbox_id' => $cashboxId,
                        'amount' => abs($amount),
                        'description' => 'پرداخت پورسانت: ' . $staff->name,
                        'category' => 'حقوق و دستمزد',
                        'expense_id' => $expense->id,
                        'commission_transaction_id' => $paymentTransaction->id,
                        'transaction_date' => $transactionDate,
                        'transaction_time' => $transactionTime,
                        'created_by' => $createdBy,
                    ]);
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'پرداخت پورسانت با موفقیت ثبت شد',
                'transaction' => $paymentTransaction,
                'expense' => $expense
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error recording commission payment: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'خطا در ثبت پرداخت: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * تسویه گروهی تراکنش‌های منتظر پرداخت
     */
    public function bulkSettleTransactions(
        int $salonId,
        int $staffId,
        array $transactionIds,
        ?int $createdBy = null
    ): array {
        DB::beginTransaction();

        try {
            $transactions = StaffCommissionTransaction::whereIn('id', $transactionIds)
                ->where('salon_id', $salonId)
                ->where('staff_id', $staffId)
                ->where('payment_status', StaffCommissionTransaction::STATUS_PENDING)
                ->get();

            if ($transactions->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'تراکنشی برای تسویه یافت نشد'
                ];
            }

            $totalAmount = $transactions->sum('amount');

            // علامت‌گذاری تراکنش‌ها به عنوان پرداخت شده
            StaffCommissionTransaction::whereIn('id', $transactionIds)
                ->where('payment_status', StaffCommissionTransaction::STATUS_PENDING)
                ->update([
                    'payment_status' => StaffCommissionTransaction::STATUS_PAID,
                    'paid_at' => now()
                ]);

            // ثبت پرداخت
            $paymentResult = $this->recordPayment(
                $salonId,
                $staffId,
                $totalAmount,
                sprintf('تسویه %d تراکنش پورسانت', $transactions->count()),
                $createdBy
            );

            DB::commit();

            return [
                'success' => true,
                'message' => sprintf('%d تراکنش تسویه شد - مبلغ: %s تومان', 
                    $transactions->count(), 
                    number_format($totalAmount)
                ),
                'settled_count' => $transactions->count(),
                'total_amount' => $totalAmount,
                'payment' => $paymentResult
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('خطا در تسویه گروهی: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'خطا در تسویه: ' . $e->getMessage()
            ];
        }
    }

    /**
     * دریافت خلاصه مالی کارکن
     */
    public function getStaffFinancialSummary(int $staffId, ?int $year = null, ?int $month = null): array
    {
        $query = StaffCommissionTransaction::where('staff_id', $staffId);

        if ($year && $month) {
            $query->inJalaliMonth($year, $month);
        }

        $transactions = $query->get();

        $totalEarned = $transactions
            ->whereIn('transaction_type', [
                StaffCommissionTransaction::TYPE_COMMISSION,
                StaffCommissionTransaction::TYPE_ADJUSTMENT
            ])
            ->where('amount', '>', 0)
            ->sum('amount');

        $totalDeductions = abs($transactions
            ->where('amount', '<', 0)
            ->sum('amount'));

        $pendingAmount = $transactions
            ->where('payment_status', StaffCommissionTransaction::STATUS_PENDING)
            ->sum('amount');

        $paidAmount = $transactions
            ->where('transaction_type', StaffCommissionTransaction::TYPE_PAYMENT)
            ->sum(function ($t) {
                return abs($t->amount);
            });

        $appointmentCount = $transactions
            ->where('transaction_type', StaffCommissionTransaction::TYPE_COMMISSION)
            ->pluck('appointment_id')
            ->unique()
            ->count();

        return [
            'total_earned' => $totalEarned,
            'total_deductions' => $totalDeductions,
            'net_amount' => $totalEarned - $totalDeductions,
            'pending_amount' => $pendingAmount,
            'paid_amount' => $paidAmount,
            'appointment_count' => $appointmentCount,
            'balance' => $pendingAmount, // بدهی فعلی
        ];
    }

    /**
     * دریافت گزارش داشبورد پورسانت
     */
    public function getDashboardReport(int $salonId, ?int $year = null, ?int $month = null): array
    {
        $staffList = Staff::where('salon_id', $salonId)
            ->where('is_active', true)
            ->get();

        $report = [];

        foreach ($staffList as $staff) {
            $summary = $this->getStaffFinancialSummary($staff->id, $year, $month);
            
            $report[] = [
                'staff_id' => $staff->id,
                'staff_name' => $staff->full_name,
                'specialty' => $staff->specialty,
                'profile_image' => $staff->profile_image,
                'monthly_commission_cap' => $staff->monthly_commission_cap,
                'apply_discount_to_commission' => $staff->apply_discount_to_commission,
                ...$summary
            ];
        }

        // مرتب‌سازی بر اساس درآمد
        usort($report, function ($a, $b) {
            return $b['total_earned'] <=> $a['total_earned'];
        });

        return [
            'staff_reports' => $report,
            'totals' => [
                'total_earned' => array_sum(array_column($report, 'total_earned')),
                'total_pending' => array_sum(array_column($report, 'pending_amount')),
                'total_paid' => array_sum(array_column($report, 'paid_amount')),
                'staff_count' => count($report),
            ]
        ];
    }
}
