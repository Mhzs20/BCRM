<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Option;
use Illuminate\Http\Request;

class OptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $options = Option::latest()->paginate(20);
        return view('admin.options.index', compact('options'));
    }

    /**
     * Toggle option active status
     */
    public function toggleStatus(Option $option)
    {
        $option->update(['is_active' => !$option->is_active]);
        
        return response()->json([
            'success' => true,
            'is_active' => $option->is_active,
            'message' => 'وضعیت آپشن با موفقیت تغییر کرد.'
        ]);
    }
}
