<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HowIntroduced;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Assuming you need the salon_id from the authenticated user

class HowIntroducedController extends Controller
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

        $howIntroducedOptions = HowIntroduced::where('salon_id', $user->active_salon_id)->paginate(10);

        return view('admin.how-introduced.index', compact('howIntroducedOptions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.how-introduced.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (is_null($user->active_salon_id)) {
            return redirect()->route('admin.dashboard')->with('error', 'برای افزودن نحوه آشنایی، ابتدا باید سالن فعال خود را انتخاب کنید.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        HowIntroduced::create([
            'salon_id' => $user->active_salon_id,
            'name' => $request->name,
        ]);

        return redirect()->route('admin.how-introduced.index')->with('success', 'نحوه آشنایی با موفقیت اضافه شد.');
    }

    /**
     * Display the specified resource.
     */
    public function show(HowIntroduced $howIntroduced)
    {
        // Not typically used for simple CRUD, but can be implemented if needed.
        return redirect()->route('admin.how-introduced.edit', $howIntroduced);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(HowIntroduced $howIntroduced)
    {
        $user = Auth::user();
        if (is_null($user->active_salon_id)) {
            return redirect()->route('admin.dashboard')->with('error', 'برای ویرایش نحوه آشنایی، ابتدا باید سالن فعال خود را انتخاب کنید.');
        }

        // Ensure the option belongs to the authenticated salon
        if ($howIntroduced->salon_id !== $user->active_salon_id) {
            abort(403); // Forbidden
        }
        return view('admin.how-introduced.edit', compact('howIntroduced'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, HowIntroduced $howIntroduced)
    {
        $user = Auth::user();
        if (is_null($user->active_salon_id)) {
            return redirect()->route('admin.dashboard')->with('error', 'برای ویرایش نحوه آشنایی، ابتدا باید سالن فعال خود را انتخاب کنید.');
        }

        // Ensure the option belongs to the authenticated salon
        if ($howIntroduced->salon_id !== $user->active_salon_id) {
            abort(403); // Forbidden
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $howIntroduced->update([
            'name' => $request->name,
        ]);

        return redirect()->route('admin.how-introduced.index')->with('success', 'نحوه آشنایی با موفقیت ویرایش شد.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HowIntroduced $howIntroduced)
    {
        $user = Auth::user();
        if (is_null($user->active_salon_id)) {
            return redirect()->route('admin.dashboard')->with('error', 'برای حذف نحوه آشنایی، ابتدا باید سالن فعال خود را انتخاب کنید.');
        }

        // Ensure the option belongs to the authenticated salon
        if ($howIntroduced->salon_id !== $user->active_salon_id) {
            abort(403); // Forbidden
        }

        $howIntroduced->delete();

        return redirect()->route('admin.how-introduced.index')->with('success', 'نحوه آشنایی با موفقیت حذف شد.');
    }
}
