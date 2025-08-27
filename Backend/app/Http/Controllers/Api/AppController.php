<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUpdate;
use App\Models\Appointment; // Import Appointment model
use App\Models\Staff; // Import Staff model
use App\Models\Province; // Import Province model
use App\Models\City; // Import City model
use App\Models\BusinessCategory; // Import BusinessCategory model
use App\Models\BusinessSubcategory; // Import BusinessSubcategory model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Import Auth facade
use App\Models\UserSmsBalance; // Import UserSmsBalance model
use App\Models\Salon; // Import Salon model
use Illuminate\Support\Facades\Log; // Import Log facade

class AppController extends Controller
{
    /**
     * Get the authenticated user's SMS balance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSmsBalance(Request $request)
    {
        $user = Auth::user()->load('activeSalon.smsBalance');

        if (!$user) {
            Log::info('AppController@getSmsBalance: Unauthenticated user.');
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        Log::info('AppController@getSmsBalance: User ID: ' . $user->id . ', Active Salon ID: ' . $user->active_salon_id);

        $salon = $user->activeSalon;

        if ($salon) {
            Log::info('AppController@getSmsBalance: Active Salon found. Salon ID: ' . $salon->id . ', Is Active: ' . (bool) $salon->is_active);
            $balance = $salon->smsBalance ? $salon->smsBalance->balance : 0;
            Log::info('AppController@getSmsBalance: Salon SMS Balance: ' . $balance);

            return response()->json([
                'balance' => $balance,
                'is_active' => (bool) $salon->is_active,
                'message' => 'SMS balance retrieved successfully for active salon.'
            ]);
        }

        Log::info('AppController@getSmsBalance: No active salon found for user ID: ' . $user->id);
        return response()->json(['message' => 'No active salon found for this user.'], 404);
    }

    public function latestHistory()
    {
        $latestUpdate = AppUpdate::latest()->first();

        if ($latestUpdate) {
            return response()->json($latestUpdate);
        }

        return response()->json(['message' => 'No update information found.'], 404);
    }

    /**
     * Get appointments for a specific staff member.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $staffId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStaffAppointments(Request $request, $staffId)
    {
        // Optional: Add authorization check here if only the staff member themselves
        // or an admin should be able to view their appointments.
        // For example:
        // if (Auth::user()->staff_id !== $staffId && !Auth::user()->is_admin) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        $query = Appointment::where('staff_id', $staffId)
            ->with(['customer', 'services', 'salon']);

        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        $appointments = $query->orderBy('appointment_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();

        if ($appointments->isEmpty()) {
            return response()->json(['message' => 'No appointments found for this staff member.'], 404);
        }

        return \App\Http\Resources\AppointmentResource::collection($appointments);
    }

    /**
     * Get cities by province ID.
     *
     * @param  int  $provinceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCitiesByProvince($provinceId)
    {
        $cities = City::where('province_id', $provinceId)->get(['id', 'name']);
        return response()->json($cities);
    }

    /**
     * Get business subcategories by business category ID.
     *
     * @param  int  $categoryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSubcategoriesByCategory($categoryId)
    {
        $subcategories = BusinessSubcategory::where('business_category_id', $categoryId)->get(['id', 'name']);
        return response()->json($subcategories); // This already returns a JSON array.
    }
}
