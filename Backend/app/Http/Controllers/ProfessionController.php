<?php

namespace App\Http\Controllers;

use App\Models\Salon;
use App\Models\Profession;
use App\Http\Requests\StoreProfessionRequest;
use App\Http\Requests\UpdateProfessionRequest;
use Illuminate\Support\Facades\Gate;

class ProfessionController extends Controller
{
    public function index(Salon $salon)
    {
        Gate::authorize('view', $salon);
        $items = $salon->professions()->orderBy('name')->get();
        return response()->json($items);
    }

    public function store(StoreProfessionRequest $request, Salon $salon)
    {
        $item = $salon->professions()->create($request->validated());
        return response()->json($item, 201);
    }

    public function show(Salon $salon, Profession $profession)
    {
        Gate::authorize('view', $salon);
        if ($profession->salon_id !== $salon->id) {
            return response()->json(['message' => 'Not Found or Forbidden'], 404);
        }
        return response()->json($profession);
    }

    public function update(UpdateProfessionRequest $request, Salon $salon, Profession $profession)
    {
        if ($profession->salon_id !== $salon->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $profession->update($request->validated());
        return response()->json($profession);
    }

    public function destroy(Salon $salon, Profession $profession)
    {
        Gate::authorize('update', $salon);
        if ($profession->salon_id !== $salon->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $profession->delete();
        return response()->json(null, 204);
    }
}
