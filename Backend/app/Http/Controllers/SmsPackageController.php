<?php

namespace App\Http\Controllers;

use App\Models\SmsPackage;
use App\Models\SmsTransaction;
use App\Http\Requests\UpdateSmsPackageRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class SmsPackageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $packages = SmsPackage::where('is_active', true)->orderBy('price')->get();
        return response()->json($packages);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Not typically used for API, but keeping for resource consistency
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Assuming only superadmin can create SMS packages
        if (!Auth::user()->is_superadmin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'sms_count' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:price',
            'is_active' => 'boolean',
        ]);

        try {
            $package = SmsPackage::create($request->all());
            return response()->json(['message' => 'بسته پیامک با موفقیت ایجاد شد.', 'data' => $package], 201);
        } catch (\Exception $e) {
            Log::error('SmsPackage store failed: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در ایجاد بسته پیامک.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Assuming only superadmin can view a specific SMS package
        if (!Auth::user()->is_superadmin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $package = SmsPackage::findOrFail($id);
        return response()->json($package);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Not typically used for API, but keeping for resource consistency
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSmsPackageRequest $request, string $id)
    {
        // Authorization is handled by UpdateSmsPackageRequest
        try {
            $package = SmsPackage::findOrFail($id);
            $validatedData = $request->validated();

            // Only update fields that are present in the request
            $package->update($validatedData);

            return response()->json(['message' => 'بسته پیامک با موفقیت به‌روزرسانی شد.', 'data' => $package]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'بسته پیامک یافت نشد.'], 404);
        } catch (\Exception $e) {
            Log::error('SmsPackage update failed: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در به‌روزرسانی بسته پیامک.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Assuming only superadmin can delete SMS packages
        if (!Auth::user()->is_superadmin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        try {
            $package = SmsPackage::findOrFail($id);
            $package->delete();
            return response()->json(['message' => 'بسته پیامک با موفقیت حذف شد.'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'بسته پیامک یافت نشد.'], 404);
        } catch (\Exception $e) {
            Log::error('SmsPackage destroy failed: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در حذف بسته پیامک.'], 500);
        }
    }

    public function getSmsBalance()
    {
        $user = Auth::user()->load('activeSalon');
        $salon = $user->activeSalon;

        if (!$salon) {
            return response()->json(['message' => 'سالن فعالی انتخاب نشده است.'], 404);
        }

        $activePackage = SmsTransaction::where('salon_id', $salon->id)
            ->where('is_active', true)
            ->with('smsPackage')
            ->first();

        return response()->json([
            'sms_balance' => $salon->sms_balance,
            'active_package' => $activePackage ? $activePackage->smsPackage : null,
        ]);
    }

    public function getSmsStatistics()
    {
        $user = Auth::user()->load('activeSalon');
        $salon = $user->activeSalon;

        if (!$salon) {
            return response()->json(['message' => 'سالن فعالی انتخاب نشده است.'], 404);
        }

        $smsBalance = $salon->sms_balance;

        $dailyConsumption = SmsTransaction::where('salon_id', $salon->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count() / 30;

        return response()->json([
            'sms_balance' => $smsBalance,
            'daily_consumption' => round($dailyConsumption, 2),
        ]);
    }
}
