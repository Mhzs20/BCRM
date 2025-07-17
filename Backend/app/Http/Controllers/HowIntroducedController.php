<?php

namespace App\Http\Controllers;

use App\Models\Salon;
use App\Models\HowIntroduced;
use App\Http\Requests\StoreHowIntroducedRequest;
use App\Http\Requests\UpdateHowIntroducedRequest;
use Illuminate\Support\Facades\Gate;

class HowIntroducedController extends Controller
{
    public function index(Salon $salon)
    {
        Gate::authorize('view', $salon);
        $items = $salon->howIntroduceds()->orderBy('name')->get();
        return response()->json($items);
    }

    public function store(StoreHowIntroducedRequest $request, Salon $salon)
    {
        // Authorization is handled by StoreHowIntroducedRequest
        $item = $salon->howIntroduceds()->create($request->validated());
        return response()->json($item, 201);
    }

    public function show(Salon $salon, HowIntroduced $howIntroduced)
    {
        Gate::authorize('view', $salon);
        if ($howIntroduced->salon_id !== $salon->id) {
            return response()->json(['message' => 'Not Found or Forbidden'], 404);
        }
        return response()->json($howIntroduced);
    }

    public function update(UpdateHowIntroducedRequest $request, Salon $salon, HowIntroduced $howIntroduced)
    {
        // Authorization is handled by UpdateHowIntroducedRequest
        if ($howIntroduced->salon_id !== $salon->id) { // Additional check
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $howIntroduced->update($request->validated());
        return response()->json($howIntroduced);
    }

    public function destroy(Salon $salon, HowIntroduced $howIntroduced)
    {
        Gate::authorize('update', $salon); // Or a more specific policy like 'deleteItem'
        if ($howIntroduced->salon_id !== $salon->id) { // Additional check
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $howIntroduced->delete();
        return response()->json(null, 204);
    }
}
