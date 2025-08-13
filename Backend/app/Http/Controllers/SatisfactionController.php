<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\CustomerFeedback;
use App\Jobs\SendSatisfactionSurveySms;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SatisfactionController extends Controller
{
    public function showByHash($hash)
    {
        $appointment = Appointment::where('hash', $hash)->firstOrFail();
        $feedback = $appointment->feedback; // Check if feedback already exists

        if ($feedback) {
            return view('appointments.satisfaction', [
                'appointment' => $appointment,
                'success_message' => 'از بازخورد شما سپاسگزاریم! شما قبلاً نظر خود را ثبت کرده‌اید.'
            ]);
        }

        return view('appointments.satisfaction', compact('appointment'));
    }

    public function storeByHash(Request $request, $hash)
    {
        $appointment = Appointment::where('hash', $hash)->firstOrFail();

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'text_feedback' => 'nullable|string',
        ]);

        $appointment->feedback()->create([
            'rating' => $request->rating,
            'text_feedback' => $request->text_feedback,
        ]);

        return view('appointments.satisfaction', [
            'appointment' => $appointment,
            'success_message' => 'از بازخورد شما سپاسگزاریم'
        ]);
    }

    public function sendSurvey(Appointment $appointment)
    {
        // Ensure appointment_date is treated as a date only, and combine with end_time
        $appointment_end_datetime = Carbon::parse($appointment->appointment_date->format('Y-m-d') . ' ' . $appointment->end_time);

        if (Carbon::now()->lessThan($appointment_end_datetime)) {
            return response()->json(['message' => 'هنوز زمان نوبت به پایان نرسیده است.'], 422);
        }

        SendSatisfactionSurveySms::dispatch($appointment);

        return response()->json(['message' => 'پیامک نظرسنجی با موفقیت ارسال شد.']);
    }
}
