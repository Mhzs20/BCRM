<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUpdate;
use Illuminate\Http\Request;

class AppController extends Controller
{
    public function latestHistory()
    {
        $latestUpdate = AppUpdate::latest()->first();

        if ($latestUpdate) {
            return response()->json($latestUpdate);
        }

        return response()->json(['message' => 'No update information found.'], 404);
    }
}
