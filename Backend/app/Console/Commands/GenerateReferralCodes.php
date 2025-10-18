<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateReferralCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'referral:generate-codes {--dry-run : Show what would be done without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate referral codes for users who don\'t have one';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        // Get users without referral codes
        $usersWithoutCodes = User::whereNull('referral_code')->get();
        
        if ($usersWithoutCodes->isEmpty()) {
            $this->info('All users already have referral codes!');
            return;
        }
        
        $this->info("Found {$usersWithoutCodes->count()} users without referral codes.");
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->table(['ID', 'Mobile', 'Name'], 
                $usersWithoutCodes->take(10)->map(function($user) {
                    return [$user->id, $user->mobile, $user->name ?: 'No name'];
                })->toArray()
            );
            if ($usersWithoutCodes->count() > 10) {
                $this->info("... and " . ($usersWithoutCodes->count() - 10) . " more users");
            }
            return;
        }
        
        $confirmed = $this->confirm("Do you want to generate referral codes for {$usersWithoutCodes->count()} users?");
        
        if (!$confirmed) {
            $this->info('Operation cancelled.');
            return;
        }
        
        $this->info('Generating referral codes...');
        $bar = $this->output->createProgressBar($usersWithoutCodes->count());
        $bar->start();
        
        $processed = 0;
        $errors = 0;
        
        foreach ($usersWithoutCodes as $user) {
            try {
                DB::beginTransaction();
                
                // Generate unique referral code
                $referralCode = $user->generateReferralCode();
                
                // Update user with referral code
                $user->update(['referral_code' => $referralCode]);
                
                DB::commit();
                $processed++;
                
            } catch (\Exception $e) {
                DB::rollBack();
                $errors++;
                $this->error("Error for user {$user->id}: " . $e->getMessage());
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        $this->info("Successfully processed: {$processed} users");
        if ($errors > 0) {
            $this->error("Errors encountered: {$errors} users");
        }
        
        // Show final stats
        $totalWithCodes = User::whereNotNull('referral_code')->count();
        $totalUsers = User::count();
        
        $this->info("Final stats:");
        $this->info("- Users with referral codes: {$totalWithCodes}");
        $this->info("- Total users: {$totalUsers}");
        $this->info("- Coverage: " . round(($totalWithCodes / $totalUsers) * 100, 2) . "%");
    }
}
