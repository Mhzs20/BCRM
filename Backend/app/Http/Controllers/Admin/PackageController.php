<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Option;
use App\Http\Requests\StorePackageRequest;
use App\Http\Requests\UpdatePackageRequest;

class PackageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $packages = Package::with('options')->latest()->paginate(15);
        return view('admin.packages.index', compact('packages'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $options = Option::where('is_active', true)->get();
        return view('admin.packages.create', compact('options'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePackageRequest $request)
    {
        $package = Package::create($request->only(['name', 'description', 'price', 'gift_sms_count', 'duration_days', 'is_active']));
        
        if ($request->has('options')) {
            $package->options()->sync($request->options);
        }
        
        return redirect()->route('admin.packages.index')
            ->with('success', 'پکیج با موفقیت ایجاد شد.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Package $package)
    {
        $package->load('options');
        return view('admin.packages.show', compact('package'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Package $package)
    {
        $options = Option::where('is_active', true)->get();
        $package->load('options');
        return view('admin.packages.edit', compact('package', 'options'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePackageRequest $request, Package $package)
    {
        $package->update($request->only(['name', 'description', 'price', 'gift_sms_count', 'duration_days', 'is_active']));
        
        if ($request->has('options')) {
            $package->options()->sync($request->options);
        }
        
        return redirect()->route('admin.packages.index')
            ->with('success', 'پکیج با موفقیت به‌روزرسانی شد.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Package $package)
    {
        $package->delete();
        
        return redirect()->route('admin.packages.index')
            ->with('success', 'پکیج با موفقیت حذف شد.');
    }
}
