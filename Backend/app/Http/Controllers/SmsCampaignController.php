<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterSmsCampaignRequest;
use App\Http\Resources\SmsCampaignResource;
use App\Jobs\SendSmsCampaign;
use App\Models\Salon;
use App\Models\SmsCampaign;
use App\Models\SalonSmsTemplate;
use App\Models\Profession;
use App\Models\CustomerGroup;
use App\Models\HowIntroduced;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class SmsCampaignController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Display the admin approval page for SMS campaigns
     */
    public function index()
    {
        $pendingCampaigns = SmsCampaign::with(['salon', 'user'])
            ->where('approval_status', 'pending')
            ->where('uses_template', false)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Enhance filters with complete object data for each campaign
        foreach ($pendingCampaigns as $campaign) {
            if ($campaign->filters) {
                $filters = is_string($campaign->filters) ? json_decode($campaign->filters, true) : $campaign->filters;
                if ($filters) {
                    $enhancedFilters = $this->enhanceFiltersWithObjects($filters, $campaign->salon);
                    $campaign->filters = $enhancedFilters;
                }
            }
        }

        return view('admin.sms-campaign-approval.index', compact('pendingCampaigns'));
    }

    public function prepareCampaign(FilterSmsCampaignRequest $request, Salon $salon)
    {
        Gate::authorize('manageResources', $salon);

         if ($request->filled('part_of_name')) {
            $namePart = $request->input('part_of_name');
            $customers = $salon->customers()->where('sms_opt_out', false)->where('name', 'like', "%{$namePart}%")->get();
        } else {
            $query = $this->buildFilteredQuery($request, $salon);
            $customers = $query->get();
        }
        $customerCount = $customers->count();
        $totalCustomers = $salon->customers()->where('sms_opt_out', false)->count();

        if ($customerCount === 0) {
            return response()->json([
                'message' => 'هیچ مشتری‌ای با فیلترهای انتخابی یافت نشد.',
                'customers' => [],
                'total_customers_in_salon' => $totalCustomers,
                'filters_applied' => $request->only([
                    'part_of_name', 'satisfaction', 'gender', 'min_age', 'max_age', 
                    'profession_id', 'customer_group_id', 'how_introduced_id', 
                    'min_appointments', 'max_appointments', 'min_payment', 'max_payment', 
                    'customer_created_from', 'last_visit_from', 'sms_template_id'
                ]),
                'error_type' => 'no_customers'
            ], 422);
        }

        $usesTemplate = false;
        $smsTemplateId = null;
        $messageToSave = '';

        // اگر template انتخاب شده باشد، از template استفاده کن
        if ($request->has('sms_template_id')) {
            $template = SalonSmsTemplate::find($request->input('sms_template_id'));
            if ($template) {
                $usesTemplate = true;
                $smsTemplateId = $template->id;
                $messageToSave = $template->template ?? '';
            }
        }

        $campaign = SmsCampaign::create([
            'salon_id' => $salon->id,
            'user_id' => Auth::id(),
            'filters' => json_encode($request->only([
                'part_of_name', 'satisfaction', 'gender', 'min_age', 'max_age', 
                'profession_id', 'customer_group_id', 'how_introduced_id', 
                'min_appointments', 'max_appointments', 'min_payment', 'max_payment', 
                'customer_created_from', 'last_visit_from', 'sms_template_id'
            ]), JSON_UNESCAPED_UNICODE),
            'message' => $messageToSave,
            'customer_count' => $customerCount,
            'total_cost' => 0,
            'status' => 'draft',
            'approval_status' => $usesTemplate ? 'approved' : 'pending',
            'uses_template' => $usesTemplate,
            'sms_template_id' => $smsTemplateId,
        ]);

        $campaign->load(['salon', 'user']);
        $enhancedFilters = $this->enhanceFiltersWithObjects($request->validated(), $salon);
        $campaign->filters = $enhancedFilters;

        return response()->json([
            'campaign' => new SmsCampaignResource($campaign),
            'customers' => $customerCount, // Just return the count, not the full list
        ]);
    }

    public function sendCampaign(Request $request, Salon $salon, SmsCampaign $campaign): JsonResponse
    {
        Gate::authorize('manageResources', $salon);

        if ($campaign->salon_id !== $salon->id) {
            return response()->json(['message' => 'این کمپین به این سالن تعلق ندارد.'], 403);
        }
        
        if ($campaign->status !== 'draft') {
            return response()->json(['message' => 'این کمپین قبلاً ارسال شده یا در حال پردازش است.'], 422);
        }

        // Require message in submit stage and persist it immediately so admin sees it
        $request->validate([
            'message' => 'required|string|max:1000',
            'send_to_owner' => 'sometimes|boolean',
        ]);

        // Update campaign message and send_to_owner right away (prevents empty message in admin panel)
        $campaign->message = $request->input('message');
        $campaign->send_to_owner = $request->boolean('send_to_owner', false); // store flag in campaign
        $campaign->save();

        $sendToOwner = $campaign->send_to_owner;
        // prepare placeholders so we can return counts after transaction
        $calculatedParts = 0;
        $calculatedMonetaryCost = 0;

        try {
            DB::transaction(function () use ($salon, $campaign, $request, $sendToOwner, &$calculatedParts, &$calculatedMonetaryCost) {
                // Re-run & lock relevant data
                $filters = is_string($campaign->filters) ? json_decode($campaign->filters, true) : $campaign->filters;
                $filters = $filters ?: [];
                $customers = $this->buildFilteredQuery(new Request($filters), $campaign->salon)->get();

                 $deselectIds = $request->input('deselect_ids', []);
                if (!empty($deselectIds)) {
                    $customers = $customers->whereNotIn('id', $deselectIds);
                }

                // Build personalized messages
                $messagesToInsert = $customers->map(function ($customer) use ($campaign) {
                    $finalMessage = str_replace('{customer_name}', $customer->name, $campaign->message);
                    return [
                        'sms_campaign_id' => $campaign->id,
                        'customer_id' => $customer->id,
                        'phone_number' => $customer->phone_number,
                        'message' => $finalMessage,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                });

                Log::info("Campaign {$campaign->id}: Prepared to send to {$customers->count()} customers (after deselect). Total messages: {$messagesToInsert->count()}");

                // Calculate parts and cost
                $totalParts = $messagesToInsert->sum(function ($row) {
                    return $this->smsService->calculateSmsCount($row['message']);
                });
                // Include owner message parts if owner should receive a copy
                $ownerExtraParts = 0;
                $sendToOwner = $request->boolean('send_to_owner', false);
                if ($sendToOwner) {
                    $ownerPhone = $salon->mobile ?? $salon->phone;
                    if ($ownerPhone) {
                        $ownerMessage = $campaign->message;
                        $ownerExtraParts = $this->smsService->calculateSmsCount($ownerMessage);
                    }
                }

                $requiredParts = $totalParts + $ownerExtraParts;
                $costPerPart = $this->smsService->getSmsCostPerPart();
                $totalMonetaryCost = (int)ceil($requiredParts * $costPerPart);

                // store calculated figures so we can return them to client
                $calculatedParts = $requiredParts;
                $calculatedMonetaryCost = $totalMonetaryCost;

                // Lock & check balance (balance stores SMS parts)
                $balance = $salon->smsBalance()->lockForUpdate()->first();
                if (!$balance || $balance->balance < $requiredParts) {
                    throw new \Exception('اعتبار پیامک کافی نیست. اعتبار فعلی: ' . ($balance?->balance ?? 0) . '، مورد نیاز: ' . $requiredParts);
                }

                // Deduct full recalculated parts
                if ($requiredParts > 0) {
                    $balance->decrement('balance', $requiredParts);
                }

                // Update campaign stats atomically (store parts count)
                $campaign->update([
                    'status' => 'pending',
                    'total_cost' => $requiredParts, // تعداد پارت‌ها به‌جای هزینه
                    'customer_count' => $customers->count(),
                ]);

                if ($messagesToInsert->isNotEmpty()) {
                    $campaign->messages()->insert($messagesToInsert->toArray());
                }

                // Do NOT send SMS to salon owner here; owner copy should be sent
                // only when the campaign is actually queued/sent (after approval).
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // Only dispatch the job if the campaign is using a template (auto-approved) or already approved
        if ($campaign->uses_template || $campaign->approval_status === 'approved') {
            try {
                    SendSmsCampaign::dispatch($campaign)->onQueue('sms');
                $message = "کمپین پیامکی برای {$campaign->customer_count} مشتری در صف ارسال قرار گرفت.";
            } catch (\Exception $e) {
                Log::error("Failed to send campaign #{$campaign->id}: " . $e->getMessage());
                $message = "خطا در ارسال کمپین: " . $e->getMessage();
            }
        } else {
            $message = "کمپین پیامکی برای {$campaign->customer_count} مشتری ایجاد شد و به ادمین برای تایید ارسال شده است.";
        }

        // If owner requested a copy, only send it now when campaign is actually
        // approved/queued (so owner does not get the message while campaign is pending).
        if ($sendToOwner && ($campaign->uses_template || $campaign->approval_status === 'approved')) {
            $ownerPhone = $salon->mobile ?? $salon->phone;
            if ($ownerPhone) {
                try {
                    app(\App\Services\SmsService::class)->sendSms($ownerPhone, $campaign->message);
                } catch (\Exception $e) {
                    Log::error("Failed to send owner copy for campaign #{$campaign->id}: " . $e->getMessage());
                }
            }
        }

        // reload campaign to ensure latest fields
        $campaign->refresh();
        $remainingBalance = $salon->fresh()->smsBalance->balance ?? 0;

        return response()->json([
            'message' => $message,
            'campaign_id' => $campaign->id,
            'requires_approval' => !$campaign->uses_template,
            'approval_status' => $campaign->approval_status,
            'sent_to_owner' => $sendToOwner,
            'customer_count' => $campaign->customer_count,
            'total_parts' => $calculatedParts,
            'total_cost' => $calculatedParts, 
            'remaining_balance_parts' => $remainingBalance,
        ]);
    }

    private function buildFilteredQuery(Request $request, Salon $salon)
    {
        $query = $salon->customers()->where('sms_opt_out', false);
        if ($request->filled('service_ids')) {
            $serviceIds = $request->input('service_ids');
            if (is_array($serviceIds) && count($serviceIds)) {
                $query->whereIn('id', function($q) use ($salon, $serviceIds) {
                    $q->select('customer_id')
                      ->from('appointments')
                      ->join('appointment_service', 'appointments.id', '=', 'appointment_service.appointment_id')
                      ->where('appointments.salon_id', $salon->id)
                      ->where('appointments.status', 'completed')
                      ->whereIn('appointment_service.service_id', $serviceIds)
                      ->groupBy('customer_id');
                });
            }
        }

        // فیلتر بر اساس قسمتی از نام
        if ($request->filled('part_of_name')) {
            $namePart = $request->input('part_of_name');
            $query->where('name', 'like', "%{$namePart}%");
        }

        if ($request->filled('min_age') || $request->filled('max_age')) {
            $query->whereNotNull('birth_date');
            if ($request->filled('min_age')) {
                $max_date = Carbon::now()->subYears($request->input('min_age'))->format('Y-m-d');
                $query->where('birth_date', '<=', $max_date);
            }
            if ($request->filled('max_age')) {
                $min_date = Carbon::now()->subYears($request->input('max_age') + 1)->addDay()->format('Y-m-d');
                $query->where('birth_date', '>=', $min_date);
            }
        }

        if ($request->filled('profession_id')) {
            $query->whereHas('profession', function($q) use ($salon, $request) {
                $q->where('professions.salon_id', $salon->id)
                  ->whereIn('professions.id', $request->input('profession_id'));
            });
        }
        if ($request->filled('customer_group_id')) {
            $query->whereHas('customerGroups', function($q) use ($salon, $request) {
                $q->where('customer_groups.salon_id', $salon->id)
                  ->whereIn('customer_groups.id', $request->input('customer_group_id'));
            });
        }
        if ($request->filled('how_introduced_id')) {
            $query->whereHas('howIntroduced', function($q) use ($salon, $request) {
                $q->where('how_introduceds.salon_id', $salon->id)
                  ->whereIn('how_introduceds.id', $request->input('how_introduced_id'));
            });
        }

        if ($request->filled('min_appointments') || $request->filled('max_appointments')) {
            $minAppointments = $request->input('min_appointments', 0);
            $maxAppointments = $request->input('max_appointments', PHP_INT_MAX);
            
            $query->whereIn('id', function($q) use ($salon, $minAppointments, $maxAppointments, $request) {
                $q->select('customer_id')
                  ->from('appointments')
                  ->where('salon_id', $salon->id)
                  ->groupBy('customer_id')
                  ->havingRaw('COUNT(*) >= ?', [$minAppointments]);
                  
                if ($request->filled('max_appointments')) {
                    $q->havingRaw('COUNT(*) <= ?', [$maxAppointments]);
                }
            });
        }

        if ($request->filled('min_payment') || $request->filled('max_payment')) {
            // Calculate customer payments from completed appointments total_price
            if ($request->filled('min_payment')) {
                $minPayment = $request->input('min_payment');
                $query->whereIn('id', function($q) use ($salon, $minPayment) {
                    $q->select('customer_id')
                      ->from('appointments')
                      ->where('salon_id', $salon->id)
                      ->whereNotNull('total_price')
                      ->groupBy('customer_id')
                      ->havingRaw('SUM(total_price) >= ?', [$minPayment]);
                });
            }
            if ($request->filled('max_payment')) {
                $maxPayment = $request->input('max_payment');
                $query->whereIn('id', function($q) use ($salon, $maxPayment) {
                    $q->select('customer_id')
                      ->from('appointments')
                      ->where('salon_id', $salon->id)
                      ->whereNotNull('total_price')
                      ->groupBy('customer_id')
                      ->havingRaw('SUM(total_price) <= ?', [$maxPayment]);
                });
            }
        }

        if ($request->filled('customer_created_from')) {
            $query->where('created_at', '>=', $request->input('customer_created_from'));
        }

        if ($request->filled('last_visit_from')) {
            $query->whereIn('id', function($q) use ($salon, $request) {
                $q->select('customer_id')
                  ->from('appointments')
                  ->where('salon_id', $salon->id)
                  ->where('appointment_date', '>=', $request->input('last_visit_from'))
                  ->groupBy('customer_id');
            });
        }

        if ($request->filled('satisfaction')) {
            $satisfactionArr = $request->input('satisfaction');
            if (is_array($satisfactionArr) && count($satisfactionArr)) {
                $query->whereIn('id', function($q) use ($salon, $satisfactionArr) {
                    $q->select('customers.id')
                      ->from('customers')
                      ->join('appointments', 'customers.id', '=', 'appointments.customer_id')
                      ->join('customer_feedback', 'appointments.id', '=', 'customer_feedback.appointment_id')
                      ->where('appointments.salon_id', $salon->id)
                      ->groupBy('customers.id')
                      ->havingRaw('ROUND(AVG(customer_feedback.rating)) IN (' . implode(',', $satisfactionArr) . ')');
                });
            }
        }

        // فیلتر جنسیت
        if ($request->filled('gender')) {
            $gender = $request->input('gender');
            if (in_array($gender, ['male', 'female'])) {
                $query->where('gender', $gender);
            }
        }

        return $query;
    }

    private function enhanceFiltersWithObjects(array $filters, Salon $salon): array
    {
        $enhancedFilters = $filters;

        // Get profession objects if profession_id is provided
        if (isset($filters['profession_id']) && is_array($filters['profession_id'])) {
            $professions = Profession::where('salon_id', $salon->id)
                ->whereIn('id', $filters['profession_id'])
                ->select('id', 'name')
                ->get()
                ->toArray();
            $enhancedFilters['professions'] = $professions;
        }

        // Get customer group objects if customer_group_id is provided
        if (isset($filters['customer_group_id']) && is_array($filters['customer_group_id'])) {
            $customerGroups = CustomerGroup::where('salon_id', $salon->id)
                ->whereIn('id', $filters['customer_group_id'])
                ->select('id', 'name')
                ->get()
                ->toArray();
            $enhancedFilters['customer_groups'] = $customerGroups;
        }

        // Get how introduced objects if how_introduced_id is provided
        if (isset($filters['how_introduced_id']) && is_array($filters['how_introduced_id'])) {
            $howIntroduceds = HowIntroduced::where('salon_id', $salon->id)
                ->whereIn('id', $filters['how_introduced_id'])
                ->select('id', 'name')
                ->get()
                ->toArray();
            $enhancedFilters['how_introduceds'] = $howIntroduceds;
        }

        return $enhancedFilters;
    }

    /**
     * Get pending SMS campaigns for admin approval
     */
    public function getPendingCampaigns(Request $request): JsonResponse
    {
        $campaigns = SmsCampaign::with(['salon', 'user'])
            ->where('approval_status', 'pending')
            ->where('uses_template', false)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // اطمینان از نمایش پیام ادیت‌شده
        foreach ($campaigns as $campaign) {
            if ($campaign->message) {
                $campaign->message = $campaign->message;
            }
        }

        return response()->json($campaigns);
    }

    /**
     * Show the approval page for admin
     */
    public function showApprovalPage()
    {
        if (!Auth::user()->is_superadmin) {
            abort(403, 'اقدام غیرمجاز.');
        }

        return view('admin.sms-campaign-approval.index');
    }

    /**
     * Approve an SMS campaign
     */
    public function approveCampaign(Request $request, SmsCampaign $campaign): JsonResponse
    {
        if (!Auth::user()->is_superadmin) {
            return response()->json(['message' => 'اقدام غیرمجاز.'], 403);
        }

        if ($campaign->approval_status !== 'pending') {
            return response()->json(['message' => 'این کمپین قبلاً بررسی شده است.'], 422);
        }

        $request->validate([
            'edited_message' => 'nullable|string|max:1000',
        ]);

        $editedMessage = $request->input('edited_message');
        if ($editedMessage !== null) {
            $campaign->message = $editedMessage;
        }
        $campaign->approval_status = 'approved';
        $campaign->approved_by = Auth::id();
        $campaign->approved_at = now();
        $campaign->save();

        // Automatically trigger send process for approved campaigns
        if ($campaign->status === 'draft') {
            try {
                // Create a fake request with campaign data to reuse sendCampaign logic
                $filters = json_decode($campaign->filters, true) ?: [];
                $sendRequest = new Request($filters);

                // Use a separate method to avoid authorization issues in admin approval
                $this->processCampaignSending($campaign);

            } catch (\Exception $e) {
                Log::error("Failed to send approved campaign #{$campaign->id}: " . $e->getMessage() . ' | Stack: ' . $e->getTraceAsString());
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در ارسال کمپین: ' . $e->getMessage(),
                    'details' => $e->getTraceAsString(),
                ], 500);
            }
        }

        return response()->json(['success' => true, 'message' => 'کمپین با موفقیت تایید شد و در صف ارسال قرار گرفت.']);
    }

    /**
     * Process campaign sending without authorization checks (for admin approval)
     */
    private function processCampaignSending(SmsCampaign $campaign): void
    {
        if ($campaign->status !== 'draft') {
            throw new \Exception('این کمپین قبلاً ارسال شده یا در حال پردازش است.');
        }

        try {
            DB::transaction(function () use ($campaign) {
                // Re-run & lock relevant data
                $filters = json_decode($campaign->filters, true) ?: [];
                $customers = $this->buildFilteredQuery(new Request($filters), $campaign->salon)->get();

                // Build personalized messages
                $messagesToInsert = $customers->map(function ($customer) use ($campaign) {
                    $finalMessage = str_replace('{customer_name}', $customer->name, $campaign->message);
                    return [
                        'sms_campaign_id' => $campaign->id,
                        'customer_id' => $customer->id,
                        'phone_number' => $customer->phone_number,
                        'message' => $finalMessage,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                });

                Log::info("Admin approved campaign {$campaign->id}: Prepared to send to {$customers->count()} customers. Total messages: {$messagesToInsert->count()}");

                // Recalculate real total parts based on final personalized messages
                $totalParts = $messagesToInsert->sum(function ($row) {
                    return $this->smsService->calculateSmsCount($row['message']);
                });

                // Lock & check balance (balance stored as parts)
                $balance = $campaign->salon->smsBalance()->lockForUpdate()->first();
                if (!$balance || $balance->balance < $totalParts) {
                    throw new \Exception('اعتبار پیامک کافی نیست.');
                }

                // Deduct full recalculated parts (no prior deduction at draft stage)
                if ($totalParts > 0) {
                    $balance->decrement('balance', $totalParts);
                }

                $costPerPart = $this->smsService->getSmsCostPerPart();
                $totalMonetaryCost = (int)ceil($totalParts * $costPerPart);

                // Update campaign stats atomically (store parts count)
                $campaign->update([
                    'status' => 'pending',
                    'total_cost' => $totalParts, // تعداد پارت‌ها به‌جای هزینه
                    'customer_count' => $customers->count(),
                ]);

                if ($messagesToInsert->isNotEmpty()) {
                    $campaign->messages()->insert($messagesToInsert->toArray());
                }
            });
        } catch (\Exception $e) {
            Log::error("processCampaignSending failed for campaign #{$campaign->id}: " . $e->getMessage());
            throw $e;
        }

        // Always try to dispatch the job after transaction
        try {
            SendSmsCampaign::dispatch($campaign)->onQueue('sms');
            Log::info("SendSmsCampaign job dispatched for campaign #{$campaign->id} to sms queue.");
        } catch (\Exception $e) {
            Log::error("Failed to dispatch SendSmsCampaign for campaign #{$campaign->id}: " . $e->getMessage());
            throw $e;
        }

        // Send owner copy if requested (after dispatch)
        if ($campaign->send_to_owner) {
            $ownerPhone = $campaign->salon->mobile ?? $campaign->salon->phone;
            if ($ownerPhone) {
                try {
                    app(\App\Services\SmsService::class)->sendSms($ownerPhone, $campaign->message);
                    Log::info("Owner copy sent for campaign #{$campaign->id} to {$ownerPhone}.");
                } catch (\Exception $e) {
                    Log::error("Failed to send owner copy for campaign #{$campaign->id}: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Reject an SMS campaign
     */
    public function rejectCampaign(Request $request, SmsCampaign $campaign): JsonResponse
    {
        if (!Auth::user()->is_superadmin) {
            return response()->json(['message' => 'اقدام غیرمجاز.'], 403);
        }

        if ($campaign->approval_status !== 'pending') {
            return response()->json(['message' => 'این کمپین قبلاً بررسی شده است.'], 422);
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $campaign->update([
            'approval_status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        // Refund the deducted balance
        $salon = $campaign->salon;
        $salonSmsBalance = $salon->smsBalance()->firstOrCreate(['salon_id' => $salon->id], ['balance' => 0]);
        if ($salonSmsBalance && $campaign->total_cost > 0) {
            $salonSmsBalance->increment('balance', $campaign->total_cost);
        }

        return response()->json(['success' => true, 'message' => 'کمپین رد شد و اعتبار کسر شده بازگردانده شد.']);
    }

    /**
     * Update campaign content before approval
     */
    public function updateContent(Request $request, SmsCampaign $campaign): JsonResponse
    {
        if (!Auth::user()->is_superadmin) {
            return response()->json(['message' => 'اقدام غیرمجاز.'], 403);
        }

        if ($campaign->approval_status !== 'pending') {
            return response()->json(['message' => 'این کمپین قبلاً بررسی شده است.'], 422);
        }

        $request->validate([
            'edited_message' => 'required|string|max:1000',
        ]);

    $campaign->message = $request->input('edited_message');
    $campaign->save();

        return response()->json(['success' => true, 'message' => 'محتوای کمپین با موفقیت ویرایش شد.']);
    }

    /**
     * Get paginated customers for an existing SMS campaign
     */
    public function getCampaignPagination(Request $request, Salon $salon, SmsCampaign $campaign): JsonResponse
    {
        Gate::authorize('manageResources', $salon);

        if ($campaign->salon_id !== $salon->id) {
            return response()->json(['message' => 'این کمپین به این سالن تعلق ندارد.'], 403);
        }

        // Get filters from the campaign
        $filters = json_decode($campaign->filters, true) ?: [];

        // Override with pagination parameters from request
        $perPage = $request->input('per_page', 50);
        $page = $request->input('page', 1);

        // Create a request object with the campaign filters
        $filterRequest = new Request($filters);

        // Get paginated customers using the same filters
        $paginatedCustomers = $this->buildFilteredQuery($filterRequest, $salon)->paginate($perPage, ['*'], 'page', $page);

        $customerResource = \App\Http\Resources\CustomerSmsCampaignResource::collection($paginatedCustomers);

        $enhancedFilters = $this->enhanceFiltersWithObjects($filters, $salon);

        return response()->json([
            'campaign_id' => $campaign->id,
            'customers' => $customerResource,
            'total_customers' => $paginatedCustomers->total(),
            'current_page' => $paginatedCustomers->currentPage(),
            'per_page' => $paginatedCustomers->perPage(),
            'last_page' => $paginatedCustomers->lastPage(),
            'filters_applied' => $enhancedFilters,
        ]);
    }

    /**
     * Display reports page for SMS campaigns
     */
    public function reports()
    {
        $campaigns = SmsCampaign::with(['salon', 'user', 'approver', 'messages'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Enhance filters with complete object data for each campaign
        foreach ($campaigns as $campaign) {
            if ($campaign->filters) {
                $filters = is_string($campaign->filters) ? json_decode($campaign->filters, true) : $campaign->filters;
                if ($filters) {
                    $enhancedFilters = $this->enhanceFiltersWithObjects($filters, $campaign->salon);
                    $campaign->filters = $enhancedFilters;
                }
            }
        }

        return view('admin.sms-campaign-reports.index', compact('campaigns'));
    }
}
