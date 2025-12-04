<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_price' => 'nullable|integer|min:0|lt:price',
            'is_active' => 'sometimes|boolean',
        ]);

        $data = $request->only(['name', 'sms_count', 'price', 'discount_percentage']);
        $data['is_active'] = $request->boolean('is_active');
        
        // محاسبه قیمت با تخفیف بر اساس درصد تخفیف
        if ($request->filled('discount_percentage') && $request->discount_percentage > 0) {
            $data['discount_price'] = $request->price - ($request->price * $request->discount_percentage / 100);
        } elseif ($request->filled('discount_price')) {
            $data['discount_price'] = $request->discount_price;
        }

        SmsPackage::create($data);

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
        Log::info('SmsPackage update request received.', ['request_data' => $request->all(), 'smsPackage_id' => $smsPackage->id]);

        $request->validate([
            'name' => 'required|string|max:255',
            'sms_count' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_price' => 'nullable|numeric|min:0|lt:price',
            'is_active' => 'sometimes|boolean',
        ]);

        Log::info('SmsPackage validation passed.');

        $data = $request->only(['name', 'sms_count', 'price', 'discount_percentage']);
        $data['is_active'] = $request->boolean('is_active');
        
        // محاسبه قیمت با تخفیف بر اساس درصد تخفیف
        if ($request->filled('discount_percentage') && $request->discount_percentage > 0) {
            $data['discount_price'] = $request->price - ($request->price * $request->discount_percentage / 100);
        } elseif ($request->filled('discount_price')) {
            $data['discount_price'] = $request->discount_price;
        } else {
            $data['discount_price'] = null;
        }

        Log::info('SmsPackage data prepared for update.', ['prepared_data' => $data]);
        Log::info('SmsPackage before update.', ['smsPackage_before' => $smsPackage->toArray()]);

        try {
            $smsPackage->update($data);
            Log::info('SmsPackage updated successfully.', ['smsPackage_after' => $smsPackage->toArray()]);
            return redirect()->route('admin.sms-packages.index')->with('success', 'پکیج با موفقیت ویرایش شد.');
        } catch (\Exception $e) {
            Log::error('SmsPackage update failed: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'خطا در به‌روزرسانی پکیج پیامک.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SmsPackage $smsPackage)
    {
        try {
            // Disassociate related SmsTransactions by setting sms_package_id to NULL
            $smsPackage->smsTransactions()->update(['sms_package_id' => null]);

            $smsPackage->delete();
            return redirect()->route('admin.sms-packages.index')->with('success', 'پکیج با موفقیت حذف شد.');
        } catch (\Exception $e) {
            Log::error('Error deleting SmsPackage: ' . $e->getMessage(), ['smsPackage_id' => $smsPackage->id, 'exception' => $e]);
            return redirect()->back()->with('error', 'خطا در حذف پکیج پیامک: ' . $e->getMessage());
        }
    }
}
