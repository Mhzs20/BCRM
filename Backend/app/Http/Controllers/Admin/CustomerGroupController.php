<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        if (is_null($user->active_salon_id)) {
            return redirect()->route('admin.dashboard')->with('error', 'برای دسترسی به این بخش، ابتدا باید سالن فعال خود را انتخاب کنید.');
        }

        $customerGroups = CustomerGroup::where('salon_id', $user->active_salon_id)->paginate(10);

        return view('admin.customer-groups.index', compact('customerGroups'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.customer-groups.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (is_null($user->active_salon_id)) {
            return redirect()->route('admin.dashboard')->with('error', 'برای افزودن گروه مشتری، ابتدا باید سالن فعال خود را انتخاب کنید.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        CustomerGroup::create([
            'salon_id' => $user->active_salon_id,
            'name' => $request->name,
        ]);

        return redirect()->route('admin.customer-groups.index')->with('success', 'گروه مشتری با موفقیت اضافه شد.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CustomerGroup $customerGroup)
    {
        return redirect()->route('admin.customer-groups.edit', $customerGroup);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CustomerGroup $customerGroup)
    {
        $user = Auth::user();
        if (is_null($user->active_salon_id)) {
            return redirect()->route('admin.dashboard')->with('error', 'برای ویرایش گروه مشتری، ابتدا باید سالن فعال خود را انتخاب کنید.');
        }

        if ($customerGroup->salon_id !== $user->active_salon_id) {
            abort(403); // Forbidden
        }
        return view('admin.customer-groups.edit', compact('customerGroup'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CustomerGroup $customerGroup)
    {
        $user = Auth::user();
        if (is_null($user->active_salon_id)) {
            return redirect()->route('admin.dashboard')->with('error', 'برای ویرایش گروه مشتری، ابتدا باید سالن فعال خود را انتخاب کنید.');
        }

        if ($customerGroup->salon_id !== $user->active_salon_id) {
            abort(403); // Forbidden
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $customerGroup->update([
            'name' => $request->name,
        ]);

        return redirect()->route('admin.customer-groups.index')->with('success', 'گروه مشتری با موفقیت ویرایش شد.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomerGroup $customerGroup)
    {
        $user = Auth::user();
        if (is_null($user->active_salon_id)) {
            return redirect()->route('admin.dashboard')->with('error', 'برای حذف گروه مشتری، ابتدا باید سالن فعال خود را انتخاب کنید.');
        }

        if ($customerGroup->salon_id !== $user->active_salon_id) {
            abort(403); // Forbidden
        }

        $customerGroup->delete();

        return redirect()->route('admin.customer-groups.index')->with('success', 'گروه مشتری با موفقیت حذف شد.');
    }
}
