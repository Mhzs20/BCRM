<?php
namespace App\Http\Controllers;

use App\Models\CustomerFollowUpSetting;
use App\Models\CustomerFollowUpGroupSetting;
use App\Models\CustomerFollowUpHistory;
use App\Models\CustomerGroup;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Salon;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CustomerFollowUpController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    // 1. Get Customer Follow-up Statistics
    public function stats($salonId)
    {
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
    public function groups(Request $request, $salonId)
    {
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
        
        return response()->json($groups);
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
    public function summary($salonId)
    {
        $setting = CustomerFollowUpSetting::where('salon_id', $salonId)
            ->with('groupSettings.customerGroup')
            ->first();
        
        return response()->json($setting);
    }

    // 5. Update Customer Follow-up Settings (برای Automated)
    public function updateSettings(Request $request, $salonId)
    {
        $data = $request->all();
        
        $setting = CustomerFollowUpSetting::firstOrCreate([
            'salon_id' => $salonId
        ], [
            'template_id' => $data['template_id'] ?? null,
            'is_global_active' => true,
        ]);

        // Ensure global active is true when updating settings
        $setting->is_global_active = true;
        $setting->template_id = $data['template_id'] ?? $setting->template_id;
        $setting->save();

        $result = [];
        
        if (isset($data['customer_group_ids'])) {
            foreach ($data['customer_group_ids'] as $groupId => $settings) {
                $groupSetting = CustomerFollowUpGroupSetting::updateOrCreate([
                    'customer_followup_setting_id' => $setting->id,
                    'customer_group_id' => $groupId
                ], [
                    'is_active' => $settings['is_active'] ?? true,
                    'days_since_last_visit' => $settings['days_since_last_visit'] ?? 15,
                    'check_frequency_days' => $settings['check_frequency_days'] ?? 7,
                ]);
                
                $result[$groupId] = [
                    'success' => true,
                    'followup_setting' => [
                        'salon_id' => $salonId,
                        'customer_group_id' => $groupId,
                        'is_active' => $groupSetting->is_active,
                        'days_since_last_visit' => $groupSetting->days_since_last_visit,
                        'check_frequency_days' => $groupSetting->check_frequency_days,
                        'template_id' => $setting->template_id,
                        'updated_at' => $groupSetting->updated_at,
                        'created_at' => $groupSetting->created_at,
                        'id' => $groupSetting->id
                    ]
                ];
            }
        }
        
        $response = [
            'success' => true,
            'template_id' => $setting->template_id,
            'customer_group_ids' => $result
        ];
        
        return response()->json($response);
    }

    // 6. Toggle Individual Group Customer Follow-up
    public function toggleGroup(Request $request, $salonId, $groupId)
    {
        $setting = CustomerFollowUpSetting::where('salon_id', $salonId)->firstOrFail();
        
        $groupSetting = CustomerFollowUpGroupSetting::where('customer_followup_setting_id', $setting->id)
            ->where('customer_group_id', $groupId)
            ->firstOrFail();
        
        $groupSetting->is_active = $request->input('is_active');
        $groupSetting->save();
        
        return response()->json(['success' => true]);
    }

    // 7. Enable/Disable Global Customer Follow-up System
    public function globalToggle(Request $request, $salonId)
    {
        $setting = CustomerFollowUpSetting::where('salon_id', $salonId)->firstOrFail();
        
        $setting->is_global_active = $request->input('is_active');
        $setting->save();
        
        return response()->json(['success' => true]);
    }

    // 8. Delete Customer Follow-up Settings for a Group
    public function deleteGroupSettings($salonId, $groupId)
    {
        $setting = CustomerFollowUpSetting::where('salon_id', $salonId)->firstOrFail();
        
        CustomerFollowUpGroupSetting::where('customer_followup_setting_id', $setting->id)
            ->where('customer_group_id', $groupId)
            ->delete();
        
        return response()->json(['success' => true]);
    }

    // 9. Get Specific Group Settings
    public function groupSettings($salonId, $groupId)
    {
        $setting = CustomerFollowUpSetting::where('salon_id', $salonId)->firstOrFail();
        
        $groupSetting = CustomerFollowUpGroupSetting::where('customer_followup_setting_id', $setting->id)
            ->where('customer_group_id', $groupId)
            ->first();
        
        return response()->json($groupSetting);
    }

    // 10. Prepare Manual Follow-up (فیلتر و محاسبه هزینه)
    public function prepareManualFollowup(Request $request, $salonId)
    {
        try {
            $data = $request->validate([
                'customer_group_ids' => 'nullable|array',
                'customer_group_ids.*' => 'exists:customer_groups,id',
                'service_ids' => 'nullable|array',
                'service_ids.*' => 'exists:services,id',
                'days_since_last_visit' => 'required|integer|min:1|max:365',
                'template_id' => 'required|exists:salon_sms_templates,id',
            ]);

            $salon = Salon::findOrFail($salonId);

            // فیلتر مشتریان بر اساس شرایط
            $query = Customer::where('salon_id', $salonId);

            // فیلتر گروه مشتری
            if (!empty($data['customer_group_ids'])) {
                $query->whereHas('groups', function($q) use ($data) {
                    $q->whereIn('customer_group_id', $data['customer_group_ids']);
                });
            }

            // پیدا کردن مشتریانی که آخرین ویزیتشون X روز پیش بوده
            $targetDate = Carbon::now()->subDays($data['days_since_last_visit']);
            
            $query->whereHas('appointments', function($q) use ($targetDate, $data) {
                $q->where('status', 'completed')
                  ->whereDate('appointment_date', '<=', $targetDate);
                  
                // فیلتر بر اساس سرویس‌ها
                if (!empty($data['service_ids'])) {
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

            // دریافت تمپلیت برای محاسبه هزینه
            $template = \App\Models\SalonSmsTemplate::findOrFail($data['template_id']);
            
            // محاسبه طول تقریبی پیام
            $sampleMessage = $this->generateSampleMessage($template->template, $salon);
            $partsPerMessage = $this->smsService->calculateSmsParts($sampleMessage);
            $costPerPart = 250; // هزینه هر پارت (تومان)
            $totalCost = $customerCount * $partsPerMessage * $costPerPart;

            return response()->json([
                'success' => true,
                'customer_count' => $customerCount,
                'message_parts' => $partsPerMessage,
                'cost_per_message' => $partsPerMessage * $costPerPart,
                'total_cost' => $totalCost,
                'sample_message' => $sampleMessage,
                'customer_ids' => $eligibleCustomers->pluck('id')->toArray(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error in CustomerFollowUpController@prepareManualFollowup:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'خطا در آماده‌سازی پیگیری: ' . $e->getMessage()
            ], 500);
        }
    }

    // 11. Send Manual Follow-up SMS
    public function sendManualFollowup(Request $request, $salonId)
    {
        try {
            $data = $request->validate([
                'customer_ids' => 'required|array',
                'customer_ids.*' => 'exists:customers,id',
                'template_id' => 'required|exists:salon_sms_templates,id',
            ]);

            $salon = Salon::findOrFail($salonId);
            $template = \App\Models\SalonSmsTemplate::findOrFail($data['template_id']);

            $sentCount = 0;
            $failedCount = 0;

            foreach ($data['customer_ids'] as $customerId) {
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
                        
                        // ثبت در تاریخچه
                        CustomerFollowUpHistory::create([
                            'salon_id' => $salonId,
                            'customer_id' => $customerId,
                            'template_id' => $template->id,
                            'message' => $message,
                            'sent_at' => Carbon::now(),
                            'type' => 'manual',
                        ]);
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
    public function history(Request $request, $salonId)
    {
        $perPage = $request->get('per_page', 15);
        $type = $request->get('type'); // manual or automatic
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = CustomerFollowUpHistory::where('salon_id', $salonId)
            ->with(['customer', 'template'])
            ->orderBy('sent_at', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        if ($startDate) {
            $query->whereDate('sent_at', '>=', Carbon::parse($startDate));
        }

        if ($endDate) {
            $query->whereDate('sent_at', '<=', Carbon::parse($endDate));
        }

        $history = $query->paginate($perPage);

        return response()->json($history);
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
