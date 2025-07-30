<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsPackage;
use Illuminate\Http\Request;

class SmsPackageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $smsPackages = SmsPackage::latest()->get();
        return view('admin.sms-packages.index', compact('smsPackages'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.sms-packages.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sms_count' => 'required|integer|min:1',
            'price' => 'required|integer|min:0',
            'purchase_link' => 'nullable|url',
            'is_active' => 'required|boolean',
        ]);

        SmsPackage::create($request->all());

        return redirect()->route('admin.sms-packages.index')->with('success', 'پکیج با موفقیت ایجاد شد.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SmsPackage $smsPackage)
    {
        return view('admin.sms-packages.edit', compact('smsPackage'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SmsPackage $smsPackage)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sms_count' => 'required|integer|min:1',
            'price' => 'required|integer|min:0',
            'purchase_link' => 'nullable|url',
            'is_active' => 'required|boolean',
        ]);

        $smsPackage->update($request->all());

        return redirect()->route('admin.sms-packages.index')->with('success', 'پکیج با موفقیت ویرایش شد.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SmsPackage $smsPackage)
    {
        $smsPackage->delete();
        return redirect()->route('admin.sms-packages.index')->with('success', 'پکیج با موفقیت حذف شد.');
    }
}
