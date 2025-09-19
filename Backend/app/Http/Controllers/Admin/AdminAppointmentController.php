<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Salon;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

class AdminAppointmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Appointment::with(['salon.user', 'customer', 'staff', 'services']);

        // فیلتر بر اساس سالن
        if ($request->filled('salon_id')) {
            $query->where('salon_id', $request->salon_id);
        }

        // فیلتر بر اساس وضعیت
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فیلتر بر اساس تاریخ
        if ($request->filled('date_from')) {
            $query->whereDate('appointment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('appointment_date', '<=', $request->date_to);
        }

        // فیلتر بر اساس تاریخ امروز
        if ($request->has('today')) {
            $query->whereDate('appointment_date', Carbon::today());
        }

        $appointments = $query->orderBy('appointment_date', 'desc')
                             ->orderBy('start_time', 'desc')
                             ->paginate(20);

        $salons = Salon::with('user')->get();

        // وضعیت‌های موجود
        $statuses = Appointment::select('status')
                              ->distinct()
                              ->whereNotNull('status')
                              ->pluck('status')
                              ->toArray();

        return view('admin.appointments.index', compact('appointments', 'salons', 'statuses'));
    }

    public function show(Appointment $appointment)
    {
        $appointment->load(['salon.user', 'customer', 'staff', 'services']);

        return view('admin.appointments.show', compact('appointment'));
    }

    public function updateStatus(Request $request, Appointment $appointment)
    {
        $request->validate([
            'status' => 'required|string|in:pending,completed,cancelled,pending_confirmation,no_show'
        ]);

        $appointment->update([
            'status' => $request->status
        ]);

        return redirect()->back()->with('success', 'وضعیت نوبت با موفقیت بروزرسانی شد.');
    }
}
