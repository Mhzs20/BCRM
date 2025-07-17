<?php

namespace App\Http\Controllers;

use App\Models\Salon;
use App\Models\CustomerGroup;
use App\Http\Requests\StoreCustomerGroupRequest;
use App\Http\Requests\UpdateCustomerGroupRequest;
use Illuminate\Support\Facades\Gate;

class CustomerGroupController extends Controller
{
    public function index(Salon $salon)
    {
        Gate::authorize('view', $salon);
        $items = $salon->customerGroups()->orderBy('name')->get();
        return response()->json($items);
    }

    public function store(StoreCustomerGroupRequest $request, Salon $salon)
    {
        $item = $salon->customerGroups()->create($request->validated());
        return response()->json($item, 201);
    }

    public function show(Salon $salon, CustomerGroup $customerGroup)
    {
        Gate::authorize('view', $salon);
        if ($customerGroup->salon_id !== $salon->id) {
            return response()->json(['message' => 'Not Found or Forbidden'], 404);
        }
        return response()->json($customerGroup);
    }

    public function update(UpdateCustomerGroupRequest $request, Salon $salon, CustomerGroup $customerGroup)
    {
        if ($customerGroup->salon_id !== $salon->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $customerGroup->update($request->validated());
        return response()->json($customerGroup);
    }

    public function destroy(Salon $salon, CustomerGroup $customerGroup)
    {
        Gate::authorize('update', $salon);
        if ($customerGroup->salon_id !== $salon->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $customerGroup->delete();
        return response()->json(null, 204);
    }
}
