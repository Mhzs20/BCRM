<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalonSmsTemplate;
use Illuminate\Http\Request;

class SmsTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $smsTemplates = SalonSmsTemplate::latest()->get();
        return view('admin.sms-templates.index', compact('smsTemplates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.sms-templates.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        SalonSmsTemplate::create($request->all());

        return redirect()->route('admin.sms-templates.index')->with('success', 'قالب با موفقیت ایجاد شد.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SalonSmsTemplate $smsTemplate)
    {
        return view('admin.sms-templates.edit', compact('smsTemplate'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SalonSmsTemplate $smsTemplate)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $smsTemplate->update($request->all());

        return redirect()->route('admin.sms-templates.index')->with('success', 'قالب با موفقیت ویرایش شد.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SalonSmsTemplate $smsTemplate)
    {
        $smsTemplate->delete();
        return redirect()->route('admin.sms-templates.index')->with('success', 'قالب با موفقیت حذف شد.');
    }
}
