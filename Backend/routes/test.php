<?php

use Illuminate\Http\Request;
use App\Models\DiscountCode;

if (app()->environment('local')) {
Route::get('/test-discount', function (Request $request) {
    $code = $request->get('code', 'WELCOME10');
    $amount = $request->get('amount', 10000);
    
    $discountCode = DiscountCode::where('code', $code)
        ->where('is_active', true)
        ->first();
    
    if (!$discountCode) {
        return response()->json([
            'status' => 'error',
            'message' => 'کد تخفیف نامعتبر است'
        ]);
    }
    
    if ($discountCode->expires_at && now()->greaterThan($discountCode->expires_at)) {
        return response()->json([
            'status' => 'error',
            'message' => 'کد تخفیف منقضی شده است'
        ]);
    }
    
    $discountAmount = ($amount * $discountCode->percentage) / 100;
    $finalAmount = $amount - $discountAmount;
    
    return response()->json([
        'status' => 'success',
        'code' => $code,
        'percentage' => $discountCode->percentage,
        'original_amount' => $amount,
        'discount_amount' => $discountAmount,
        'final_amount' => $finalAmount,
        'expires_at' => $discountCode->expires_at?->format('Y-m-d H:i:s'),
        'is_valid' => $discountCode->isValid()
    ]);
});
}
