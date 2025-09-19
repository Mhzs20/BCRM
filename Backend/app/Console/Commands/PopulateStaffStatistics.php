<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Staff;

class PopulateStaffStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:populate-staff-statistics {--salon_id= : The salon ID to populate statistics for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate staff statistics for existing data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $salonId = $this->option('salon_id');

        $query = Staff::query();

        if ($salonId) {
            $query->where('salon_id', $salonId);
        }

        $staffMembers = $query->get();

        $this->info('Starting to populate staff statistics...');

        $bar = $this->output->createProgressBar($staffMembers->count());
        $bar->start();

        foreach ($staffMembers as $staff) {
            $staff->updateStatistics();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info('Staff statistics populated successfully!');
    }
}
