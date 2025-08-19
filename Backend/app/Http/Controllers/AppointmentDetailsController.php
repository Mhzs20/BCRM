<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentDetailsController extends Controller
{
    public function showByHash($hash)
    {
        $appointment = Appointment::where('hash', $hash)->firstOrFail();
        $appointment->load(['customer', 'salon', 'services', 'staff']);
        // Here you would typically return a view with the appointment details
        return view('appointments.details', compact('appointment'));
    }
}
