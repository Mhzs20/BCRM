<?php

namespace App\Http\Controllers;

use App\Models\Salon;
use App\Models\Service;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ServiceController extends Controller
{
    /**
     * Display a listing of services for a specific salon.
     */
    public function index(Request $request, Salon $salon)
    {

        $this->authorize('viewAny', [Service::class, $salon]);

        $services = $salon->services()
        ->orderBy($request->input('sort_by', 'name'), $request->input('sort_direction', 'asc'))
            ->paginate($request->input('per_page', 15));

        return response()->json($services);
    }

    /**
     * Store a newly created service in storage for a specific salon.
     */
    public function store(StoreServiceRequest $request, Salon $salon)
    {


        try {
            $service = $salon->services()->create($request->validated());

            return response()->json(['message' => 'خدمت با موفقیت ایجاد شد.', 'data' => $service], 201);
        } catch (\Exception $e) {
            Log::error('Service store failed: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در ایجاد خدمت.'], 500);
        }
    }

    /**
     * Display the specified service.
     */
    public function show(Salon $salon, Service $service)
    {
        $this->authorize('view', $service);

        $service->load('staff:id,full_name');
        return response()->json($service);
    }

    /**
     * Update the specified service in storage.
     */
    public function update(UpdateServiceRequest $request, Salon $salon, Service $service)
    {

        try {
            $service->update($request->validated());

            $service->refresh()->load('staff:id,full_name');
            return response()->json(['message' => 'اطلاعات خدمت با موفقیت به‌روزرسانی شد.', 'data' => $service]);
        } catch (\Exception $e) {
            Log::error('Service update failed: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در به‌روزرسانی اطلاعات خدمت.'], 500);
        }
    }

    /**
     * Remove the specified service from storage.
     */
    public function destroy(Salon $salon, Service $service)
    {
        $this->authorize('delete', $service);

        try {
            $service->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error('Service delete failed: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در حذف خدمت.'], 500);
        }
    }

    /**
     * Get a list of active services suitable for booking.
     */
    public function getBookingList(Request $request, Salon $salon)
    {

        $this->authorize('viewAny', [Service::class, $salon]);

        $services = $salon->services()
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->select(['id', 'name', 'price', 'duration_minutes', 'description'])
            ->get();

        return response()->json(['data' => $services]);
    }
}
