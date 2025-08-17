<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterSmsCampaignRequest;
use App\Jobs\SendSmsCampaign;
use App\Models\Salon;
use App\Models\SmsCampaign;
use App\Models\SalonSmsTemplate;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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

    public function prepareCampaign(FilterSmsCampaignRequest $request, Salon $salon): JsonResponse
    {
        Gate::authorize('manageResources', $salon);

        $query = $this->buildFilteredQuery($request, $salon);
        
        // Use a subquery to get distinct customer IDs to avoid issues with COUNT and HAVING
        $customerIds = $query->pluck('customers.id');
        $customerCount = $customerIds->count();

        $message = $request->input('message', '');
        if ($request->has('sms_template_id')) {
            $template = SalonSmsTemplate::find($request->input('sms_template_id'));
            if ($template) {
                $message = $template->template;
            }
        }

        $smsPartsPerCustomer = $this->smsService->calculateSmsCount($message);
        $totalSmsParts = $customerCount * $smsPartsPerCustomer;

        $campaign = SmsCampaign::create([
            'salon_id' => $salon->id,
            'user_id' => Auth::id(),
            'filters' => $request->validated(),
            'message' => $message,
            'customer_count' => $customerCount,
            'total_cost' => $totalSmsParts,
            'status' => 'draft',
        ]);

        return response()->json($campaign);
    }

    public function sendCampaign(Request $request, SmsCampaign $campaign): JsonResponse
    {
        Gate::authorize('manageResources', $campaign->salon);

        if ($campaign->status !== 'draft') {
            return response()->json(['message' => 'این کمپین قبلاً ارسال شده یا در حال پردازش است.'], 422);
        }

        $user = $campaign->salon->user;
        $totalCost = $campaign->total_cost;

        try {
            DB::transaction(function () use ($user, $totalCost, $campaign) {
                $balance = $user->smsBalance()->lockForUpdate()->first();

                if (!$balance || $balance->balance < $totalCost) {
                    throw new \Exception('اعتبار پیامک کافی نیست.');
                }

                $balance->decrement('balance', $totalCost);

                $campaign->update(['status' => 'pending']);
                
                // Re-run the query to get the final list of customers
                $query = $this->buildFilteredQuery(new Request($campaign->filters), $campaign->salon);
                $customers = $query->get();

                $messagesToInsert = $customers->map(function ($customer) use ($campaign) {
                    // Basic placeholder replacement
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

                // Insert all message records at once for efficiency
                $campaign->messages()->insert($messagesToInsert->toArray());

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

    private function buildFilteredQuery(Request $request, Salon $salon): Builder
    {
        $query = $salon->customers()->distinct()->where('sms_opt_out', false);

        if ($request->filled('min_age') || $request->filled('max_age')) {
            $query->whereNotNull('date_of_birth');
            if ($request->filled('min_age')) {
                $max_date = Carbon::now()->subYears($request->input('min_age'))->format('Y-m-d');
                $query->where('date_of_birth', '<=', $max_date);
            }
            if ($request->filled('max_age')) {
                $min_date = Carbon::now()->subYears($request->input('max_age') + 1)->addDay()->format('Y-m-d');
                $query->where('date_of_birth', '>=', $min_date);
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
            $query->withCount(['appointments' => function ($q) {
                $q->where('status', 'completed');
            }]);
            if ($request->filled('min_appointments')) {
                $query->having('appointments_count', '>=', $request->input('min_appointments'));
            }
            if ($request->filled('max_appointments')) {
                $query->having('appointments_count', '<=', $request->input('max_appointments'));
            }
        }

        if ($request->filled('min_payment') || $request->filled('max_payment')) {
            $query->addSelect([
                'total_paid' => DB::table('payments_received')
                    ->selectRaw('sum(amount)')
                    ->whereColumn('customer_id', 'customers.id')
            ]);
            if ($request->filled('min_payment')) {
                $query->having('total_paid', '>=', $request->input('min_payment'));
            }
            if ($request->filled('max_payment')) {
                $query->having('total_paid', '<=', $request->input('max_payment'));
            }
        }

        return $query;
    }
}
