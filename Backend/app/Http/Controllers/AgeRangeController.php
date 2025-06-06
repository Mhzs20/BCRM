<?php

namespace App\Http\Controllers;

use App\Models\Salon;
use App\Models\AgeRange;
use App\Http\Requests\StoreAgeRangeRequest;
use App\Http\Requests\UpdateAgeRangeRequest;
use Illuminate\Support\Facades\Gate;

class AgeRangeController extends Controller
{
    public function index(Salon $salon)
    {
        Gate::authorize('view', $salon);
        $items = $salon->ageRanges()->orderBy('name')->get();
        return response()->json($items);
    }

    public function store(StoreAgeRangeRequest $request, Salon $salon)
    {
        $item = $salon->ageRanges()->create($request->validated());
        return response()->json($item, 201);
    }

    public function show(Salon $salon, AgeRange $ageRange)
    {
        Gate::authorize('view', $salon);
        if ($ageRange->salon_id !== $salon->id) {
            return response()->json(['message' => 'Not Found or Forbidden'], 404);
        }
        return response()->json($ageRange);
    }

    public function update(UpdateAgeRangeRequest $request, Salon $salon, AgeRange $ageRange)
    {
        if ($ageRange->salon_id !== $salon->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $ageRange->update($request->validated());
        return response()->json($ageRange);
    }

    public function destroy(Salon $salon, AgeRange $ageRange)
    {
        Gate::authorize('update', $salon);
        if ($ageRange->salon_id !== $salon->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $ageRange->delete();
        return response()->json(null, 204);
    }
}
