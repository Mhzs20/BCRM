<?php

namespace App\Http\Controllers;

use App\Models\DiscountCode;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DiscountCodeController extends Controller
{
    public function validateCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'amount' => 'required|numeric|min:0', // Base amount to calculate discount on
            'user_id' => 'nullable|integer|exists:users,id', // Optional user ID for filter checking
        ]);

        $discountCode = DiscountCode::where('code', $request->code)
            ->where('is_active', true)
            ->first();

        if (!$discountCode) {
            return response()->json(['message' => 'کد تخفیف نامعتبر است.'], 404);
        }

        // Check if user can use this discount code (if user_id is provided)
        if ($request->filled('user_id')) {
            $user = \App\Models\User::find($request->user_id);
            if ($user && !$discountCode->canUserUse($user)) {
                return response()->json(['message' => 'شما مجاز به استفاده از این کد تخفیف نیستید.'], 403);
            }
        }

        if ($discountCode->expires_at && Carbon::now()->greaterThan($discountCode->expires_at)) {
            return response()->json(['message' => 'کد تخفیف منقضی شده است.'], 400);
        }

        // Check usage limit
        if ($discountCode->usage_limit && $discountCode->usage_count >= $discountCode->usage_limit) {
            return response()->json(['message' => 'تعداد استفاده از این کد تخفیف به حد مجاز رسیده است.'], 400);
        }

        $originalAmount = $request->amount;
        $discountAmount = ($originalAmount * $discountCode->percentage) / 100;
        $finalAmount = $originalAmount - $discountAmount;

        return response()->json([
            'message' => 'کد تخفیف معتبر است.',
            'discount_percentage' => $discountCode->percentage,
            'original_amount' => $originalAmount,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
            'usage_count' => $discountCode->usage_count,
            'usage_limit' => $discountCode->usage_limit,
            'description' => $discountCode->description,
        ]);
    }

    /**
     * Apply discount code and increment usage count
     */
    public function applyCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'user_id' => 'nullable|integer|exists:users,id',
            'order_reference' => 'nullable|string', // Reference to track the usage
        ]);

        $discountCode = DiscountCode::where('code', $request->code)
            ->where('is_active', true)
            ->first();

        if (!$discountCode) {
            return response()->json(['message' => 'کد تخفیف نامعتبر است.'], 404);
        }

        // Validate code (reuse the validation logic)
        $validationResponse = $this->validateCode($request);
        if ($validationResponse->getStatusCode() !== 200) {
            return $validationResponse;
        }

        // Increment usage count
        $discountCode->incrementUsage();

        $originalAmount = $request->amount;
        $discountAmount = ($originalAmount * $discountCode->percentage) / 100;
        $finalAmount = $originalAmount - $discountAmount;

        return response()->json([
            'message' => 'کد تخفیف با موفقیت اعمال شد.',
            'discount_percentage' => $discountCode->percentage,
            'original_amount' => $originalAmount,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
            'usage_count' => $discountCode->usage_count,
            'usage_limit' => $discountCode->usage_limit,
            'applied_at' => Carbon::now()->toISOString(),
        ]);
    }

    /**
     * Get discount code statistics for admin dashboard
     */
    public function getStatistics()
    {
        $totalCodes = DiscountCode::count();
        $activeCodes = DiscountCode::where('is_active', true)->count();
        $expiredCodes = DiscountCode::where('expires_at', '<', Carbon::now())
            ->where('is_active', true)
            ->count();
        $usedCodes = DiscountCode::whereHas('orders')->count();

        return response()->json([
            'total_codes' => $totalCodes,
            'active_codes' => $activeCodes,
            'expired_codes' => $expiredCodes,
            'used_codes' => $usedCodes,
            'unused_codes' => $totalCodes - $usedCodes,
        ]);
    }
}
