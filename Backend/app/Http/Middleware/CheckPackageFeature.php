<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\UserPackage;
use Symfony\Component\HttpFoundation\Response;

class CheckPackageFeature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $featureName): Response
    {
        $salon = $request->route()->parameter('salon') ?? $request->route()->parameter('salonId');
        
        $salonId = is_object($salon) ? $salon->id : $salon;
        
        if (!$salonId) {
            return response()->json([
                'success' => false,
                'message' => 'شناسه سالن یافت نشد.',
                'error_code' => 'SALON_ID_MISSING'
            ], 400);
        }

        if (!$this->hasFeatureAccess($salonId, $featureName)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "برای استفاده از این امکان باید پکیج پرو خریداری کنید.",
                    'feature_required' => $featureName,
                    'error_code' => 'FEATURE_ACCESS_DENIED'
                ], 403);
            }
            
            return response()->view('booking.feature-locked', ['feature' => $featureName], 403);
        }

        return $next($request);
    }

    /**
     */
    private function hasFeatureAccess($salonId, string $featureName): bool
    {
        try {
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
        } catch (\Exception $e) {
            return false;
        }
    }
}