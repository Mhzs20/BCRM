<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUpdate;
use App\Models\Appointment; // Import Appointment model
use App\Models\Staff; // Import Staff model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Import Auth facade

class AppController extends Controller
{
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

        $appointments = Appointment::where('staff_id', $staffId)
            ->with(['customer', 'services', 'salon']) // Eager load related data
            ->orderBy('appointment_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();

        if ($appointments->isEmpty()) {
            return response()->json(['message' => 'No appointments found for this staff member.'], 404);
        }

        return response()->json($appointments);
    }
}
