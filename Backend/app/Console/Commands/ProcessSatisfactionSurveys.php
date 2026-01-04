<?php

namespace App\Console\Commands;

use App\Jobs\ProcessScheduledSatisfactionSurveys;
use Illuminate\Console\Command;

class ProcessSatisfactionSurveys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'satisfaction:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and send scheduled satisfaction survey SMS messages';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting satisfaction survey processing...');
        
        ProcessScheduledSatisfactionSurveys::dispatch();
        
        $this->info('Satisfaction survey processing job dispatched successfully.');
        
        return 0;
    }
}
