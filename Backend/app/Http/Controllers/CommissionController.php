<?php

namespace App\Http\Controllers;

use App\Models\Salon;
use App\Models\Staff;
use App\Models\StaffServiceCommission;
use App\Models\StaffCommissionTransaction;
use App\Services\CommissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Morilog\Jalali\Jalalian;

class CommissionController extends Controller
{
    protected CommissionService $commissionService;

    public function __construct(CommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
    }

    /**
     * دریافت داشبورد پورسانت
     * GET /salons/{salon}/commissions/dashboard
     */
    public function dashboard(Request $request, Salon $salon)
    {
        $year = $request->input('year');
        $month = $request->input('month');

        // اگر سال و ماه مشخص نشده، ماه جاری شمسی
        if (!$year || !$month) {
            $now = Jalalian::now();
            $year = $now->getYear();
            $month = $now->getMonth();
        }

        $report = $this->commissionService->getDashboardReport($salon->id, (int) $year, (int) $month);

        return response()->json([
            'success' => true,
            'data' => $report,
            'filters' => [
                'year' => (int) $year,
                'month' => (int) $month,
                'month_name' => $this->getJalaliMonthName((int) $month),
            ]
        ]);
    }

    /**
     * دریافت لیست کارکنان با اطلاعات پورسانت
     * GET /salons/{salon}/commissions/staff
     */
    public function staffList(Request $request, Salon $salon)
    {
        $year = $request->input('year');
        $month = $request->input('month');
        $paymentStatus = $request->input('payment_status'); // pending, paid, all

        $query = Staff::where('salon_id', $salon->id)
            ->with('services:id,name,duration_minutes,price');

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $staffList = $query->get()->map(function ($staff) use ($year, $month, $paymentStatus) {
            $summary = $this->commissionService->getStaffFinancialSummary(
                $staff->id, 
                $year ? (int) $year : null, 
                $month ? (int) $month : null
            );

            return [
                'id' => $staff->id,
                'full_name' => $staff->full_name,
                'specialty' => $staff->specialty,
                'profile_image' => $staff->profile_image,
                'is_active' => $staff->is_active,
                'commission_type' => $staff->commission_type,
                'commission_value' => (float) $staff->commission_value,
                'monthly_commission_cap' => $staff->monthly_commission_cap ? (float) $staff->monthly_commission_cap : null,
                'apply_discount_to_commission' => $staff->apply_discount_to_commission,
                'total_commission_paid' => (float) $staff->total_commission_paid,
                'services' => $staff->services,
                ...$summary
            ];
        });

        // فیلتر بر اساس وضعیت پرداخت
        if ($paymentStatus === 'pending') {
            $staffList = $staffList->filter(fn($s) => $s['pending_amount'] > 0)->values();
        } elseif ($paymentStatus === 'paid') {
            $staffList = $staffList->filter(fn($s) => $s['paid_amount'] > 0)->values();
        }

        return response()->json([
            'success' => true,
            'data' => $staffList,
        ]);
    }

    /**
     * دریافت تراکنش‌های پورسانت یک کارکن
     * GET /salons/{salon}/commissions/staff/{staff}/transactions
     */
    public function staffTransactions(Request $request, Salon $salon, Staff $staff)
    {
        if ($staff->salon_id !== $salon->id) {
            return response()->json(['message' => 'کارکن یافت نشد'], 404);
        }

        $query = StaffCommissionTransaction::where('staff_id', $staff->id)
            ->with(['service:id,name', 'appointment:id,appointment_date,start_time', 'createdBy:id,name']);

        // فیلتر بر اساس وضعیت پرداخت
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        // فیلتر بر اساس نوع تراکنش
        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->input('transaction_type'));
        }

        // فیلتر بر اساس ماه و سال شمسی
        if ($request->filled('year') && $request->filled('month')) {
            $query->inJalaliMonth((int) $request->input('year'), (int) $request->input('month'));
        }

        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        // محاسبه خلاصه مالی
        $summary = $this->commissionService->getStaffFinancialSummary(
            $staff->id,
            $request->filled('year') ? (int) $request->input('year') : null,
            $request->filled('month') ? (int) $request->input('month') : null
        );

        return response()->json([
            'success' => true,
            'data' => $transactions,
            'summary' => $summary,
            'staff' => [
                'id' => $staff->id,
                'full_name' => $staff->full_name,
                'specialty' => $staff->specialty,
                'profile_image' => $staff->profile_image,
            ]
        ]);
    }

    /**
     * دریافت تنظیمات پورسانت خدمات یک کارکن
     * GET /salons/{salon}/commissions/staff/{staff}/service-settings
     */
    public function getServiceCommissionSettings(Request $request, Salon $salon, Staff $staff)
    {
        if ($staff->salon_id !== $salon->id) {
            return response()->json(['message' => 'کارکن یافت نشد'], 404);
        }

        // دریافت همه خدمات کارکن
        $staffServices = $staff->services()->get();

        // دریافت تنظیمات پورسانت اختصاصی
        $commissionSettings = StaffServiceCommission::where('staff_id', $staff->id)
            ->get()
            ->keyBy('service_id');

        $result = $staffServices->map(function ($service) use ($staff, $commissionSettings) {
            $setting = $commissionSettings->get($service->id);

            return [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'service_price' => (float) $service->price,
                'has_custom_commission' => $setting !== null,
                'commission_type' => $setting ? $setting->commission_type : $staff->commission_type,
                'commission_value' => $setting ? (float) $setting->commission_value : (float) $staff->commission_value,
                'is_active' => $setting ? $setting->is_active : true,
                'is_using_default' => $setting === null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $result,
            'staff_default' => [
                'commission_type' => $staff->commission_type,
                'commission_value' => (float) $staff->commission_value,
                'monthly_commission_cap' => $staff->monthly_commission_cap ? (float) $staff->monthly_commission_cap : null,
                'apply_discount_to_commission' => (bool) $staff->apply_discount_to_commission,
            ]
        ]);
    }

    /**
     * به‌روزرسانی تنظیمات پورسانت خدمات یک کارکن
     * PUT /salons/{salon}/commissions/staff/{staff}/service-settings
     */
    public function updateServiceCommissionSettings(Request $request, Salon $salon, Staff $staff)
    {
        if ($staff->salon_id !== $salon->id) {
            return response()->json(['message' => 'کارکن یافت نشد'], 404);
        }

        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.service_id' => 'required|exists:services,id',
            'settings.*.commission_type' => 'required|in:percentage,fixed',
            'settings.*.commission_value' => 'required|numeric|min:0',
            'settings.*.is_active' => 'boolean',
            'settings.*.use_default' => 'boolean',
            'monthly_commission_cap' => 'nullable|numeric|min:0',
            'apply_discount_to_commission' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            foreach ($request->input('settings') as $setting) {
                $serviceId = $setting['service_id'];
                $useDefault = $setting['use_default'] ?? false;

                if ($useDefault) {
                    // حذف تنظیمات اختصاصی
                    StaffServiceCommission::where('staff_id', $staff->id)
                        ->where('service_id', $serviceId)
                        ->delete();
                } else {
                    // ایجاد یا به‌روزرسانی تنظیمات اختصاصی
                    StaffServiceCommission::updateOrCreate(
                        [
                            'staff_id' => $staff->id,
                            'service_id' => $serviceId,
                        ],
                        [
                            'salon_id' => $salon->id,
                            'commission_type' => $setting['commission_type'],
                            'commission_value' => $setting['commission_value'],
                            'is_active' => $setting['is_active'] ?? true,
                        ]
                    );
                }
            }

            // به‌روزرسانی تنظیمات عمومی پورسانت کارکن
            $staffUpdateData = [];
            if ($request->has('monthly_commission_cap')) {
                $staffUpdateData['monthly_commission_cap'] = $request->input('monthly_commission_cap');
            }
            if ($request->has('apply_discount_to_commission')) {
                $staffUpdateData['apply_discount_to_commission'] = $request->input('apply_discount_to_commission');
            }
            
            if (!empty($staffUpdateData)) {
                $staff->update($staffUpdateData);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تنظیمات پورسانت با موفقیت ذخیره شد'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('خطا در ذخیره تنظیمات پورسانت: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'خطا در ذخیره تنظیمات'
            ], 500);
        }
    }

    /**
     * به‌روزرسانی تنظیمات پایه پورسانت کارکن
     * PUT /salons/{salon}/commissions/staff/{staff}/settings
     */
    public function updateStaffCommissionSettings(Request $request, Salon $salon, Staff $staff)
    {
        if ($staff->salon_id !== $salon->id) {
            return response()->json(['message' => 'کارکن یافت نشد'], 404);
        }

        $validator = Validator::make($request->all(), [
            'commission_type' => 'sometimes|in:percentage,fixed',
            'commission_value' => 'sometimes|numeric|min:0',
            'monthly_commission_cap' => 'nullable|numeric|min:0',
            'apply_discount_to_commission' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = $request->only([
                'commission_type',
                'commission_value',
                'monthly_commission_cap',
                'apply_discount_to_commission'
            ]);

            $staff->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'تنظیمات پورسانت کارکن با موفقیت ذخیره شد',
                'data' => $staff->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('خطا در ذخیره تنظیمات پورسانت کارکن: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'خطا در ذخیره تنظیمات'
            ], 500);
        }
    }

    /**
     * ثبت اصلاح پورسانت (کسر یا اضافه)
     * POST /salons/{salon}/commissions/staff/{staff}/adjustment
     */
    public function createAdjustment(Request $request, Salon $salon, Staff $staff)
    {
        if ($staff->salon_id !== $salon->id) {
            return response()->json(['message' => 'کارکن یافت نشد'], 404);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric', // می‌تواند منفی باشد
            'description' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $transaction = $this->commissionService->createAdjustment(
                $salon->id,
                $staff->id,
                $request->input('amount'),
                $request->input('description'),
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'اصلاح پورسانت با موفقیت ثبت شد',
                'data' => $transaction
            ]);

        } catch (\Exception $e) {
            Log::error('خطا در ثبت اصلاح پورسانت: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'خطا در ثبت اصلاح'
            ], 500);
        }
    }

    /**
     * ثبت پرداخت پورسانت
     * POST /salons/{salon}/commissions/staff/{staff}/payment
     */
    public function recordPayment(Request $request, Salon $salon, Staff $staff)
    {
        if ($staff->salon_id !== $salon->id) {
            return response()->json(['message' => 'کارکن یافت نشد'], 404);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:500',
            'cashbox_id' => 'nullable|exists:cashboxes,id',
            'create_expense' => 'boolean',
            'payment_date' => 'nullable|date',
            'payment_time' => 'nullable|string',
            'for_month' => 'nullable|integer|min:1|max:12',
            'for_year' => 'nullable|integer|min:1300|max:1500',
        ], [
            'amount.required' => 'مبلغ الزامی است',
            'amount.min' => 'مبلغ باید بیشتر از صفر باشد',
            'cashbox_id.exists' => 'صندوق انتخابی یافت نشد',
            'payment_date.date' => 'فرمت تاریخ پرداخت نامعتبر است',
            'for_month.min' => 'ماه باید بین 1 تا 12 باشد',
            'for_month.max' => 'ماه باید بین 1 تا 12 باشد',
            'for_year.min' => 'سال باید بین 1300 تا 1500 باشد',
            'for_year.max' => 'سال باید بین 1300 تا 1500 باشد',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->commissionService->recordPayment(
            $salon->id,
            $staff->id,
            $request->input('amount'),
            $request->input('description', 'پرداخت پورسانت'),
            auth()->id(),
            $request->input('create_expense', true),
            $request->input('cashbox_id'),
            $request->input('payment_date'),
            $request->input('payment_time'),
            $request->input('for_month'),
            $request->input('for_year')
        );

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * تسویه گروهی تراکنش‌ها
     * POST /salons/{salon}/commissions/staff/{staff}/settle
     */
    public function bulkSettle(Request $request, Salon $salon, Staff $staff)
    {
        if ($staff->salon_id !== $salon->id) {
            return response()->json(['message' => 'کارکن یافت نشد'], 404);
        }

        $validator = Validator::make($request->all(), [
            'transaction_ids' => 'required|array|min:1',
            'transaction_ids.*' => 'required|integer|exists:staff_commission_transactions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->commissionService->bulkSettleTransactions(
            $salon->id,
            $staff->id,
            $request->input('transaction_ids'),
            auth()->id()
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * تسویه همه تراکنش‌های منتظر پرداخت یک کارکن
     * POST /salons/{salon}/commissions/staff/{staff}/settle-all
     */
    public function settleAll(Request $request, Salon $salon, Staff $staff)
    {
        if ($staff->salon_id !== $salon->id) {
            return response()->json(['message' => 'کارکن یافت نشد'], 404);
        }

        $pendingTransactionIds = StaffCommissionTransaction::where('staff_id', $staff->id)
            ->where('salon_id', $salon->id)
            ->where('payment_status', StaffCommissionTransaction::STATUS_PENDING)
            ->pluck('id')
            ->toArray();

        if (empty($pendingTransactionIds)) {
            return response()->json([
                'success' => false,
                'message' => 'تراکنش منتظر پرداختی وجود ندارد'
            ], 400);
        }

        $result = $this->commissionService->bulkSettleTransactions(
            $salon->id,
            $staff->id,
            $pendingTransactionIds,
            auth()->id()
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * دریافت نام ماه شمسی
     */
    protected function getJalaliMonthName(int $month): string
    {
        $months = [
            1 => 'فروردین',
            2 => 'اردیبهشت',
            3 => 'خرداد',
            4 => 'تیر',
            5 => 'مرداد',
            6 => 'شهریور',
            7 => 'مهر',
            8 => 'آبان',
            9 => 'آذر',
            10 => 'دی',
            11 => 'بهمن',
            12 => 'اسفند',
        ];

        return $months[$month] ?? '';
    }
}
