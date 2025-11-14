<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsPackage;
use Illuminate\Http\Request;

class SmsPackageController extends Controller
{
    /**
     * Get all active SMS packages with optional discount code
     * GET /api/sms-packages?discount_code=WINTER30
     */
    public function index(Request $request)
    {
        try {
            // Check for discount code in request
            $discountCode = null;
            $discountCodeModel = null;
            $user = $request->user();

            if ($request->filled('discount_code')) {
                $discountCodeModel = \App\Models\DiscountCode::where('code', $request->discount_code)
                    ->where('is_active', true)
                    ->first();

                if ($discountCodeModel && $discountCodeModel->isValid()) {
                    // Check if user can use this code
                    if ($user && $discountCodeModel->canUserUse($user)) {
                        $discountCode = $discountCodeModel;
                    }
                }
            }

            $packages = SmsPackage::where('is_active', true)
                ->orderBy('sms_count', 'asc')
                ->get()
                ->map(function ($package) use ($discountCode) {
                    $originalPrice = $package->price;
                    $packageDiscountPrice = $package->discount_price;
                    $packageHasDiscount = $packageDiscountPrice && $packageDiscountPrice < $originalPrice;
                    $packageDiscountPercentage = $packageHasDiscount 
                        ? (($originalPrice - $packageDiscountPrice) / $originalPrice) * 100 
                        : 0;
                    
                    $finalPrice = $packageHasDiscount ? $packageDiscountPrice : $originalPrice;
                    $appliedDiscountPercentage = $packageDiscountPercentage;
                    $appliedDiscountAmount = $packageHasDiscount ? ($originalPrice - $packageDiscountPrice) : 0;
                    $discountSource = $packageHasDiscount ? 'package' : 'none';
                    $codeDiscountPercentage = 0;

                    // Apply discount code if available and better than package discount
                    if ($discountCode) {
                        // Calculate discount code percentage
                        if ($discountCode->type === 'percentage') {
                            $codeDiscountPercentage = $discountCode->value;
                        } elseif ($discountCode->type === 'fixed') {
                            // Convert fixed amount to percentage for comparison
                            $codeDiscountPercentage = ($discountCode->value / $originalPrice) * 100;
                        }

                        // Apply code only if its discount is better than package discount
                        if ($codeDiscountPercentage > $packageDiscountPercentage) {
                            // Check minimum order amount against original price
                            if (!$discountCode->min_order_amount || $originalPrice >= $discountCode->min_order_amount) {
                                $codeDiscountAmount = $discountCode->calculateDiscount($originalPrice);
                                $finalPrice = $originalPrice - $codeDiscountAmount;
                                $appliedDiscountPercentage = $codeDiscountPercentage;
                                $appliedDiscountAmount = $codeDiscountAmount;
                                $discountSource = 'code';
                            }
                        }
                    }

                    return [
                        'id' => $package->id,
                        'name' => $package->name,
                        'sms_count' => $package->sms_count,
                        'price' => $originalPrice,
                        'final_price' => $finalPrice,
                        'discount_percentage' => round($appliedDiscountPercentage, 2),
                        'discount_amount' => $appliedDiscountAmount,
                        'package_discount_percentage' => round($packageDiscountPercentage, 2),
                        'package_discount_amount' => $packageHasDiscount ? ($originalPrice - $packageDiscountPrice) : 0,
                        'code_discount_percentage' => $discountSource === 'code' ? round($codeDiscountPercentage, 2) : 0,
                        'discount_source' => $discountSource,
                        'formatted_sms_count' => number_format($package->sms_count),
                        'formatted_price' => number_format($originalPrice / 10) . ' تومان',
                        'formatted_final_price' => number_format($finalPrice / 10) . ' تومان',
                        'formatted_you_save' => $appliedDiscountAmount > 0 
                            ? number_format($appliedDiscountAmount / 10) . ' تومان صرفه‌جویی' 
                            : null,
                    ];
                });

            $response = [
                'status' => 'success',
                'data' => $packages
            ];

            // Add discount code info if applied
            if ($discountCode) {
                $response['discount_code'] = [
                    'code' => $discountCode->code,
                    'description' => $discountCode->description,
                    'type' => $discountCode->type,
                    'value' => $discountCode->value,
                    'applied' => true,
                ];
            } elseif ($request->filled('discount_code')) {
                $response['discount_code'] = [
                    'code' => $request->discount_code,
                    'applied' => false,
                    'message' => 'کد تخفیف نامعتبر یا منقضی شده است.',
                ];
            }

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در دریافت پکیج‌ها: ' . $e->getMessage()
            ], 500);
        }
    }
}
