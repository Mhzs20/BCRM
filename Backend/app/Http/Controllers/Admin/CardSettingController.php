<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CardSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cardSettings = \App\Models\CardSetting::orderByDesc('id')->get();
        return view('admin.card_setting.index', compact('cardSettings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $cardSetting = \App\Models\CardSetting::findOrFail($id);
        return view('admin.card_setting.edit', compact('cardSetting'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'is_active' => 'nullable|boolean',
            'card_number' => 'nullable|string',
            'card_holder_name' => 'nullable|string',
            'description' => 'nullable|string',
        ]);
        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        $cardSetting = \App\Models\CardSetting::findOrFail($id);
        $cardSetting->update($data);
        return redirect()->route('card-setting.index')->with('success', 'تنظیمات کارت با موفقیت ویرایش شد');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $cardSetting = \App\Models\CardSetting::findOrFail($id);
        $cardSetting->delete();
        return redirect()->route('card-setting.index')->with('success', 'کارت حذف شد');
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'is_active' => 'nullable|boolean',
            'card_number' => 'nullable|string',
            'card_holder_name' => 'nullable|string',
            'description' => 'nullable|string',
        ]);
        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        \App\Models\CardSetting::create($data);
        return redirect()->route('card-setting.index')->with('success', 'کارت جدید ثبت شد');
    }
}
