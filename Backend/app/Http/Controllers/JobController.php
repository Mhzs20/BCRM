<?php

namespace App\Http\Controllers;

use App\Models\Salon;
use App\Models\Job;
use App\Http\Requests\StoreJobRequest;
use App\Http\Requests\UpdateJobRequest;
use Illuminate\Support\Facades\Gate;

class JobController extends Controller
{
    public function index(Salon $salon)
    {
        Gate::authorize('view', $salon);
        $items = $salon->jobs()->orderBy('name')->get();
        return response()->json($items);
    }

    public function store(StoreJobRequest $request, Salon $salon)
    {
        $item = $salon->jobs()->create($request->validated());
        return response()->json($item, 201);
    }

    public function show(Salon $salon, Job $job)
    {
        Gate::authorize('view', $salon);
        if ($job->salon_id !== $salon->id) {
            return response()->json(['message' => 'Not Found or Forbidden'], 404);
        }
        return response()->json($job);
    }

    public function update(UpdateJobRequest $request, Salon $salon, Job $job)
    {
        if ($job->salon_id !== $salon->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $job->update($request->validated());
        return response()->json($job);
    }

    public function destroy(Salon $salon, Job $job)
    {
        Gate::authorize('update', $salon);
        if ($job->salon_id !== $salon->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $job->delete();
        return response()->json(null, 204);
    }
}
