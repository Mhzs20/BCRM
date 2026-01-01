<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Salon;
use Illuminate\Http\Request;

class OnlineBookingSettingController extends Controller
{
    public function show($salonId)
    {
        $salon = Salon::findOrFail($salonId);
        
        $defaults = [
            'default_booking_status' => 'pending_confirmation',
            'enabled' => true,
            'enabled_days' => [0, 1, 2, 3, 4, 5, 6], // 0=Sat, ..., 6=Fri
            'allow_holiday_booking' => false // Allow booking on official holidays
        ];
        
        $settings = $salon->online_booking_settings ?? [];
        
        // Merge defaults with existing settings
        $mergedSettings = array_merge($defaults, $settings);

        return response()->json([
            'success' => true,
            'data' => $mergedSettings
        ]);
    }

    public function update(Request $request, $salonId)
    {
        $request->validate([
            'default_booking_status' => 'sometimes|required|in:pending_confirmation,confirmed',
            'enabled' => 'sometimes|required|boolean',
            'enabled_days' => 'sometimes|required|array',
            'enabled_days.*' => 'integer|min:0|max:6',
            'allow_holiday_booking' => 'sometimes|required|boolean',
        ]);

        $salon = Salon::findOrFail($salonId);
        
        $settings = $salon->online_booking_settings ?? [];
        
        if ($request->has('default_booking_status')) {
            $settings['default_booking_status'] = $request->default_booking_status;
        }
        
        if ($request->has('enabled')) {
            $settings['enabled'] = filter_var($request->enabled, FILTER_VALIDATE_BOOLEAN);
        }
        
        if ($request->has('enabled_days')) {
            $settings['enabled_days'] = $request->enabled_days;
        }
        
        if ($request->has('allow_holiday_booking')) {
            $settings['allow_holiday_booking'] = filter_var($request->allow_holiday_booking, FILTER_VALIDATE_BOOLEAN);
        }
        
        $salon->online_booking_settings = $settings;
        $salon->save();

        return response()->json([
            'success' => true,
            'message' => 'تنظیمات رزرو آنلاین با موفقیت بروزرسانی شد.',
            'data' => $salon->online_booking_settings
        ]);
    }
}
