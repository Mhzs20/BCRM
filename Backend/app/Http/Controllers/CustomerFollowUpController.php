<?php
namespace App\Http\Controllers;

use App\Models\CustomerFollowUpSetting;
use App\Models\CustomerFollowUpGroupSetting;
use App\Models\CustomerFollowUpServiceSetting;
use App\Models\CustomerFollowUpHistory;
use App\Models\CustomerGroup;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Salon;
use App\Models\Service;
use App\Models\ManualFollowupPreparation;
use App\Models\SalonSmsBalance;
use App\Models\SmsTransaction;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Hekmatinasser\Verta\Verta;

class CustomerFollowUpController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    // 1. Get Customer Follow-up Statistics
    public function stats(Salon $salon)
    {
        $salonId = $salon->id;
        $totalGroups = CustomerGroup::where('salon_id', $salonId)->count();
        
        $activeGroups = CustomerFollowUpGroupSetting::whereHas('customerFollowUpSetting', function($q) use ($salonId) {
            $q->where('salon_id', $salonId);
        })->where('is_active', true)->count();
        
        $pendingReminders = CustomerFollowUpGroupSetting::where('is_active', true)
            ->whereHas('customerFollowUpSetting', function($q) use ($salonId) {
                $q->where('salon_id', $salonId);
            })->count();
        
        // پیام‌های ارسال شده امروز
        $messagesSentToday = CustomerFollowUpHistory::where('salon_id', $salonId)
            ->whereDate('sent_at', Carbon::today())
            ->count();
        
        $coverage = $totalGroups ? round(($activeGroups / $totalGroups) * 100, 2) : 0;
        
        return response()->json([
            'total_groups' => $totalGroups,
            'active_groups' => $activeGroups,
            'pending_reminders' => $pendingReminders,
            'messages_sent_today' => $messagesSentToday,
            'coverage_percentage' => $coverage,
        ]);
    }

    // 2. Get All Customer Groups with Follow-up Settings
    public function groups(Request $request, Salon $salon)
    {
        $salonId = $salon->id;
        $search = $request->get('search');
        $reminder_status = $request->get('reminder_status');
        $sort_by = $request->get('sort_by', 'name');
        
        $query = CustomerGroup::where('salon_id', $salonId);
        
        if ($search) {
            $query->where('name', 'like', "%$search%");
        }
        
        if ($reminder_status !== null) {
            $query->whereHas('customerFollowUpSettings', function($q) use ($reminder_status) {
                $q->where('is_active', $reminder_status === 'active');
            });
        }
        
        if ($sort_by === 'name') {
            $query->orderBy('name');
        }
        
        $groups = $query->with(['customerFollowUpSettings'])
            ->withCount('customers')
            ->get();
        
        // اضافه کردن آیتم "همه موارد" با ID صفر
        $allOption = [
            'id' => 0,
            'name' => 'همه گروه‌ها',
            'salon_id' => $salonId,
            'customers_count' => Customer::where('salon_id', $salonId)->count(),
        ];
        
        $groups->prepend($allOption);
        
        return response()->json($groups);
    }

    // 2.1. Get All Services for Customer Follow-up
    public function services(Request $request, Salon $salon)
    {
        $salonId = $salon->id;
        $search = $request->get('search');
        $sort_by = $request->get('sort_by', 'name');
        
        $query = Service::where('salon_id', $salonId)
            ->where('is_active', true);
        
        if ($search) {
            $query->where('name', 'like', "%$search%");
        }
        
        if ($sort_by === 'name') {
            $query->orderBy('name');
        } elseif ($sort_by === 'price') {
            $query->orderBy('price');
        }
        
        $services = $query->select('id', 'name', 'price', 'duration_minutes')->get();
        
        // اضافه کردن آیتم "همه موارد" با ID صفر
        $allOption = [
            'id' => 0,
            'name' => 'همه خدمات',
            'price' => null,
            'duration_minutes' => null,
        ];
        
        $services->prepend($allOption);
        
        return response()->json([
            'message' => 'خدمات با موفقیت دریافت شدند.',
            'services' => $services
        ]);
    }

    // 3. Get Available SMS Templates for Customer Follow-up
    public function templates(Request $request)
    {
        $category = \App\Models\SmsTemplateCategory::whereNull('salon_id')
            ->where('name', 'پیگیری مشتری')
            ->first();

        if (!$category) {
            return response()->json([
                'message' => 'دسته‌بندی پیگیری مشتری یافت نشد.',
                'templates' => []
            ], 404);
        }

        $templates = \App\Models\SalonSmsTemplate::where('category_id', $category->id)
            ->whereNull('salon_id')
            ->where('is_active', true)
            ->get();

        return response()->json([
            'message' => 'قالب‌ها با موفقیت دریافت شدند.',
            'templates' => $templates
        ]);
    }

    // 4. Get Summary of Customer Follow-up Settings
    public function summary(Salon $salon)
    {
        $salonId = $salon->id;
        $setting = CustomerFollowUpSetting::where('salon_id', $salonId)
            ->with([
                'groupSettings.customerGroup',
                'serviceSettings.service'
            ])
            ->first();
        
        return response()->json($setting);
    }

    // 5. Update Customer Follow-up Settings (برای Automated)
    public function updateSettings(Request $request, Salon $salon)
    {
        $salonId = $salon->id;

        $data = $request->validate([
            'template_id' => 'required|exists:salon_sms_templates,id',
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'integer|min:0',
            'customer_group_ids' => 'nullable|array',
            'customer_group_ids.*' => 'integer|min:0',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'integer|min:0',
            'is_active' => 'required|boolean',
            'days_since_last_visit' => 'required|integer|min:1|max:365',
            'check_frequency_days' => 'required|integer|min:1|max:90',
        ]);

        // پشتیبانی از هر دو نام فیلد: group_ids و customer_group_ids
        if (!isset($data['group_ids']) && isset($data['customer_group_ids'])) {
            $data['group_ids'] = $data['customer_group_ids'];
        }

        // فیلتر کردن ID صفر (یعنی همه) - اگر 0 باشد، بدون فیلتر خاص عمل می‌کند
        $allGroups = isset($data['group_ids']) && in_array(0, $data['group_ids']);
        $allServices = isset($data['service_ids']) && in_array(0, $data['service_ids']);
        $data['group_ids'] = isset($data['group_ids']) ? array_values(array_filter($data['group_ids'], fn($id) => $id != 0)) : [];
        $data['service_ids'] = isset($data['service_ids']) ? array_values(array_filter($data['service_ids'], fn($id) => $id != 0)) : [];

        // اگر ID 0 (همه) انتخاب شده، همه گروه‌ها/خدمات سالن رو بگیر
        if ($allGroups && empty($data['group_ids'])) {
            $data['group_ids'] = CustomerGroup::where('salon_id', $salonId)->pluck('id')->toArray();
        }
        if ($allServices && empty($data['service_ids'])) {
            $data['service_ids'] = Service::where('salon_id', $salonId)->where('is_active', true)->pluck('id')->toArray();
        }
        
        // حداقل یکی از group_ids یا service_ids باید موجود باشد
        if (empty($data['group_ids']) && empty($data['service_ids'])) {
            return response()->json([
                'success' => false,
                'message' => 'حداقل یکی از گروه‌ها یا خدمات باید انتخاب شود.'
            ], 422);
        }
        
        // غیرفعال کردن موقت FK checks (به دلیل تفاوت engine جداول MyISAM/InnoDB)
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $setting = CustomerFollowUpSetting::firstOrCreate([
            'salon_id' => $salonId
        ], [
            'template_id' => $data['template_id'],
            'is_global_active' => true,
        ]);

        // Ensure global active is true when updating settings
        $setting->is_global_active = true;
        $setting->template_id = $data['template_id'];
        $setting->save();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $groupResults = [];
        $serviceResults = [];
        
        // Delete existing group settings that are not in the new list
        if (!empty($data['group_ids'])) {
            CustomerFollowUpGroupSetting::where('customer_followup_setting_id', $setting->id)
                ->whereNotIn('customer_group_id', $data['group_ids'])
                ->delete();
        } else {
            // If no groups selected, delete all group settings
            CustomerFollowUpGroupSetting::where('customer_followup_setting_id', $setting->id)
                ->delete();
        }
        
        // Apply the same settings to all selected groups
        if (!empty($data['group_ids'])) {
            foreach ($data['group_ids'] as $groupId) {
                $groupSetting = CustomerFollowUpGroupSetting::updateOrCreate([
                    'customer_followup_setting_id' => $setting->id,
                    'customer_group_id' => $groupId
                ], [
                    'is_active' => $data['is_active'],
                    'days_since_last_visit' => $data['days_since_last_visit'],
                    'check_frequency_days' => $data['check_frequency_days'],
                ]);
                
                $groupResults[] = [
                    'group_id' => $groupId,
                    'success' => true,
                    'settings' => [
                        'id' => $groupSetting->id,
                        'is_active' => $groupSetting->is_active,
                        'days_since_last_visit' => $groupSetting->days_since_last_visit,
                        'check_frequency_days' => $groupSetting->check_frequency_days,
                        'updated_at' => $groupSetting->updated_at,
                    ]
                ];
            }
        }
        
        // Delete existing service settings that are not in the new list
        if (!empty($data['service_ids'])) {
            CustomerFollowUpServiceSetting::where('customer_followup_setting_id', $setting->id)
                ->whereNotIn('service_id', $data['service_ids'])
                ->delete();
        } else {
            // If no services selected, delete all service settings
            CustomerFollowUpServiceSetting::where('customer_followup_setting_id', $setting->id)
                ->delete();
        }
        
        // Apply the same settings to all selected services
        if (!empty($data['service_ids'])) {
            foreach ($data['service_ids'] as $serviceId) {
                $serviceSetting = CustomerFollowUpServiceSetting::updateOrCreate([
                    'customer_followup_setting_id' => $setting->id,
                    'service_id' => $serviceId
                ], [
                    'is_active' => $data['is_active'],
                ]);
                
                $serviceResults[] = [
                    'service_id' => $serviceId,
                    'success' => true,
                    'settings' => [
                        'id' => $serviceSetting->id,
                        'is_active' => $serviceSetting->is_active,
                        'updated_at' => $serviceSetting->updated_at,
                    ]
                ];
            }
        }
        
        $response = [
            'success' => true,
            'message' => 'تنظیمات پیگیری مشتری با موفقیت به‌روزرسانی شد.',
            'setting' => [
                'template_id' => $setting->template_id,
                'is_active' => $data['is_active'],
                'days_since_last_visit' => $data['days_since_last_visit'],
                'check_frequency_days' => $data['check_frequency_days'],
            ],
            'groups' => $groupResults,
            'services' => $serviceResults
        ];
        
        return response()->json($response);
    }

    // 6. Toggle Individual Group Customer Follow-up
    public function toggleGroup(Request $request, Salon $salon, $groupId)
    {
        $salonId = $salon->id;
        $setting = CustomerFollowUpSetting::where('salon_id', $salonId)->firstOrFail();
        
        $groupSetting = CustomerFollowUpGroupSetting::where('customer_followup_setting_id', $setting->id)
            ->where('customer_group_id', $groupId)
            ->firstOrFail();
        
        $groupSetting->is_active = $request->input('is_active');
        $groupSetting->save();
        
        return response()->json(['success' => true]);
    }

    // 7. Enable/Disable Global Customer Follow-up System
    public function globalToggle(Request $request, Salon $salon)
    {
        $salonId = $salon->id;
        $setting = CustomerFollowUpSetting::where('salon_id', $salonId)->firstOrFail();
        
        $setting->is_global_active = $request->input('is_active');
        $setting->save();
        
        return response()->json(['success' => true]);
    }

    // 8. Delete Customer Follow-up Settings for a Group
    public function deleteGroupSettings(Salon $salon, $groupId)
    {
        $salonId = $salon->id;
        $setting = CustomerFollowUpSetting::where('salon_id', $salonId)->firstOrFail();
        
        CustomerFollowUpGroupSetting::where('customer_followup_setting_id', $setting->id)
            ->where('customer_group_id', $groupId)
            ->delete();
        
        return response()->json(['success' => true]);
    }

    // 9. Get Specific Group Settings
    public function groupSettings(Salon $salon, $groupId)
    {
        $salonId = $salon->id;
        $setting = CustomerFollowUpSetting::where('salon_id', $salonId)->firstOrFail();
        
        $groupSetting = CustomerFollowUpGroupSetting::where('customer_followup_setting_id', $setting->id)
            ->where('customer_group_id', $groupId)
            ->first();
        
        return response()->json($groupSetting);
    }

    // 10. Prepare Manual Follow-up (فیلتر مشتریان)
    public function prepareManualFollowup(Request $request, Salon $salon)
    {
        try {
            $salonId = $salon->id;

            $data = $request->validate([
                'customer_group_ids' => 'nullable|array',
                'customer_group_ids.*' => 'integer',
                'service_ids' => 'nullable|array',
                'service_ids.*' => 'integer',
                'days_since_last_visit' => 'required|integer|min:1|max:365',
            ]);

            // فیلتر مشتریان بر اساس شرایط
            $query = Customer::where('salon_id', $salonId);

            // فیلتر گروه مشتری (ID صفر یعنی همه)
            if (!empty($data['customer_group_ids']) && !in_array(0, $data['customer_group_ids'])) {
                $query->whereHas('groups', function($q) use ($data) {
                    $q->whereIn('customer_group_id', $data['customer_group_ids']);
                });
            }

            // پیدا کردن مشتریانی که آخرین ویزیتشون X روز پیش بوده
            $targetDate = Carbon::now()->subDays($data['days_since_last_visit']);
            
            $query->whereHas('appointments', function($q) use ($targetDate, $data) {
                $q->where('status', 'completed')
                  ->whereDate('appointment_date', '<=', $targetDate);
                  
                // فیلتر بر اساس سرویس‌ها (ID صفر یعنی همه)
                if (!empty($data['service_ids']) && !in_array(0, $data['service_ids'])) {
                    $q->whereHas('services', function($serviceQuery) use ($data) {
                        $serviceQuery->whereIn('service_id', $data['service_ids']);
                    });
                }
            });

            // اطمینان از اینکه آخرین نوبتشون قبل از تاریخ هدف بوده
            $eligibleCustomers = $query->get()->filter(function ($customer) use ($targetDate) {
                $lastAppointment = $customer->appointments()
                    ->where('status', 'completed')
                    ->orderBy('appointment_date', 'desc')
                    ->first();
                    
                return $lastAppointment && Carbon::parse($lastAppointment->appointment_date)->lte($targetDate);
            });

            $customerCount = $eligibleCustomers->count();
            $customerIds = $eligibleCustomers->pluck('id')->toArray();

            // ذخیره preparation برای استفاده در مرحله ارسال
            $preparation = ManualFollowupPreparation::create([
                'salon_id' => $salonId,
                'customer_group_ids' => $data['customer_group_ids'] ?? null,
                'service_ids' => $data['service_ids'] ?? null,
                'days_since_last_visit' => $data['days_since_last_visit'],
                'customer_ids' => $customerIds,
                'customer_count' => $customerCount,
                'expires_at' => Carbon::now()->addHours(24), // انقضا بعد از 24 ساعت
            ]);

            return response()->json([
                'success' => true,
                'prepare_id' => $preparation->id,
                'customer_count' => $customerCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in CustomerFollowUpController@prepareManualFollowup:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'خطا در آماده‌سازی پیگیری: ' . $e->getMessage()
            ], 500);
        }
    }

    // 11. Send Manual Follow-up SMS
    public function sendManualFollowup(Request $request, Salon $salon)
    {
        try {
            $salonId = $salon->id;

            $data = $request->validate([
                'prepare_id' => 'required|exists:manual_followup_preparations,id',
                'template_id' => 'required|exists:salon_sms_templates,id',
            ]);

            // دریافت preparation
            $preparation = ManualFollowupPreparation::findOrFail($data['prepare_id']);

            // بررسی انقضا
            if ($preparation->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'این آماده‌سازی منقضی شده است. لطفاً مجدداً آماده‌سازی کنید.'
                ], 400);
            }

            // بررسی salon_id
            if ($preparation->salon_id != $salonId) {
                return response()->json([
                    'success' => false,
                    'message' => 'دسترسی غیرمجاز به این آماده‌سازی.'
                ], 403);
            }
            $template = \App\Models\SalonSmsTemplate::findOrFail($data['template_id']);

            // بررسی موجودی شارژ سالن
            $salonBalance = SalonSmsBalance::where('salon_id', $salonId)->first();
            if (!$salonBalance) {
                return response()->json([
                    'success' => false,
                    'message' => 'موجودی پیامک برای این سالن یافت نشد.'
                ], 400);
            }

            $sentCount = 0;
            $failedCount = 0;
            $totalSmsCount = 0;

            foreach ($preparation->customer_ids as $customerId) {
                $customer = Customer::find($customerId);
                
                if (!$customer || $customer->salon_id != $salonId) {
                    $failedCount++;
                    continue;
                }

                try {
                    // ارسال پیامک
                    $result = $this->smsService->sendTemplateNow(
                        $salon,
                        $template,
                        $customer->phone_number,
                        [
                            'customer_name' => $customer->name,
                            'salon_name' => $salon->name,
                        ],
                        $customer->id
                    );

                    // تولید پیام برای ذخیره در تاریخچه
                    $message = $this->smsService->compileTemplateText(
                        $template->template,
                        [
                            'customer_name' => $customer->name,
                            'salon_name' => $salon->name,
                        ]
                    );

                    if (isset($result['status']) && $result['status'] === 'success') {
                        $sentCount++;
                        $smsUsed = $this->smsService->calculateSmsParts($message);
                        $totalSmsCount += $smsUsed;
                        
                        // کسر از موجودی شارژ
                        $salonBalance->decrement('balance', $smsUsed);
                        
                        // ثبت تراکنش
                        SmsTransaction::create([
                            'salon_id' => $salonId,
                            'amount' => $smsUsed,
                            'type' => 'debit',
                            'sms_type' => 'customer_followup',
                            'description' => 'پیگیری دستی مشتری - ' . $customer->phone_number,
                            'balance_after' => $salonBalance->balance,
                            'sent_at' => Carbon::now(),
                        ]);
                        
                        // ثبت در تاریخچه - با استفاده از یک connection واحد تا SET FOREIGN_KEY_CHECKS و INSERT حتماً روی یک session باشند
                        try {
                            $historyData = [
                                'salon_id' => $salonId,
                                'customer_id' => $customerId,
                                'template_id' => $template->id,
                                'message' => $message,
                                'sent_at' => Carbon::now(),
                                'type' => 'manual',
                                'customer_group_ids' => is_array($preparation->customer_group_ids) ? json_encode($preparation->customer_group_ids) : $preparation->customer_group_ids,
                                'service_ids' => is_array($preparation->service_ids) ? json_encode($preparation->service_ids) : $preparation->service_ids,
                                'total_customers' => $preparation->customer_count,
                                'sms_count' => $smsUsed,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now(),
                            ];

                            // استفاده از connection واحد برای اطمینان از اجرای FK_CHECKS و INSERT روی یک session
                            $connection = DB::connection();
                            $connection->statement('SET FOREIGN_KEY_CHECKS=0');
                            $connection->table('customer_followup_histories')->insert($historyData);
                            $connection->statement('SET FOREIGN_KEY_CHECKS=1');

                            Log::info('Manual followup history created', [
                                'salon_id' => $salonId,
                                'customer_id' => $customerId,
                                'sent_at' => $historyData['sent_at'],
                            ]);
                        } catch (\Exception $historyException) {
                            // اطمینان از برگرداندن FK_CHECKS به حالت عادی
                            try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Exception $e) {}
                            
                            Log::error('Failed to create followup history:', [
                                'salon_id' => $salonId,
                                'customer_id' => $customerId,
                                'error' => $historyException->getMessage(),
                                'trace' => $historyException->getTraceAsString(),
                            ]);
                        }
                    } else {
                        $failedCount++;
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send followup SMS:', [
                        'customer_id' => $customerId,
                        'error' => $e->getMessage()
                    ]);
                    $failedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'پیامک‌های پیگیری ارسال شدند.',
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'total_sms_used' => $totalSmsCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in CustomerFollowUpController@sendManualFollowup:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال پیامک‌های پیگیری: ' . $e->getMessage()
            ], 500);
        }
    }

    // 12. Get Follow-up History
    public function history(Request $request, Salon $salon)
    {
        $salonId = $salon->id;
        $perPage = $request->get('per_page', 15);
        $type = $request->get('type'); // manual or automatic
        $startDateRaw = $request->get('start_date', $request->get('from_date'));
        $endDateRaw = $request->get('end_date', $request->get('to_date'));
        $startDate = $this->parseHistoryDate($startDateRaw, false);
        $endDate = $this->parseHistoryDate($endDateRaw, true);

        $query = CustomerFollowUpHistory::where('salon_id', $salonId)
            ->with(['customer', 'template'])
            ->orderBy('sent_at', 'desc');

        if ($type) {
            $query->where('type', strtolower(trim((string) $type)));
        }

        if ($startDate) {
            $query->where('sent_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('sent_at', '<=', $endDate);
        }

        // Log query details for debugging
        Log::info('Customer follow-up history query', [
            'salon_id' => $salonId,
            'type' => $type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_count' => CustomerFollowUpHistory::where('salon_id', $salonId)->count(),
            'filtered_count' => $query->count(),
        ]);

        $history = $query->paginate($perPage);

        // اضافه کردن اطلاعات گروه‌ها و خدمات به هر رکورد
        $history->getCollection()->transform(function ($item) {
            $groups = [];
            $services = [];

            if ($item->customer_group_ids) {
                $groups = CustomerGroup::whereIn('id', $item->customer_group_ids)
                    ->select('id', 'name')
                    ->get();
            }

            if ($item->service_ids) {
                $services = \App\Models\Service::whereIn('id', $item->service_ids)
                    ->select('id', 'name')
                    ->get();
            }

            $item->customer_groups = $groups;
            $item->services = $services;

            return $item;
        });

        return response()->json($history);
    }

    /**
     * Parse history date filters from either Gregorian or Jalali input.
     */
    private function parseHistoryDate($value, bool $endOfDay = false): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        $value = trim((string) $value);

        try {
            $normalized = str_replace('/', '-', $value);

            // If year looks Jalali (1300-1600), parse through Verta.
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $normalized, $matches)) {
                $year = (int) $matches[1];
                if ($year >= 1300 && $year <= 1600) {
                    $date = Verta::parse(str_replace('-', '/', $normalized))->toCarbon();
                    return $endOfDay ? $date->endOfDay() : $date->startOfDay();
                }
            }

            $date = Carbon::parse($normalized);
            return $endOfDay ? $date->endOfDay() : $date->startOfDay();
        } catch (\Exception $e) {
            return null;
        }
    }

    // Helper: تولید پیام نمونه
    private function generateSampleMessage($template, $salon)
    {
        $sampleCustomerName = 'مشتری گرامی';
        $message = str_replace('{{customer_name}}', $sampleCustomerName, $template);
        $message = str_replace('{{salon_name}}', $salon->name, $message);
        return $message;
    }

    // Helper: تولید پیام شخصی‌سازی شده
    private function generatePersonalizedMessage($template, $customer, $salon)
    {
        $message = str_replace('{{customer_name}}', $customer->name, $template);
        $message = str_replace('{{salon_name}}', $salon->name, $message);
        return $message;
    }
}
