<?php

namespace App\Traits;

use App\Models\UserPackage;
use App\Models\Option;
use Illuminate\Http\JsonResponse;

trait ChecksPackageFeature
{
    /**
     */
    protected function checkFeatureAccess(int $salonId, string $featureName): bool
    {
        $activePackage = UserPackage::with(['package.options'])
            ->where('salon_id', $salonId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();

        if (!$activePackage) {
            return false;
        }

        $hasFeature = $activePackage->package->options()
            ->where('name', $featureName)
            ->where('is_active', true)
            ->exists();

        return $hasFeature;
    }

    /**
     */
    protected function featureAccessDeniedResponse(string $featureName): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => "برای استفاده از این امکان باید پکیج پرو خریداری کنید.",
            'feature_required' => $featureName,
            'error_code' => 'FEATURE_ACCESS_DENIED'
        ], 403);
    }

    /**
     */
    protected function checkRenewalReminderAccess(int $salonId): bool
    {
        return $this->checkFeatureAccess($salonId, 'پیامک یادآوری ترمیم و تولد');
    }

    /**
     */
    protected function renewalReminderAccessDeniedResponse(): JsonResponse
    {
        return $this->featureAccessDeniedResponse('پیامک یادآوری ترمیم و تولد');
    }
}