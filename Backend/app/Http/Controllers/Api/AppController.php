<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUpdate;
use App\Models\Appointment; // Import Appointment model
use App\Models\Staff; // Import Staff model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Import Auth facade
use App\Models\UserSmsBalance; // Import UserSmsBalance model
use App\Models\Salon; // Import Salon model

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
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Assuming a user can be associated with a salon, and the salon owner has the smsBalance.
        // If the user is a salon owner, their smsBalance is directly accessible.
        // If the user is a staff member, we might need to get the salon owner's balance.
        // For simplicity, let's assume the authenticated user is the one whose balance we need.
        // The User model should have a hasOne relationship with UserSmsBalance.
        $user->loadMissing('smsBalance');

        if ($user->smsBalance) {
            return response()->json([
                'balance' => $user->smsBalance->balance,
                'message' => 'SMS balance retrieved successfully.'
            ]);
        }

        return response()->json(['message' => 'SMS balance not found for this user.'], 404);
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
}
