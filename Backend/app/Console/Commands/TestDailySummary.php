<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use App\Models\Salon;
use App\Models\Customer;
use App\Models\Payment;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

class TestDailySummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:daily-summary {salon_id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the daily summary report logic';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $salonId = $this->argument('salon_id');

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // Data for today
        $newAppointmentsToday = Appointment::where('salon_id', $salonId)
            ->whereDate('appointment_date', $today)
            ->count();

        $newCustomersToday = Customer::where('salon_id', $salonId)
            ->whereDate('created_at', $today)
            ->count();

        $totalIncomeToday = Payment::where('salon_id', $salonId)
            ->whereDate('created_at', $today)
            ->sum('amount');

        // Data for yesterday
        $newAppointmentsYesterday = Appointment::where('salon_id', $salonId)
            ->whereDate('appointment_date', $yesterday)
            ->count();

        $newCustomersYesterday = Customer::where('salon_id', $salonId)
            ->whereDate('created_at', $yesterday)
            ->count();

        $totalIncomeYesterday = Payment::where('salon_id', $salonId)
            ->whereDate('created_at', $yesterday)
            ->sum('amount');

        // Calculate growth percentages
        $appointmentGrowth = $this->calculateGrowth($newAppointmentsToday, $newAppointmentsYesterday);
        $customerGrowth = $this->calculateGrowth($newCustomersToday, $newCustomersYesterday);
        $incomeGrowth = $this->calculateGrowth($totalIncomeToday, $totalIncomeYesterday);

        $result = [
            'date' => Jalalian::fromCarbon($today)->format('Y/m/d'),
            'new_appointments_today' => $newAppointmentsToday,
            'new_customers_today' => $newCustomersToday,
            'total_income_today' => $totalIncomeToday,
            'appointment_growth_percentage' => round($appointmentGrowth, 2),
            'customer_growth_percentage' => round($customerGrowth, 2),
            'income_growth_percentage' => round($incomeGrowth, 2),
        ];

        $this->info(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function calculateGrowth($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return (($current - $previous) / $previous) * 100;
    }
}