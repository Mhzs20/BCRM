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

        if ($request->hasFile('profile_image')) {
            $validatedData['profile_image'] = $request->file('profile_image')->store('staff_profiles', 'public');
        }

        try {
            $staff = $salon->staff()->create($validatedData);

            if (!empty($validatedData['service_ids'])) {
                $staff->services()->sync($validatedData['service_ids']);
            }

            if (!empty($validatedData['schedules'])) {
                $staff->schedules()->createMany($validatedData['schedules']);
            }

            $staff->load(['services:id,name', 'schedules']);
            return response()->json(['message' => 'پرسنل با موفقیت ایجاد شد.', 'data' => $staff], 201);
        } catch (\Exception $e) {
            Log::error('Staff store failed: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در ایجاد پرسنل.'], 500);
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

        if ($request->hasFile('profile_image')) {
            if ($staff->profile_image) {
                Storage::disk('public')->delete($staff->profile_image);
            }
            $validatedData['profile_image'] = $request->file('profile_image')->store('staff_profiles', 'public');
        } elseif ($request->input('remove_profile_image') == true) {
            if ($staff->profile_image) {
                Storage::disk('public')->delete($staff->profile_image);
            }
            $validatedData['profile_image'] = null;
        }

        try {
            $staff->update($validatedData);

            if (isset($validatedData['service_ids'])) {
                $staff->services()->sync($validatedData['service_ids']);
            }

            if (isset($validatedData['schedules'])) {
                $staff->schedules()->delete(); // Delete old schedules
                $staff->schedules()->createMany($validatedData['schedules']); // Create new ones
            }

            $staff->refresh()->load(['services:id,name', 'schedules']);
            return response()->json(['message' => 'اطلاعات پرسنل با موفقیت به‌روزرسانی شد.', 'data' => $staff]);
        } catch (\Exception $e) {
            Log::error('Staff update failed: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در به‌روزرسانی اطلاعات پرسنل.'], 500);
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
}
