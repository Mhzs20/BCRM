<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CardSettingController extends Controller
{
    public function showCardInfo()
    {
        $cardSetting = \App\Models\CardSetting::first();
        if ($cardSetting && $cardSetting->is_active) {
            return response()->json([
                'card_number' => $cardSetting->card_number,
                'card_holder_name' => $cardSetting->card_holder_name,
                'description' => $cardSetting->description,
                'is_active' => $cardSetting->is_active,
            ]);
        }
        return response()->json(['message' => 'کارت فعال نیست'], 404);
    }
}
