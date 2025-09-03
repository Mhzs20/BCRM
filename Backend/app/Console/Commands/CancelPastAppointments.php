<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CancelPastAppointments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:cancel-past';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel past appointments that are still active';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();
        $appointments = \App\Models\Appointment::whereIn('status', ['confirmed', 'pending_confirmation'])
            ->where('appointment_date', '<', $now)
            ->get();

        foreach ($appointments as $appointment) {
            $appointment->update(['status' => 'canceled']);
        }

        $this->info(count($appointments) . ' past appointments have been canceled.');
    }
}
