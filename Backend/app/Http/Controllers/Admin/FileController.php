<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $files = File::all();
        return view('admin.files.index', compact('files'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx,zip,xls,xlsx|max:2048',
                'name' => 'required|string|max:255',
            ]);

            $fileName = time() . '_' . $request->file('file')->getClientOriginalName();
            $filePath = $request->file('file')->storeAs('files', $fileName, 'public');

            $file = File::create([
                'name' => $request->name,
                'path' => $filePath,
            ]);

            return redirect()->route('admin.files.index')->with('success', 'فایل با موفقیت آپلود شد: ' . $file->name);
        } catch (\Exception $e) {
            return redirect()->route('admin.files.index')->with('error', 'خطا در آپلود فایل: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $file = File::findOrFail($id);
            Storage::disk('public')->delete($file->path);
            $file->delete();

            return redirect()->route('admin.files.index')->with('success', 'فایل با موفقیت حذف شد.');
        } catch (\Exception $e) {
            return redirect()->route('admin.files.index')->with('error', 'خطا در حذف فایل: ' . $e->getMessage());
        }
    }
}
