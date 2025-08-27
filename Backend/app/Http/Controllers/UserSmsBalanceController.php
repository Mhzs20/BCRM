<?php

namespace App\Http\Controllers;

use App\Models\SalonSmsBalance; // Use SalonSmsBalance
use App\Models\SmsPackage; // برای نمایش لیست بسته‌ها
use App\Models\Salon; // Import Salon model to access active salon
use App\Models\ActivityLog; // برای لاگ خرید بسته
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserSmsBalanceController extends Controller
{
    /**
     */
    public function show(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->activeSalon) {
            return response()->json(['data' => ['sms_balance' => 0, 'message' => 'سالن فعالی برای نمایش اعتبار پیامک وجود ندارد.']], 200);
        }

        $salonSmsBalance = SalonSmsBalance::firstOrCreate(
            ['salon_id' => $user->activeSalon->id],
            ['balance' => env('INITIAL_SMS_BALANCE', 0)]
        );

        return response()->json(['data' => ['sms_balance' => $salonSmsBalance->balance]]);
    }

    /**
     */
    public function getSmsPackages(Request $request): JsonResponse
    {
        $packages = SmsPackage::where('is_active', true)
            ->orderBy('price')
            ->get(['id', 'name', 'sms_count', 'price', 'description', 'purchase_link']);
        return response()->json(['data' => $packages]);
    }

    /**

     */
    public function purchasePackage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'package_id' => [
                'required',
                'integer',
                Rule::exists('sms_packages', 'id')->where(function ($query) {
                    $query->where('is_active', true);
                }),
            ],
        ]);

        // The ZarinpalController::purchase method now handles updating SalonSmsBalance
        // based on the authenticated user's active_salon_id.
        // We just need to ensure the request object is passed correctly.
        return app(ZarinpalController::class)->purchase($request);
    }
}
