<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Salon;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(Salon $salon)
    {
        // Policy check
        $this->authorize('view', $salon);

        $settings = Setting::where('salon_id', $salon->id)
            ->pluck('value', 'key');
            
        return response()->json($settings);
    }

    public function store(Request $request, Salon $salon)
    {
        // Policy check
        $this->authorize('update', $salon);

        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable|string',
        ]);

        foreach ($request->settings as $setting) {
            // Do not allow updating global settings from this endpoint
            if (in_array($setting['key'], ['sms_character_limit'])) {
                continue;
            }

            Setting::updateOrCreate(
                ['salon_id' => $salon->id, 'key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }

        return response()->json(['message' => 'Settings updated successfully.']);
    }
}
