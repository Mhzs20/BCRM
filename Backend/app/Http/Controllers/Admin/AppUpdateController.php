<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppUpdate;
use Illuminate\Http\Request;

class AppUpdateController extends Controller
{
    public function index()
    {
        $updates = AppUpdate::latest()->paginate(10);
        return view('admin.app-updates.index', compact('updates'));
    }

    public function create()
    {
        return view('admin.app-updates.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'version' => 'required|string|max:255',
            'direct_link' => 'nullable|url',
            'google_play_link' => 'nullable|url',
            'cafe_bazaar_link' => 'nullable|url',
            'app_store_link' => 'nullable|url',
        ]);

        AppUpdate::create($request->all());

        return redirect()->route('admin.app-updates.index')->with('success', 'لینک آپدیت جدید با موفقیت اضافه شد.');
    }
}
