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
        $user = Auth::user();
        $validated = $request->validate([
            'package_id' => 'required|integer|exists:sms_packages,id,is_active,true',
        ]);

        $package = SmsPackage::find($validated['package_id']);



        DB::beginTransaction();
        try {
            $userSmsBalance = UserSmsBalance::firstOrCreate(['user_id' => $user->id]);
            $userSmsBalance->increment('balance', $package->sms_count);

            ActivityLog::create([
                'user_id' => $user->id,
                'salon_id' => $user->active_salon_id,
                'activity_type' => 'sms_package_purchased',
                'description' => "بسته پیامکی '{$package->name}' ({$package->sms_count} پیامک به مبلغ {$package->price}) خریداری شد.",
                'loggable_id' => $package->id,
                'loggable_type' => SmsPackage::class,
            ]);

            DB::commit();
            return response()->json([
                'message' => 'بسته پیامکی با موفقیت خریداری شد (شبیه‌سازی شده).',
                'new_balance' => $userSmsBalance->balance
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error purchasing SMS package for user ID {$user->id}: " . $e->getMessage());
            return response()->json(['message' => 'خطا در پردازش خرید بسته پیامکی.'], 500);
        }
    }
}
