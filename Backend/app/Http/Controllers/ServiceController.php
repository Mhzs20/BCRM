<?php

namespace App\Http\Controllers;

use App\Models\Salon;
use App\Models\Service;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ServiceController extends Controller
{
    /**
     */
    public function index(Request $request, Salon $salon)
    {
        $this->authorize('viewAny', [Service::class, $salon]);

        $sortBy = $request->input('sort_by', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');

        $services = $salon->services()
            ->with('staff:id,full_name')
            ->withCount('appointments');

        if ($sortBy === 'appointments_count') {
            $services->orderBy('appointments_count', $sortDirection);
        } else {
            $services->orderBy($sortBy, $sortDirection);
        }

        $paginatedServices = $services->paginate($request->input('per_page', 100));

        return response()->json($paginatedServices);
    }

    /**
     */
    public function store(StoreServiceRequest $request, Salon $salon)
    {
        try {
            $serviceData = Arr::except($request->validated(), ['staff_ids']);
            $service = $salon->services()->create($serviceData);

            if ($request->has('staff_ids')) {
                $service->staff()->attach($request->validated()['staff_ids']);
            }

            $service->load('staff:id,full_name');

            return response()->json(['message' => 'خدمت با موفقیت ایجاد شد.', 'data' => $service], 201);
        } catch (\Exception $e) {
            Log::error('Service store failed: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در ایجاد خدمت.'], 500);
        }
    }

    /**
     */
    public function show(Salon $salon, Service $service)
    {
        // $this->authorize('view', $service);
        $service->load('staff:id,full_name');
        return response()->json($service);
    }

    /**
     */
    public function update(UpdateServiceRequest $request, Salon $salon, Service $service)
    {
        try {
            $validatedData = $request->validated();

            // Filter validated data to only include keys present in the request input.
            $updateData = collect($validatedData)->filter(function ($value, $key) use ($request) {
                return $request->exists($key);
            })->toArray();

            if (Arr::except($updateData, ['staff_ids'])) {
                $service->update(Arr::except($updateData, ['staff_ids']));
            }

            if (array_key_exists('staff_ids', $updateData)) {
                $service->staff()->sync($updateData['staff_ids']);
            }

            $service->refresh()->load('staff:id,full_name');

            return response()->json(['message' => 'اطلاعات خدمت با موفقیت به‌روزرسانی شد.', 'data' => $service]);
        } catch (\Exception $e) {
            Log::error('Service update failed: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در به‌روزرسانی اطلاعات خدمت.'], 500);
        }
    }

    /**
     */
    public function destroy(Salon $salon, Service $service)
    {
        // $this->authorize('delete', $service);

        try {
            $service->delete();
            return response()->json(['message' => 'خدمت با موفقیت حذف شد.'], 200);
        } catch (\Exception $e) {
            Log::error('Service delete failed: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در حذف خدمت.'], 500);
        }
    }

    /**
     * Get a list of active services with their booking statistics.
     */
    public function getBookingList(Request $request, Salon $salon)
    {
        $services = $salon->services()
            ->where('is_active', true)
            ->withCount([
                'appointments as total_bookings_count',
                'appointments as active_bookings_count' => function ($query) {
                    $query->whereIn('status', ['confirmed', 'pending']);
                }
            ])
            ->orderBy('name', 'asc')
            ->get();

        return response()->json(['data' => $services]);
    }

    /**
     * Search for services within a salon.
     */
    public function search(Request $request, Salon $salon)
    {
        $this->authorize('viewAny', [Service::class, $salon]);

        $query = $salon->services();

        if ($request->filled('q')) {
            $searchTerm = $request->input('q');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        $sortBy = $request->input('sort_by', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');

        $services = $query->orderBy($sortBy, $sortDirection)
            ->paginate($request->input('per_page', 100));

        return response()->json($services);
    }
}
