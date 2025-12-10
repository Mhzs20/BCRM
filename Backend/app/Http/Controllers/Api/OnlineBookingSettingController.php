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
            'default_booking_status' => 'pending_confirmation'
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
            'default_booking_status' => 'required|in:pending_confirmation,confirmed',
        ]);

        $salon = Salon::findOrFail($salonId);
        
        $settings = $salon->online_booking_settings ?? [];
        $settings['default_booking_status'] = $request->default_booking_status;
        
        $salon->online_booking_settings = $settings;
        $salon->save();

        return response()->json([
            'success' => true,
            'message' => 'با موفقیت بروزرسانی شد.',
            'data' => $salon->online_booking_settings
        ]);
    }
}
