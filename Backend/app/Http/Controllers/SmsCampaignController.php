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

        $query = $this->buildFilteredQuery($request, $salon);
        $customers = $query->get();
        $customerCount = $customers->count();
        $totalCustomers = $salon->customers()->where('sms_opt_out', false)->count();

        if ($customerCount === 0) {
            return response()->json([
                'message' => 'هیچ مشتری‌ای با فیلترهای انتخابی یافت نشد.',
                'customers' => [],
                'total_customers_in_salon' => $totalCustomers,
                'filters_applied' => $request->only(['min_age', 'max_age', 'profession_id', 'customer_group_id', 'how_introduced_id', 'min_appointments', 'max_appointments']),
                'error_type' => 'no_customers'
            ], 422);
        }

        $message = $request->input('message', '');
        $usesTemplate = false;
        $smsTemplateId = null;

        if ($request->has('sms_template_id')) {
            $template = SalonSmsTemplate::find($request->input('sms_template_id'));
            if ($template) {
                $message = $template->template;
                $usesTemplate = true;
                $smsTemplateId = $template->id;
            }
        }

        $totalSmsParts = $customers->sum(function ($customer) use ($message) {
            $finalMessage = str_replace('{customer_name}', $customer->name, $message);
            return $this->smsService->calculateSmsCount($finalMessage);
        });

        $smsBalance = $salon->smsBalance()->first();
        if (!$smsBalance || $smsBalance->balance < $totalSmsParts) {
            $currentBalance = $smsBalance ? $smsBalance->balance : 0;
            return response()->json([
                'message' => 'اعتبار پیامک کافی نیست.',
                'required_sms_count' => $totalSmsParts,
                'current_balance' => $currentBalance,
                'customers' => (new \Illuminate\Support\Collection([])),
                'error_type' => 'insufficient_balance'
            ], 422);
        }

        $campaign = SmsCampaign::create([
            'salon_id' => $salon->id,
            'user_id' => Auth::id(),
            'filters' => json_encode($request->validated(), JSON_UNESCAPED_UNICODE),
            'message' => $message,
            'customer_count' => $customerCount,
            'total_cost' => $totalSmsParts,
            'status' => 'draft',
            'approval_status' => $usesTemplate ? 'approved' : 'pending',
            'uses_template' => $usesTemplate,
            'sms_template_id' => $smsTemplateId,
        ]);

        $campaign->load(['salon', 'user']);
        $enhancedFilters = $this->enhanceFiltersWithObjects($request->validated(), $salon);
        $campaign->filters = $enhancedFilters;

        // اضافه کردن لیست کامل مشتریان با اطلاعات خواسته‌شده
        $customerResource = \App\Http\Resources\CustomerSmsCampaignResource::collection($customers);

        return response()->json([
            'campaign' => new SmsCampaignResource($campaign),
            'customers' => $customerResource,
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

        // Check approval status for custom messages
        if (!$campaign->uses_template && $campaign->approval_status !== 'approved') {
            return response()->json([
                'message' => 'کمپین شما با موفقیت ثبت شد و اکنون در حال بررسی است. به‌محض تأیید، پیام‌ها ارسال خواهند شد..',
                'approval_status' => $campaign->approval_status
            ], 422);
        }
        try {
            DB::transaction(function () use ($salon, $campaign) {
                // Re-run & lock relevant data
                $filters = is_string($campaign->filters) ? json_decode($campaign->filters, true) : $campaign->filters;
                $filters = $filters ?: [];
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

                // Recalculate real total cost based on final personalized messages
                $newTotalCost = $messagesToInsert->sum(function ($row) {
                    return $this->smsService->calculateSmsCount($row['message']);
                });

                // Lock & check balance
                $balance = $salon->smsBalance()->lockForUpdate()->first();
                if (!$balance || $balance->balance < $newTotalCost) {
                    throw new \Exception('اعتبار پیامک کافی نیست.');
                }

                // Deduct full recalculated cost (no prior deduction at draft stage)
                if ($newTotalCost > 0) {
                    $balance->decrement('balance', $newTotalCost);
                }

                // Update campaign stats atomically
                $campaign->update([
                    'status' => 'pending',
                    'total_cost' => $newTotalCost,
                    'customer_count' => $customers->count(),
                ]);

                if ($messagesToInsert->isNotEmpty()) {
                    $campaign->messages()->insert($messagesToInsert->toArray());
                }
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // Only dispatch the job if the campaign is using a template (auto-approved) or already approved
        if ($campaign->uses_template || $campaign->approval_status === 'approved') {
            SendSmsCampaign::dispatch($campaign);
            $message = "کمپین پیامکی برای {$campaign->customer_count} مشتری با موفقیت در صف ارسال قرار گرفت.";
        } else {
            $message = "کمپین پیامکی برای {$campaign->customer_count} مشتری ایجاد شد و به ادمین برای تایید ارسال شده است.";
        }

        return response()->json([
            'message' => $message,
            'campaign_id' => $campaign->id,
            'requires_approval' => !$campaign->uses_template,
            'approval_status' => $campaign->approval_status,
        ]);
    }

    private function buildFilteredQuery(Request $request, Salon $salon)
    {
        $query = $salon->customers()->where('sms_opt_out', false);

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
                $q->where('salon_id', $salon->id)
                  ->whereIn('id', $request->input('profession_id'));
            });
        }
        if ($request->filled('customer_group_id')) {
            $query->whereHas('customerGroups', function($q) use ($salon, $request) {
                $q->where('salon_id', $salon->id)
                  ->whereIn('id', $request->input('customer_group_id'));
            });
        }
        if ($request->filled('how_introduced_id')) {
            $query->whereHas('howIntroduced', function($q) use ($salon, $request) {
                $q->where('salon_id', $salon->id)
                  ->whereIn('id', $request->input('how_introduced_id'));
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

            // Recalculate real total cost based on final personalized messages
            $newTotalCost = $messagesToInsert->sum(function ($row) {
                return $this->smsService->calculateSmsCount($row['message']);
            });

            // Lock & check balance
            $balance = $campaign->salon->smsBalance()->lockForUpdate()->first();
            if (!$balance || $balance->balance < $newTotalCost) {
                throw new \Exception('اعتبار پیامک کافی نیست.');
            }

            // Deduct full recalculated cost (no prior deduction at draft stage)
            if ($newTotalCost > 0) {
                $balance->decrement('balance', $newTotalCost);
            }

            // Update campaign stats atomically
            $campaign->update([
                'status' => 'pending',
                'total_cost' => $newTotalCost,
                'customer_count' => $customers->count(),
            ]);

            if ($messagesToInsert->isNotEmpty()) {
                $campaign->messages()->insert($messagesToInsert->toArray());
            }
        });

        // Dispatch the job for sending
        SendSmsCampaign::dispatch($campaign);
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

        return response()->json(['success' => true, 'message' => 'کمپین رد شد.']);
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
     * Get campaign status (for salon users to track their campaigns)
     */
    public function getCampaignStatus(Salon $salon, SmsCampaign $campaign): JsonResponse
    {
        Gate::authorize('manageResources', $salon);

        if ($campaign->salon_id !== $salon->id) {
            return response()->json(['message' => 'این کمپین به این سالن تعلق ندارد.'], 403);
        }

        $campaign->load(['approver']);

        return response()->json([
            'id' => $campaign->id,
            'status' => $campaign->status,
            'approval_status' => $campaign->approval_status,
            'uses_template' => $campaign->uses_template,
            'message' => $campaign->message,
            'customer_count' => $campaign->customer_count,
            'total_cost' => $campaign->total_cost,
            'approved_by' => $campaign->approver ? $campaign->approver->name : null,
            'approved_at' => $campaign->approved_at,
            'rejection_reason' => $campaign->rejection_reason,
            'created_at' => $campaign->created_at,
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
