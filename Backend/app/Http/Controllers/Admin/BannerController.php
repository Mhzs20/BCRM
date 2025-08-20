<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::all();
        return view('admin.banners.index', compact('banners'));
    }

    public function create()
    {
        return view('admin.banners.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image',
            'link' => 'nullable|url',
            'location' => 'required|in:home,dashboard',
        ]);

        $path = $request->file('image')->store('banners', 'public');

        Banner::create([
            'image' => $path,
            'link' => $request->link,
            'location' => $request->location,
        ]);

        return redirect()->route('admin.banners.index')->with('success', 'بنر با موفقیت ایجاد شد.');
    }

    public function edit(Banner $banner)
    {
        return view('admin.banners.edit', compact('banner'));
    }

    public function update(Request $request, Banner $banner)
    {
        $request->validate([
            'image' => 'nullable|image',
            'link' => 'nullable|url',
            'location' => 'required|in:home,dashboard',
        ]);

        $data = $request->only(['link', 'location']);

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($banner->image);
            $data['image'] = $request->file('image')->store('banners', 'public');
        }

        $banner->update($data);

        return redirect()->route('admin.banners.index')->with('success', 'بنر با موفقیت ویرایش شد.');
    }

    public function destroy(Banner $banner)
    {
        Storage::disk('public')->delete($banner->image);
        $banner->delete();

        return redirect()->route('admin.banners.index')->with('success', 'بنر با موفقیت حذف شد.');
    }
}
