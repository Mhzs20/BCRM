<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class AdminSmsSettingController extends Controller
{
    /**
     * Display the SMS settings form.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $smsCostPerPart = Setting::where('key', 'sms_cost_per_part')->first();
        $smsCharacterLimitFa = Setting::where('key', 'sms_part_char_limit_fa')->first();
        $smsCharacterLimitEn = Setting::where('key', 'sms_part_char_limit_en')->first();

        return view('admin.sms-settings.index', compact('smsCostPerPart', 'smsCharacterLimitFa', 'smsCharacterLimitEn'));
    }

    /**
     * Update the SMS settings.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $request->validate([
            'sms_cost_per_part' => 'required|numeric|min:0',
            'sms_part_char_limit_fa' => 'required|integer|min:1',
            'sms_part_char_limit_en' => 'required|integer|min:1',
        ]);

        Setting::updateOrCreate(
            ['key' => 'sms_cost_per_part'],
            ['value' => $request->sms_cost_per_part]
        );

        Setting::updateOrCreate(
            ['key' => 'sms_part_char_limit_fa'],
            ['value' => $request->sms_part_char_limit_fa]
        );

        Setting::updateOrCreate(
            ['key' => 'sms_part_char_limit_en'],
            ['value' => $request->sms_part_char_limit_en]
        );

        return redirect()->back()->with('success', 'تنظیمات پیامک با موفقیت به‌روزرسانی شد.');
    }
}
