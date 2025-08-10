<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Salon;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

class AppointmentReportController extends Controller
{
    private function checkAccess(Salon $salon)
    {
        // Check if the authenticated user owns the requested salon
        if (!auth()->user()->salons->contains('id', $salon->id)) {
            abort(403, 'You do not have access to this salon\'s reports.');
        }
    }

    // 1. Time-based Reports
    public function getAppointmentTimeReports(Request $request, Salon $salon)
    {
        $this->checkAccess($salon);

        $request->validate([
            'period' => 'required|in:daily,weekly,monthly,yearly',
            'date' => 'required|jdate:Y-m-d',
        ]);

        $period = $request->input('period');
        $jalaliDate = $request->input('date');
        $date = Jalalian::fromFormat('Y-m-d', $jalaliDate)->toCarbon();

        $report = [];
        $totalAppointments = 0;
        $cancelledAppointments = 0;

        $baseQuery = Appointment::where('salon_id', $salon->id);

        switch ($period) {
            case 'daily':
                $query = (clone $baseQuery)->whereDate('start_time', $date);
                $totalAppointments = $query->count();
                $cancelledAppointments = (clone $query)->where('status', 'cancelled')->count();

                $dbReport = (clone $query)
                    ->select(DB::raw('HOUR(start_time) as unit'), DB::raw('count(*) as total_appointments'))
                    ->groupBy('unit')
                    ->pluck('total_appointments', 'unit');

                for ($hour = 0; $hour < 24; $hour++) {
                    $report[] = [
                        'unit' => 'ساعت ' . $hour,
                        'total_appointments' => $dbReport[$hour] ?? 0,
                    ];
                }
                break;

            case 'weekly':
                $dayOfWeek = $date->dayOfWeek;
                $startOfWeek = $date->copy()->subDays($dayOfWeek)->startOfDay();
                $endOfWeek = $date->copy()->addDays(6 - $dayOfWeek)->endOfDay();

                $query = (clone $baseQuery)->whereBetween('start_time', [$startOfWeek, $endOfWeek]);
                $totalAppointments = $query->count();
                $cancelledAppointments = (clone $query)->where('status', 'cancelled')->count();
                $appointments = $query->get();

                $daysOfWeek = ['شنبه' => 0, 'یکشنبه' => 0, 'دوشنبه' => 0, 'سه‌شنبه' => 0, 'چهارشنبه' => 0, 'پنج‌شنبه' => 0, 'جمعه' => 0];
                $dayMap = ['Saturday' => 'شنبه', 'Sunday' => 'یکشنبه', 'Monday' => 'دوشنبه', 'Tuesday' => 'سه‌شنبه', 'Wednesday' => 'چهارشنبه', 'Thursday' => 'پنج‌شنبه', 'Friday' => 'جمعه'];

                foreach ($appointments as $appointment) {
                    $dayName = $appointment->start_time->format('l');
                    $jalaliDayName = $dayMap[$dayName];
                    $daysOfWeek[$jalaliDayName]++;
                }

                foreach($daysOfWeek as $day => $count) {
                    $report[] = ['unit' => $day, 'total_appointments' => $count];
                }
                break;

            case 'monthly':
                $jalali = Jalalian::fromFormat('Y-m-d', $jalaliDate);
                $startOfMonth = (new Jalalian($jalali->getYear(), $jalali->getMonth(), 1))->toCarbon();
                $endOfMonth = (new Jalalian($jalali->getYear(), $jalali->getMonth(), $jalali->getMonthDays()))->toCarbon()->endOfDay();

                $query = (clone $baseQuery)->whereBetween('start_time', [$startOfMonth, $endOfMonth]);
                $totalAppointments = $query->count();
                $cancelledAppointments = (clone $query)->where('status', 'cancelled')->count();
                $appointments = $query->get();

                $weeksOfMonth = ['هفته اول' => 0, 'هفته دوم' => 0, 'هفته سوم' => 0, 'هفته چهارم' => 0, 'هفته پنجم' => 0];
                $weekNames = array_keys($weeksOfMonth);

                foreach ($appointments as $appointment) {
                    $dayOfMonth = Jalalian::fromCarbon($appointment->start_time)->getDay();
                    $weekIndex = (int)ceil($dayOfMonth / 7) - 1;
                    if ($weekIndex >= 4) $weekIndex = 4; // Cap at 5th week (index 4)
                    $weeksOfMonth[$weekNames[$weekIndex]]++;
                }

                foreach($weeksOfMonth as $week => $count) {
                    $report[] = ['unit' => $week, 'total_appointments' => $count];
                }
                break;

            case 'yearly':
                $j_year = Jalalian::fromFormat('Y-m-d', $jalaliDate)->getYear();
                $start_date = (new Jalalian($j_year, 1, 1))->toCarbon();
                $end_date = (new Jalalian($j_year, 12, (new Jalalian($j_year, 12, 1))->getMonthDays()))->toCarbon();

                $query = (clone $baseQuery)->whereBetween('start_time', [$start_date, $end_date]);
                $totalAppointments = $query->count();
                $cancelledAppointments = (clone $query)->where('status', 'cancelled')->count();

                $jalaliMonths = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
                $monthlyCounts = array_fill_keys($jalaliMonths, 0);

                $appointments = $query->get();
                foreach($appointments as $appointment) {
                    $monthIndex = Jalalian::fromCarbon($appointment->start_time)->getMonth() - 1;
                    $monthlyCounts[$jalaliMonths[$monthIndex]]++;
                }

                foreach($monthlyCounts as $month => $count) {
                    $report[] = [
                        'unit' => $month,
                        'total_appointments' => $count
                    ];
                }
                break;
        }

        $cancellationRate = $totalAppointments > 0 ? ($cancelledAppointments / $totalAppointments) * 100 : 0;

        return response()->json([
            'period' => $period,
            'date' => $jalaliDate,
            'total_appointments' => $totalAppointments,
            'cancelled_appointments' => $cancelledAppointments,
            'cancellation_rate' => round($cancellationRate, 2),
            'report' => $report,
        ]);
    }

    // 2. Overall Status Reports
    public function getOverallAppointmentStatusReports(Request $request, Salon $salon)
    {
        $this->checkAccess($salon);

        $request->validate([
            'start_date' => 'required|jdate:Y-m-d',
        ]);

        $jalaliStartDate = $request->input('start_date');
        $startDate = Jalalian::fromFormat('Y-m-d', $jalaliStartDate)->toCarbon();

        $baseQuery = Appointment::where('salon_id', $salon->id)->where('start_time', '>=', $startDate);

        $totalAppointments = (clone $baseQuery)->count();
        $cancelledAppointments = (clone $baseQuery)->where('status', 'cancelled')->count();
        $completedAppointments = (clone $baseQuery)->where('status', 'completed')->count();
        $cancellationPercentage = $totalAppointments > 0 ? ($cancelledAppointments / $totalAppointments) * 100 : 0;

        $dailyAverage = (clone $baseQuery)
            ->select(DB::raw('DATE(start_time) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->get()->avg('count');

        $dailyAverageCompleted = (clone $baseQuery)->where('status', 'done')
            ->select(DB::raw('DATE(start_time) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->get()->avg('count');

        $peakTimes = (clone $baseQuery)
            ->select(DB::raw('HOUR(start_time) as hour'), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->first();

        $offPeakTimes = (clone $baseQuery)
            ->select(DB::raw('HOUR(start_time) as hour'), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->orderBy('count', 'asc')
            ->first();

        $mostRequestedService = DB::table('appointment_service')
            ->join('appointments', 'appointment_service.appointment_id', '=', 'appointments.id')
            ->where('appointments.salon_id', $salon->id)
            ->where('appointments.start_time', '>=', $startDate)
            ->select('appointment_service.service_id', DB::raw('count(*) as count'))
            ->groupBy('service_id')
            ->orderBy('count', 'desc')
            ->first();

        $serviceName = $mostRequestedService ? Service::find($mostRequestedService->service_id)->name : null;


        return response()->json([
            'total_appointments' => $totalAppointments,
            'cancelled_appointments' => $cancelledAppointments,
            'completed_appointments' => $completedAppointments,
            'cancellation_percentage' => round($cancellationPercentage, 2),
            'daily_average_appointments' => round($dailyAverage, 2),
            'daily_average_completed_appointments' => round($dailyAverageCompleted, 2),
            'peak_time_hour' => $peakTimes ? $peakTimes->hour : null,
            'off_peak_time_hour' => $offPeakTimes ? $offPeakTimes->hour : null,
            'most_requested_service' => $serviceName,
        ]);
    }

    // 3. Analytical Reports
    public function getAnalyticalReports(Request $request, Salon $salon)
    {
        $this->checkAccess($salon);

        $cancellationTrendByDay = Appointment::where('salon_id', $salon->id)->where('status', 'cancelled')
            ->select(DB::raw('DAYNAME(start_time) as day'), DB::raw('count(*) as count'))
            ->groupBy('day')
            ->orderBy('count', 'desc')
            ->get();

        $cancellationTrendByHour = Appointment::where('salon_id', $salon->id)->where('status', 'cancelled')
            ->select(DB::raw('HOUR(start_time) as hour'), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->get();

        $demandPeakByService = DB::table('appointment_service')
            ->join('appointments', 'appointment_service.appointment_id', '=', 'appointments.id')
            ->join('services', 'appointment_service.service_id', '=', 'services.id')
            ->where('appointments.salon_id', $salon->id)
            ->select('services.name as service_name', DB::raw('count(*) as count'))
            ->groupBy('service_name')
            ->orderBy('count', 'desc')
            ->get();


        return response()->json([
            'cancellation_trend_by_day' => $cancellationTrendByDay,
            'cancellation_trend_by_hour' => $cancellationTrendByHour,
            'demand_peak_by_service' => $demandPeakByService,
        ]);
    }

    // 4. Detailed Reports
    public function getDetailedReports(Request $request, Salon $salon)
    {
        $this->checkAccess($salon);

        $mostRequestedDays = Appointment::where('salon_id', $salon->id)
            ->select(DB::raw('DAYNAME(start_time) as day'), DB::raw('count(*) as count'))
            ->groupBy('day')
            ->orderBy('count', 'desc')
            ->get();

        $leastRequestedDays = Appointment::where('salon_id', $salon->id)
            ->select(DB::raw('DAYNAME(start_time) as day'), DB::raw('count(*) as count'))
            ->groupBy('day')
            ->orderBy('count', 'asc')
            ->get();

        $mostRequestedHours = Appointment::where('salon_id', $salon->id)
            ->select(DB::raw('HOUR(start_time) as hour'), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->get();

        $leastRequestedHours = Appointment::where('salon_id', $salon->id)
            ->select(DB::raw('HOUR(start_time) as hour'), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->orderBy('count', 'asc')
            ->get();

        $mostRequestedService = DB::table('appointment_service')
            ->join('appointments', 'appointment_service.appointment_id', '=', 'appointments.id')
            ->join('services', 'appointment_service.service_id', '=', 'services.id')
            ->where('appointments.salon_id', $salon->id)
            ->select('services.name as service_name', DB::raw('count(*) as count'))
            ->groupBy('service_name')
            ->orderBy('count', 'desc')
            ->first();

        return response()->json([
            'most_requested_days' => $mostRequestedDays,
            'least_requested_days' => $leastRequestedDays,
            'most_requested_hours' => $mostRequestedHours,
            'least_requested_hours' => $leastRequestedHours,
            'most_requested_service' => $mostRequestedService,
        ]);
    }

    // 5. Daily Summary Report
    public function getDailySummaryReport(Request $request, Salon $salon)
    {
        $this->checkAccess($salon);

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // Data for today
        $newAppointmentsToday = Appointment::where('salon_id', $salon->id)
            ->whereDate('created_at', $today)
            ->count();

        $newCustomersToday = Customer::where('salon_id', $salon->id)
            ->whereDate('created_at', $today)
            ->count();

        $totalIncomeToday = Payment::where('salon_id', $salon->id)
            ->whereDate('created_at', $today)
            ->sum('amount');

        // Data for yesterday
        $newAppointmentsYesterday = Appointment::where('salon_id', $salon->id)
            ->whereDate('created_at', $yesterday)
            ->count();

        $newCustomersYesterday = Customer::where('salon_id', $salon->id)
            ->whereDate('created_at', $yesterday)
            ->count();

        $totalIncomeYesterday = Payment::where('salon_id', $salon->id)
            ->whereDate('created_at', $yesterday)
            ->sum('amount');

        // Calculate growth percentages
        $appointmentGrowth = $this->calculateGrowth($newAppointmentsToday, $newAppointmentsYesterday);
        $customerGrowth = $this->calculateGrowth($newCustomersToday, $newCustomersYesterday);
        $incomeGrowth = $this->calculateGrowth($totalIncomeToday, $totalIncomeYesterday);

        return response()->json([
            'date' => Jalalian::fromCarbon($today)->format('Y/m/d'),
            'new_appointments_today' => $newAppointmentsToday,
            'new_customers_today' => $newCustomersToday,
            'total_income_today' => $totalIncomeToday,
            'appointment_growth_percentage' => round($appointmentGrowth, 2),
            'customer_growth_percentage' => round($customerGrowth, 2),
            'income_growth_percentage' => round($incomeGrowth, 2),
        ]);
    }

    private function calculateGrowth($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0; // If previous was 0 and current is > 0, it's 100% growth. If both are 0, 0% growth.
        }
        return (($current - $previous) / $previous) * 100;
    }
}
