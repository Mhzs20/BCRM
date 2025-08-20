<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index(Request $request)
    {
        $location = $request->query('location');

        if ($location) {
            $banners = Banner::where('location', $location)->get();
        } else {
            $banners = Banner::all();
        }

        return response()->json($banners);
    }
}
