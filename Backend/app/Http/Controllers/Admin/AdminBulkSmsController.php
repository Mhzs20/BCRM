<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Province;
use App\Models\Salon;
use App\Models\SmsTransaction;
use App\Services\BulkSmsFilterService;
use Illuminate\Http\Request;
use App\Models\BusinessCategory;
use App\Models\BusinessSubcategory;
use App\Jobs\SendBulkSmsJob;
use Illuminate\Support\Str;

class AdminBulkSmsController extends Controller
{
    public function index(Request $request)
    {
        $provinces = Province::all();
        $businessCategories = BusinessCategory::all();

        $cities = collect();
        if ($request->filled('province_id')) {
            $cities = City::where('province_id', $request->province_id)->get();
        }

        $businessSubcategories = collect();
        if ($request->filled('business_category_id')) {
            $businessSubcategories = BusinessSubcategory::where('category_id', $request->business_category_id)->get();
        }

        $filters = $this->extractFilters($request);

        $query = Salon::with(['owner', 'city', 'smsBalance', 'smsTransactions']);

        BulkSmsFilterService::apply($query, $filters);

        $salons = $query->paginate(10);

        return view('admin.bulk_sms.index', compact('salons', 'provinces', 'cities', 'businessCategories', 'businessSubcategories'));
    }

    public function sendSms(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
            'salon_ids' => 'nullable|array',
            'salon_ids.*' => 'exists:salons,id',
            'send_to_all' => 'nullable|boolean',
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|boolean',
            'province_id' => 'nullable|exists:provinces,id',
            'city_id' => 'nullable|exists:cities,id',
            'business_category_id' => 'nullable|exists:business_categories,id',
            'business_subcategory_id' => 'nullable|exists:business_subcategories,id',
            'sms_balance_status' => 'nullable|string|in:less_than_50,less_than_200,zero',
            'min_sms_balance' => 'nullable|integer|min:0',
            'max_sms_balance' => 'nullable|integer|min:0',
            'last_sms_purchase' => 'nullable|string|in:last_month,last_3_months,last_6_months,more_than_6_months,never',
            'monthly_sms_consumption' => 'nullable|string|in:high,medium,low',
            'gender' => 'nullable|in:male,female,other',
            'min_age' => 'nullable|integer|min:18|max:120',
            'max_age' => 'nullable|integer|min:18|max:120',
        ]);

        $filters = $this->extractFilters($request);

        $query = Salon::with(['owner', 'city', 'smsBalance', 'smsTransactions']);

        BulkSmsFilterService::apply($query, $filters);

        $sendToAll = $request->boolean('send_to_all');
        $selectedSalonIds = $sendToAll ? null : $request->input('salon_ids', []);

        if (!$sendToAll) {
            $selectedSalonIds = array_unique(array_filter($selectedSalonIds));
            if (empty($selectedSalonIds)) {
                return back()->with('error', 'هیچ سالنی برای ارسال پیامک انتخاب نشده است.');
            }

            $query->whereIn('id', $selectedSalonIds);
        }

        $targetCount = (clone $query)->count();

        if ($targetCount === 0) {
            return back()->with('error', 'هیچ سالنی با فیلترهای انتخابی برای ارسال پیامک یافت نشد.');
        }

        $batchId = (string) Str::uuid();

        SendBulkSmsJob::dispatch(
            $request->message,
            $filters,
            $selectedSalonIds,
            $sendToAll,
            auth()->id(),
            optional(auth()->user())->name,
            $batchId
        );

        return back()->with('success', "ارسال پیامک به {$targetCount} سالن در صف قرار گرفت. نتیجه را در تاریخچه‌ی ارسال پیامک‌ها بررسی کنید. شناسه دسته: {$batchId}");
    }

    private function extractFilters(Request $request): array
    {
        return $request->only([
            'search',
            'status',
            'province_id',
            'city_id',
            'business_category_id',
            'business_subcategory_id',
            'sms_balance_status',
            'min_sms_balance',
            'max_sms_balance',
            'last_sms_purchase',
            'monthly_sms_consumption',
            'gender',
            'min_age',
            'max_age',
        ]);
    }

    public function history(Request $request)
    {
        $bulkSmsTransactions = SmsTransaction::with('salon.owner')
            ->where('sms_type', 'bulk')
            ->latest()
            ->get();

        // Group by content and created_at to show bulk sends together
        $groupedTransactions = $bulkSmsTransactions->groupBy(function ($transaction) {
            return $transaction->content . '|' . $transaction->created_at->format('Y-m-d H:i');
        })->map(function ($group) {
            $firstTransaction = $group->first();
            return (object) [
                'content' => $firstTransaction->content,
                'created_at' => $firstTransaction->created_at,
                'success_count' => $group->where('status', 'delivered')->count(),
                'failed_count' => $group->where('status', 'failed')->count(),
                'total_count' => $group->count(),
                'transactions' => $group
            ];
        })->values()->sortByDesc('created_at');

        // Paginate manually
        $perPage = 10;
        $currentPage = $request->get('page', 1);
        $total = $groupedTransactions->count();
        $offset = ($currentPage - 1) * $perPage;
        $items = $groupedTransactions->slice($offset, $perPage);

        $paginatedTransactions = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'pageName' => 'page']
        );

        return view('admin.bulk_sms.history', compact('paginatedTransactions'));
    }
}
