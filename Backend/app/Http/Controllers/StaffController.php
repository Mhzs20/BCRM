<?php

namespace App\Http\Controllers;

use App\Models\Salon;
use App\Models\Staff;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StaffController extends Controller
{
    public function index(Request $request, Salon $salon)
    {
        $this->authorize('viewAny', [Staff::class, $salon]);
        $query = $salon->staff()->with(['services:id,name', 'schedules']);
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        $staffMembers = $query->orderBy($request->input('sort_by', 'full_name'), $request->input('sort_direction', 'asc'))
            ->paginate($request->input('per_page', 15));
        return response()->json($staffMembers);
    }

    public function store(StoreStaffRequest $request, Salon $salon)
    {
        $validatedData = $request->validated();
        try {
            $staffData = collect($validatedData)->except(['service_ids', 'schedules'])->toArray();
            if ($request->hasFile('profile_image')) {
                $staffData['profile_image'] = $request->file('profile_image')->store('staff_profiles', 'public');
            }
            $staff = $salon->staff()->create($staffData);
            if (!empty($validatedData['service_ids'])) {
                $staff->services()->sync($validatedData['service_ids']);
            }
            if (!empty($validatedData['schedules'])) {
                $this->syncSchedules($staff, $validatedData['schedules']);
            }
            $staff->load(['services:id,name', 'schedules']);
            return response()->json([
                'success' => true,
                'message' => 'پرسنل جدید با موفقیت ثبت شد.',
                'data' => $staff
            ], 201);
        } catch (\Exception $e) {
            Log::error('Staff store failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'عملیات ثبت پرسنل با خطا مواجه شد. لطفا دوباره تلاش کنید.',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.'
            ], 500);
        }
    }

    public function show(Salon $salon, Staff $staff)
    {
        $this->authorize('view', $staff);
        $staff->load(['services:id,name', 'schedules', 'appointments']);
        return response()->json($staff);
    }

    public function update(UpdateStaffRequest $request, Salon $salon, Staff $staff)
    {
        $validatedData = $request->validated();

        // Filter validated data to only include keys present in the request input or files.
        $updateData = collect($validatedData)->filter(function ($value, $key) use ($request) {
            return $request->exists($key) || $request->hasFile($key);
        })->toArray();

        if ($request->hasFile('profile_image')) {
            if ($staff->profile_image) {
                Storage::disk('public')->delete($staff->profile_image);
            }
            $updateData['profile_image'] = $request->file('profile_image')->store('staff_profiles', 'public');
        } elseif ($request->input('remove_profile_image') == true) {
            if ($staff->profile_image) {
                Storage::disk('public')->delete($staff->profile_image);
            }
            $updateData['profile_image'] = null;
        }

        try {
            $staff->update(collect($updateData)->except(['service_ids', 'schedules'])->toArray());

            if (array_key_exists('service_ids', $updateData)) {
                $staff->services()->sync($updateData['service_ids']);
            }
            if (array_key_exists('schedules', $updateData)) {
                $this->syncSchedules($staff, $updateData['schedules']);
            }

            $staff->refresh()->load(['services:id,name', 'schedules']);
            return response()->json([
                'success' => true,
                'message' => 'اطلاعات پرسنل با موفقیت به‌روزرسانی شد.',
                'data' => $staff
            ]);
        } catch (\Exception $e) {
            Log::error('Staff update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'عملیات به‌روزرسانی با خطا مواجه شد. لطفا دوباره تلاش کنید.',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.'
            ], 500);
        }
    }

    public function destroy(Salon $salon, Staff $staff)
    {
        $this->authorize('delete', $staff);
        try {
            if ($staff->appointments()->whereIn('status', ['confirmed', 'pending'])->exists()) {
                return response()->json(['message' => 'این پرسنل دارای نوبت‌های فعال است و قابل حذف نیست.'], 403);
            }
            if ($staff->profile_image) {
                Storage::disk('public')->delete($staff->profile_image);
            }
            $staff->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error('Staff delete failed: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در حذف پرسنل.'], 500);
        }
    }

    private function syncSchedules(Staff $staff, array $schedulesData)
    {
        $staff->schedules()->delete();
        $newSchedules = [];
        foreach ($schedulesData as $day => $details) {
            if (isset($details['start'], $details['end'])) {
                $newSchedules[] = [
                    'day_of_week' => $day,
                    'start_time'  => $details['start'],
                    'end_time'    => $details['end'],
                    'is_active'   => $details['active'] ?? false,
                ];
            }
        }
        if (!empty($newSchedules)) {
            $staff->schedules()->createMany($newSchedules);
        }
    }

    public function getBookingList(Request $request, Salon $salon)
    {
        $this->authorize('viewAny', [Staff::class, $salon]);

        $staffIds = $salon->staff()->pluck('id');

        $query = \App\Models\Appointment::whereIn('staff_id', $staffIds);

        // Eager load related data for better performance
        $query->with(['client:id,full_name', 'staff:id,full_name', 'services:id,name']);

        if ($request->has('date')) {
            $query->whereDate('appointment_datetime', $request->date);
        }

        $bookings = $query->orderBy('appointment_datetime', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json($bookings);
    }
}
