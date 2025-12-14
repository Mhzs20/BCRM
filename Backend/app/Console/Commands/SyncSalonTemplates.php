<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Salon;
use App\Models\Profession;
use App\Models\HowIntroduced;
use App\Models\CustomerGroup;

class SyncSalonTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'salon:sync-templates {--force : Force sync even if salon has data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync global templates (Professions, HowIntroduced, CustomerGroups) to existing salons';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting template sync for existing salons...');

        $salons = Salon::all();
        $templateProfessions = Profession::whereNull('salon_id')->get();
        $templateHowIntroduceds = HowIntroduced::whereNull('salon_id')->get();
        $templateCustomerGroups = CustomerGroup::whereNull('salon_id')->get();

        $bar = $this->output->createProgressBar($salons->count());
        $bar->start();

        foreach ($salons as $salon) {
            // Sync Professions
            foreach ($templateProfessions as $template) {
                $exists = $salon->professions()->where('name', $template->name)->exists();
                if (!$exists) {
                    $salon->professions()->create(['name' => $template->name]);
                }
            }

            // Sync HowIntroduced
            foreach ($templateHowIntroduceds as $template) {
                $exists = $salon->howIntroduceds()->where('name', $template->name)->exists();
                if (!$exists) {
                    $salon->howIntroduceds()->create(['name' => $template->name]);
                }
            }

            // Sync CustomerGroups
            foreach ($templateCustomerGroups as $template) {
                $exists = $salon->customerGroups()->where('name', $template->name)->exists();
                if (!$exists) {
                    $salon->customerGroups()->create(['name' => $template->name]);
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Sync completed successfully.');
    }
}
