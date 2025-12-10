<?php
namespace App\Http\Controllers;

use App\Models\BirthdayReminder;
use App\Models\BirthdayReminderCustomerGroup;
use App\Models\CustomerGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BirthdayReminderController extends Controller
{
     public function groupsSettingsDetailed($salonId)
    {
        $reminder = BirthdayReminder::where('salon_id', $salonId)->first();
        if (!$reminder) {
            return response()->json([ 'success' => false, 'error' => 'تنظیمات تولد یافت نشد.' ], 404);
        }
        $groups = BirthdayReminderCustomerGroup::where('birthday_reminder_id', $reminder->id)->get();
        $result = [];
        foreach ($groups as $group) {
            $result[$group->customer_group_id] = [
                'success' => true,
                'birthday_setting' => [
                    'salon_id' => $salonId,
                    'customer_group_id' => $group->customer_group_id,
                    'is_active' => $group->is_active,
                    'send_days_before' => $group->send_days_before,
                    'send_time' => $reminder->send_time,
                    'template_id' => $reminder->template_id,
                    'updated_at' => $group->updated_at,
                    'created_at' => $group->created_at,
                    'id' => $group->id
                ]
            ];
        }
        return response()->json($result);
    }
    // 1. Get Birthday Reminder Statistics
    public function stats($salonId)
    {
         $totalGroups = CustomerGroup::where('salon_id', $salonId)->count();
        $activeGroups = BirthdayReminderCustomerGroup::whereHas('birthdayReminder', function($q) use ($salonId) {
            $q->where('salon_id', $salonId);
        })->where('is_active', true)->count();
        $pendingReminders = BirthdayReminderCustomerGroup::where('is_active', true)->whereHas('birthdayReminder', function($q) use ($salonId) {
            $q->where('salon_id', $salonId);
        })->count();
        $messagesSentToday = 0;  
        $coverage = $totalGroups ? round(($activeGroups / $totalGroups) * 100, 2) : 0;
        return response()->json([
            'total_groups' => $totalGroups,
            'active_groups' => $activeGroups,
            'pending_reminders' => $pendingReminders,
            'messages_sent_today' => $messagesSentToday,
            'coverage_percentage' => $coverage,
        ]);
    }

     public function groups(Request $request, $salonId)
    {
        $search = $request->get('search');
        $reminder_status = $request->get('reminder_status');
        $sort_by = $request->get('sort_by', 'name');
        $query = CustomerGroup::where('salon_id', $salonId);
        if ($search) $query->where('name', 'like', "%$search%");
        if ($reminder_status !== null) {
            $query->whereHas('birthdayReminders', function($q) use ($reminder_status) {
                $q->where('is_active', $reminder_status === 'active');
            });
        }
        if ($sort_by === 'name') $query->orderBy('name');
         $groups = $query->with(['birthdayReminders'])->withCount('customers')->get();
        return response()->json($groups);
    }

     public function templates(Request $request)
    {
        $category = \App\Models\SmsTemplateCategory::whereNull('salon_id')
            ->where('name', 'تبریک تولد')
            ->first();

        if (!$category) {
            return response()->json([
                'message' => 'دسته‌بندی تبریک تولد یافت نشد.',
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

     public function summary($salonId)
    {
        $reminder = BirthdayReminder::where('salon_id', $salonId)->with('customerGroups')->first();
        return response()->json($reminder);
    }

     public function updateSettings(Request $request, $salonId)
    {
        $data = $request->all();
        $reminder = BirthdayReminder::firstOrCreate([
            'salon_id' => $salonId
        ], [
            'template_id' => $data['template_id'] ?? null,
            'is_global_active' => true,
            'send_days_before' => $data['send_days_before'] ?? null,
        ]);

        // Ensure global active is true when updating settings
        $reminder->is_global_active = true;

        if (isset($data['send_time'])) {
            $reminder->send_time = $data['send_time'];
        }
        if (isset($data['send_days_before'])) {
            $reminder->send_days_before = $data['send_days_before'];
        }
        $reminder->template_id = $data['template_id'] ?? $reminder->template_id;
        $reminder->save();

        $result = [];
        foreach ($data['customer_group_ids'] as $groupId => $settings) {
            $group = BirthdayReminderCustomerGroup::updateOrCreate([
                'birthday_reminder_id' => $reminder->id,
                'customer_group_id' => $groupId
            ], [
                'is_active' => $settings['is_active'],
                'send_days_before' => $settings['send_days_before'] ?? $reminder->send_days_before ?? 3,
                'send_time' => $reminder->send_time // Sync send_time with global setting
            ]);
            $result[$groupId] = [
                'success' => true,
                'birthday_setting' => [
                    'salon_id' => $salonId,
                    'customer_group_id' => $groupId,
                    'is_active' => $group->is_active,
                    'send_days_before' => $group->send_days_before,
                    'send_time' => $group->send_time,
                    'template_id' => $reminder->template_id,
                    'updated_at' => $group->updated_at,
                    'created_at' => $group->created_at,
                    'id' => $group->id
                ]
            ];
        }
        
        $response = [
            'success' => true,
            'send_days_before' => $reminder->send_days_before,
            'send_time' => $reminder->send_time,
            'template_id' => $reminder->template_id,
            'customer_group_ids' => $result
        ];
        return response()->json($response);
    }

     public function toggleGroup(Request $request, $salonId, $groupId)
    {
        $reminder = BirthdayReminder::where('salon_id', $salonId)->firstOrFail();
        $groupSetting = BirthdayReminderCustomerGroup::where('birthday_reminder_id', $reminder->id)
            ->where('customer_group_id', $groupId)->firstOrFail();
        $groupSetting->is_active = $request->input('is_active');
        $groupSetting->save();
        return response()->json(['success' => true]);
    }

    // 7. Enable/Disable Global Birthday Reminder System
    public function globalToggle(Request $request, $salonId)
    {
        $reminder = BirthdayReminder::where('salon_id', $salonId)->firstOrFail();
        $reminder->is_global_active = $request->input('is_active');
        $reminder->save();
        return response()->json(['success' => true]);
    }

    // 8. Delete Birthday Reminder Settings for a Group
    public function deleteGroupSettings($salonId, $groupId)
    {
        $reminder = BirthdayReminder::where('salon_id', $salonId)->firstOrFail();
        BirthdayReminderCustomerGroup::where('birthday_reminder_id', $reminder->id)
            ->where('customer_group_id', $groupId)->delete();
        return response()->json(['success' => true]);
    }

    // 9. Get Specific Group Settings
    public function groupSettings($salonId, $groupId)
    {
        $reminder = BirthdayReminder::where('salon_id', $salonId)->firstOrFail();
        $groupSetting = BirthdayReminderCustomerGroup::where('birthday_reminder_id', $reminder->id)
            ->where('customer_group_id', $groupId)->first();
        return response()->json($groupSetting);
    }
}
