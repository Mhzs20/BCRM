<?php

namespace App\Console\Commands;

use App\Models\DiscountCode;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ManageDiscountCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'discount:manage 
                           {action : Action to perform (list|create|deactivate|cleanup)}
                           {--code= : Discount code (for create/deactivate actions)}
                           {--percentage= : Discount percentage (for create action)}
                           {--expires= : Expiration date in Y-m-d format (for create action)}
                           {--active=1 : Whether the code is active (for create action)}
                           {--description= : Description for the discount code (for create action)}
                           {--usage-limit= : Maximum usage limit (for create action)}
                           {--target-type=all : Target type (all|filtered) (for create action)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage discount codes (list, create, deactivate, cleanup expired)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                $this->listCodes();
                break;
            case 'create':
                $this->createCode();
                break;
            case 'deactivate':
                $this->deactivateCode();
                break;
            case 'cleanup':
                $this->cleanupExpired();
                break;
            default:
                $this->error('Invalid action. Use: list, create, deactivate, or cleanup');
                return 1;
        }

        return 0;
    }

    protected function listCodes()
    {
        $codes = DiscountCode::orderBy('created_at', 'desc')->get();

        if ($codes->isEmpty()) {
            $this->info('No discount codes found.');
            return;
        }

        $headers = ['ID', 'Code', 'Percentage', 'Target Type', 'Status', 'Usage', 'Expires At', 'Created At'];
        $data = $codes->map(function ($code) {
            $usage = $code->usage_count ?? 0;
            $usageDisplay = $code->usage_limit ? "{$usage}/{$code->usage_limit}" : $usage;
            
            return [
                $code->id,
                $code->code,
                $code->percentage . '%',
                $code->user_filter_type === 'all' ? 'All Users' : 'Filtered',
                $code->is_active ? 'Active' : 'Inactive',
                $usageDisplay,
                $code->expires_at ? $code->expires_at->format('Y-m-d H:i:s') : 'Never',
                $code->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();

        $this->table($headers, $data);
    }

    protected function createCode()
    {
        $code = $this->option('code') ?: $this->ask('Enter discount code');
        $percentage = $this->option('percentage') ?: $this->ask('Enter discount percentage (1-100)');
        $expires = $this->option('expires') ?: $this->ask('Enter expiration date (Y-m-d format, or leave empty for no expiration)');
        $active = $this->option('active');
        $description = $this->option('description') ?: $this->ask('Enter description (optional)', '');
        $usageLimit = $this->option('usage-limit') ?: $this->ask('Enter usage limit (optional, leave empty for unlimited)', '');
        $targetType = $this->option('target-type') ?: $this->choice('Target type', ['all', 'filtered'], 'all');

        if (!$code) {
            $this->error('Discount code is required.');
            return;
        }

        if (!$percentage || $percentage < 1 || $percentage > 100) {
            $this->error('Percentage must be between 1 and 100.');
            return;
        }

        $expiresAt = null;
        if ($expires) {
            try {
                $expiresAt = Carbon::createFromFormat('Y-m-d', $expires);
            } catch (\Exception $e) {
                $this->error('Invalid expiration date format. Use Y-m-d format.');
                return;
            }
        }

        $data = [
            'code' => strtoupper($code),
            'percentage' => $percentage,
            'expires_at' => $expiresAt,
            'is_active' => (bool) $active,
            'user_filter_type' => $targetType,
        ];

        if ($description) {
            $data['description'] = $description;
        }

        if ($usageLimit && is_numeric($usageLimit)) {
            $data['usage_limit'] = (int) $usageLimit;
        }

        try {
            $discountCode = DiscountCode::create($data);

            $this->info("Discount code '{$discountCode->code}' created successfully.");
            
            if ($description) {
                $this->line("Description: {$description}");
            }
            
            if ($usageLimit) {
                $this->line("Usage limit: {$usageLimit}");
            }
            
            $this->line("Target type: " . ucfirst($targetType) . " users");
            
        } catch (\Exception $e) {
            $this->error('Failed to create discount code: ' . $e->getMessage());
        }
    }

    protected function deactivateCode()
    {
        $code = $this->option('code') ?: $this->ask('Enter discount code to deactivate');

        if (!$code) {
            $this->error('Discount code is required.');
            return;
        }

        $discountCode = DiscountCode::where('code', strtoupper($code))->first();

        if (!$discountCode) {
            $this->error("Discount code '{$code}' not found.");
            return;
        }

        $discountCode->update(['is_active' => false]);
        $this->info("Discount code '{$discountCode->code}' deactivated successfully.");
    }

    protected function cleanupExpired()
    {
        $expiredCodes = DiscountCode::where('expires_at', '<', now())
            ->where('is_active', true)
            ->get();

        if ($expiredCodes->isEmpty()) {
            $this->info('No expired active discount codes found.');
            return;
        }

        $count = $expiredCodes->count();
        $expiredCodes->each->update(['is_active' => false]);

        $this->info("Deactivated {$count} expired discount codes.");
    }
}
