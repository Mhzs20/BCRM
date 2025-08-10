<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'notes' => 'nullable|string',
            'force_update' => 'boolean',
            'apk_file' => 'nullable|file|mimes:apk', // Add validation for APK file
        ]);

        $data = $request->all();
        $data['force_update'] = $request->has('force_update');

        if ($request->hasFile('apk_file')) {
            $apkPath = $request->file('apk_file')->store('public/apks');
            $data['apk_path'] = $apkPath;
        }

        AppUpdate::create($data);

        return redirect()->route('admin.app-updates.index')->with('success', 'لینک آپدیت جدید با موفقیت اضافه شد.');
    }

    public function edit(AppUpdate $appUpdate)
    {
        return view('admin.app-updates.edit', compact('appUpdate'));
    }

    public function update(Request $request, AppUpdate $appUpdate)
    {
        $request->validate([
            'version' => 'required|string|max:255',
            'direct_link' => 'nullable|url',
            'google_play_link' => 'nullable|url',
            'cafe_bazaar_link' => 'nullable|url',
            'app_store_link' => 'nullable|url',
            'notes' => 'nullable|string',
            'force_update' => 'boolean',
            'apk_file' => 'nullable|file|mimes:apk', // Add validation for APK file
        ]);

        $data = $request->all();
        $data['force_update'] = $request->has('force_update');

        if ($request->hasFile('apk_file')) {
            // Delete old APK if exists
            if ($appUpdate->apk_path) {
                Storage::delete($appUpdate->apk_path);
            }
            $apkPath = $request->file('apk_file')->store('public/apks');
            $data['apk_path'] = $apkPath;
        }

        $appUpdate->update($data);

        return redirect()->route('admin.app-updates.index')->with('success', 'App update updated successfully.');
    }

    public function destroy(AppUpdate $appUpdate)
    {
        $appUpdate->delete();
        return redirect()->route('admin.app-updates.index')->with('success', 'App update deleted successfully.');
    }
}
