<?php
namespace App\Http\Controllers;

use App\Models\Salon;
use App\Models\SatisfactionSurveySetting;
use App\Models\SatisfactionSurveyGroupSetting;
use App\Models\CustomerGroup;
use Illuminate\Http\Request;

class SatisfactionSurveyController extends Controller
{
    // 1. Get Satisfaction Survey Statistics
    public function stats(Salon $salon)
    {
        $salonId = $salon->id;
        $totalGroups = CustomerGroup::where('salon_id', $salonId)->count();
        
        $activeGroups = SatisfactionSurveyGroupSetting::whereHas('satisfactionSurveySetting', function($q) use ($salonId) {
            $q->where('salon_id', $salonId);
        })->where('is_active', true)->count();
        
        $pendingReminders = SatisfactionSurveyGroupSetting::where('is_active', true)
            ->whereHas('satisfactionSurveySetting', function($q) use ($salonId) {
                $q->where('salon_id', $salonId);
            })->count();
        
        $messagesSentToday = 0; // TODO: Implement actual count from sent messages
        
        $coverage = $totalGroups ? round(($activeGroups / $totalGroups) * 100, 2) : 0;
        
        return response()->json([
            'total_groups' => $totalGroups,
            'active_groups' => $activeGroups,
            'pending_reminders' => $pendingReminders,
            'messages_sent_today' => $messagesSentToday,
            'coverage_percentage' => $coverage,
        ]);
    }

    // 2. Get All Customer Groups with Satisfaction Survey Settings
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
            $query->whereHas('satisfactionSurveySettings', function($q) use ($reminder_status) {
                $q->where('is_active', $reminder_status === 'active');
            });
        }
        
        if ($sort_by === 'name') {
            $query->orderBy('name');
        }
        
        $groups = $query->with(['satisfactionSurveySettings'])
            ->withCount('customers')
            ->get();
        
        return response()->json($groups);
    }

    // 3. Get Available SMS Templates for Satisfaction Survey
    public function templates(Request $request)
    {
        $category = \App\Models\SmsTemplateCategory::whereNull('salon_id')
            ->where('name', 'رضایت‌سنجی')
            ->first();

        if (!$category) {
            return response()->json([
                'message' => 'دسته‌بندی رضایت‌سنجی یافت نشد.',
                'templates' => []
            ], 404);
        }

        $templates = \App\Models\SalonSmsTemplate::where('category_id', $category->id)
            ->whereNull('salon_id')
            ->where('is_active', true)
            ->get();

        // محاسبه و اضافه کردن estimated_parts, estimated_cost و variables برای هر قالب
        $templates = $templates->map(function($template) {
            // استخراج متغیرها از template
            preg_match_all('/\{\{?\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\}?\}/u', $template->template, $matches);
            $variables = array_unique($matches[1] ?? []);
            
            // محاسبه estimated_parts و estimated_cost
            $estimatedParts = $template->calculateEstimatedParts();
            $estimatedCost = (int)$template->calculateEstimatedCost();
            
            // اضافه کردن فیلدهای محاسبه شده
            $template->variables = $variables;
            $template->estimated_parts = $estimatedParts;
            $template->estimated_cost = $estimatedCost;
            
            return $template;
        });

        return response()->json([
            'message' => 'قالب‌ها با موفقیت دریافت شدند.',
            'templates' => $templates
        ]);
    }

    // 4. Get Summary of Satisfaction Survey Settings
    public function summary(Salon $salon)
    {
        $setting = SatisfactionSurveySetting::where('salon_id', $salon->id)
            ->with('groupSettings.customerGroup')
            ->first();
        
        return response()->json($setting);
    }

    // 5. Update Satisfaction Survey Settings
    public function updateSettings(Request $request, Salon $salon)
    {
        $salonId = $salon->id;
        $data = $request->all();
        
        $setting = SatisfactionSurveySetting::firstOrCreate([
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
                $groupSetting = SatisfactionSurveyGroupSetting::updateOrCreate([
                    'satisfaction_survey_setting_id' => $setting->id,
                    'customer_group_id' => $groupId
                ], [
                    'is_active' => $settings['is_active'] ?? true,
                    'send_hours_after' => $settings['send_hours_after'] ?? 2,
                ]);
                
                $result[$groupId] = [
                    'success' => true,
                    'satisfaction_setting' => [
                        'salon_id' => $salonId,
                        'customer_group_id' => $groupId,
                        'is_active' => $groupSetting->is_active,
                        'send_hours_after' => $groupSetting->send_hours_after,
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

    // 6. Toggle Individual Group Satisfaction Survey
    public function toggleGroup(Request $request, Salon $salon, $groupId)
    {
        $setting = SatisfactionSurveySetting::where('salon_id', $salon->id)->firstOrFail();
        
        $groupSetting = SatisfactionSurveyGroupSetting::where('satisfaction_survey_setting_id', $setting->id)
            ->where('customer_group_id', $groupId)
            ->firstOrFail();
        
        $groupSetting->is_active = $request->input('is_active');
        $groupSetting->save();
        
        return response()->json(['success' => true]);
    }

    // 7. Enable/Disable Global Satisfaction Survey System
    public function globalToggle(Request $request, Salon $salon)
    {
        $setting = SatisfactionSurveySetting::where('salon_id', $salon->id)->firstOrFail();
        
        $setting->is_global_active = $request->input('is_active');
        $setting->save();
        
        return response()->json(['success' => true]);
    }

    // 8. Delete Satisfaction Survey Settings for a Group
    public function deleteGroupSettings(Salon $salon, $groupId)
    {
        $setting = SatisfactionSurveySetting::where('salon_id', $salon->id)->firstOrFail();
        
        SatisfactionSurveyGroupSetting::where('satisfaction_survey_setting_id', $setting->id)
            ->where('customer_group_id', $groupId)
            ->delete();
        
        return response()->json(['success' => true]);
    }

    // 9. Get Specific Group Settings
    public function groupSettings(Salon $salon, $groupId)
    {
        $setting = SatisfactionSurveySetting::where('salon_id', $salon->id)->firstOrFail();
        
        $groupSetting = SatisfactionSurveyGroupSetting::where('satisfaction_survey_setting_id', $setting->id)
            ->where('customer_group_id', $groupId)
            ->first();
        
        return response()->json($groupSetting);
    }
}
