<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Hashids\Hashids;

class AppointmentDetailsController extends Controller
{
    public function showByHash($hash)
    {
        $hashids = new Hashids(env('HASHIDS_SALT', 'your-default-salt'), 8);
        $ids = $hashids->decode($hash);

        if (empty($ids)) {
            abort(404);
        }

        $appointment = Appointment::findOrFail($ids[0]);
        $appointment->load(['customer', 'salon', 'services', 'staff']);
        
        $response = response(view('appointments.details', compact('appointment')));
        $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', '0');

        return $response;
    }
}
