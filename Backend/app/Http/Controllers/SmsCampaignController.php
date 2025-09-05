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

class SmsCampaignController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function prepareCampaign(FilterSmsCampaignRequest $request, Salon $salon): SmsCampaignResource
    {
        Gate::authorize('manageResources', $salon);

        $query = $this->buildFilteredQuery($request, $salon);
        
        // Get customers directly instead of using pluck to avoid HAVING issues with distinct
        $customers = $query->get();
        $customerCount = $customers->count();

        $message = $request->input('message', '');
        if ($request->has('sms_template_id')) {
            $template = SalonSmsTemplate::find($request->input('sms_template_id'));
            if ($template) {
                $message = $template->template;
            }
        }

        // Calculate total SMS parts considering personalization (e.g. {customer_name}) length impact
        $totalSmsParts = $customers->sum(function ($customer) use ($message) {
            $finalMessage = str_replace('{customer_name}', $customer->name, $message);
            return $this->smsService->calculateSmsCount($finalMessage);
        });

        $campaign = SmsCampaign::create([
            'salon_id' => $salon->id,
            'user_id' => Auth::id(),
            'filters' => $request->validated(),
            'message' => $message,
            'customer_count' => $customerCount,
            'total_cost' => $totalSmsParts,
            'status' => 'draft',
        ]);

        // Load relationships for complete response
        $campaign->load(['salon', 'user']);

        // Enhance filters with complete object data
        $enhancedFilters = $this->enhanceFiltersWithObjects($request->validated(), $salon);
        $campaign->filters = $enhancedFilters;

        return new SmsCampaignResource($campaign);
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

        try {
            DB::transaction(function () use ($salon, $campaign) {
                // Re-run & lock relevant data
                $customers = $this->buildFilteredQuery(new Request($campaign->filters), $campaign->salon)->get();

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

        SendSmsCampaign::dispatch($campaign);

        return response()->json([
            'message' => "کمپین پیامکی برای {$campaign->customer_count} مشتری با موفقیت در صف ارسال قرار گرفت.",
            'campaign_id' => $campaign->id,
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
            $query->whereIn('profession_id', $request->input('profession_id'));
        }
        if ($request->filled('customer_group_id')) {
            $query->whereIn('customer_group_id', $request->input('customer_group_id'));
        }
        if ($request->filled('how_introduced_id')) {
            $query->whereIn('how_introduced_id', $request->input('how_introduced_id'));
        }

        if ($request->filled('min_appointments') || $request->filled('max_appointments')) {
            // Use subquery to filter customers based on appointment count
            $minAppointments = $request->input('min_appointments', 0);
            $maxAppointments = $request->input('max_appointments', PHP_INT_MAX);
            
            $query->whereHas('appointments', function ($appointmentQuery) use ($minAppointments, $maxAppointments) {
                $appointmentQuery->where('status', 'completed');
            }, '>=', $minAppointments);
            
            if ($request->filled('max_appointments')) {
                $query->whereHas('appointments', function ($appointmentQuery) {
                    $appointmentQuery->where('status', 'completed');
                }, '<=', $maxAppointments);
            }
        }

        if ($request->filled('min_payment') || $request->filled('max_payment')) {
            // For SQLite compatibility, we need to use a different approach
            if ($request->filled('min_payment')) {
                $minPayment = $request->input('min_payment');
                $query->whereIn('id', function($q) use ($salon, $minPayment) {
                    $q->select('customer_id')
                      ->from('payments_received')
                      ->where('salon_id', $salon->id)
                      ->groupBy('customer_id')
                      ->havingRaw('SUM(amount) >= ?', [$minPayment]);
                });
            }
            if ($request->filled('max_payment')) {
                $maxPayment = $request->input('max_payment');
                $query->whereIn('id', function($q) use ($salon, $maxPayment) {
                    $q->select('customer_id')
                      ->from('payments_received')
                      ->where('salon_id', $salon->id)
                      ->groupBy('customer_id')
                      ->havingRaw('SUM(amount) <= ?', [$maxPayment]);
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
}
