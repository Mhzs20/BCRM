<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfessionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        // Allow access if active_salon_id is null (Global Templates)
        
        if (is_null($user->active_salon_id)) {
             $professions = Profession::whereNull('salon_id')->paginate(10);
        } else {
             $professions = Profession::where('salon_id', $user->active_salon_id)->paginate(10);
        }

        return view('admin.professions.index', compact('professions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.professions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        // Allow creation if active_salon_id is null (Global Templates)

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $profession = Profession::create([
            'salon_id' => $user->active_salon_id,
            'name' => $request->name,
        ]);

        if (is_null($user->active_salon_id)) {
            \App\Jobs\SyncGlobalTemplateToSalons::dispatch(
                \App\Models\Profession::class, 
                $profession->id, 
                $profession->name,
                $profession->created_at
            );
        }

        return redirect()->route('admin.professions.index')->with('success', 'شغل با موفقیت اضافه شد.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Profession $profession)
    {
        return redirect()->route('admin.professions.edit', $profession);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Profession $profession)
    {
        $user = Auth::user();
        
        // Check ownership: if user has salon, profession must match. If user has no salon (Global), profession must be global.
        if ($profession->salon_id !== $user->active_salon_id) {
            abort(403); // Forbidden
        }
        return view('admin.professions.edit', compact('profession'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Profession $profession)
    {
        $user = Auth::user();
        
        if ($profession->salon_id !== $user->active_salon_id) {
            abort(403); // Forbidden
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $profession->update([
            'name' => $request->name,
        ]);

        return redirect()->route('admin.professions.index')->with('success', 'شغل با موفقیت ویرایش شد.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Profession $profession)
    {
        $user = Auth::user();
        
        if ($profession->salon_id !== $user->active_salon_id) {
            abort(403); // Forbidden
        }

        $profession->delete();

        return redirect()->route('admin.professions.index')->with('success', 'شغل با موفقیت حذف شد.');
    }
}
