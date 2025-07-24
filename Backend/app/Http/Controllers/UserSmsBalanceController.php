<?php

namespace App\Http\Controllers;

use App\Models\UserSmsBalance;
use App\Models\SmsPackage; // برای نمایش لیست بسته‌ها
use App\Models\ActivityLog; // برای لاگ خرید بسته
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserSmsBalanceController extends Controller
{
    /**
     */
    public function show(Request $request): JsonResponse
    {
        $user = Auth::user();
        $smsBalance = UserSmsBalance::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => env('INITIAL_SMS_BALANCE', 0)]
        );

        return response()->json(['data' => ['sms_balance' => $smsBalance->balance]]);
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
            'package_id' => 'required|integer|exists:sms_packages,id,is_active,true',
        ]);

        return app(ZarinpalController::class)->purchase($request, $validated['package_id']);
    }
}
