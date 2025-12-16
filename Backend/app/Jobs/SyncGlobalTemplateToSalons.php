<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Salon;
use Illuminate\Support\Facades\Log;

class SyncGlobalTemplateToSalons implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $modelClass;
    protected $templateId;
    protected $name;

    protected $templateCreatedAt;

    /**
     * Create a new job instance.
     *
     * @param string $modelClass The class name of the model (e.g., App\Models\Profession)
     * @param int $templateId The ID of the global template
     * @param string $name The name of the template item
     * @param \Carbon\Carbon|string|null $templateCreatedAt The creation time of the template
     */
    public function __construct(string $modelClass, int $templateId, string $name, $templateCreatedAt = null)
    {
        $this->modelClass = $modelClass;
        $this->templateId = $templateId;
        $this->name = $name;
        $this->templateCreatedAt = $templateCreatedAt;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting sync for global template: {$this->modelClass} - {$this->name}");

        // Parse template creation date if provided
        $templateDate = $this->templateCreatedAt ? \Carbon\Carbon::parse($this->templateCreatedAt) : null;

        Salon::chunk(100, function ($salons) use ($templateDate) {
            foreach ($salons as $salon) {
                try {
                    // SAFETY CHECK 1:
                    // If the salon was created AFTER the template, they should have received this template
                    // via the SalonObserver at the moment of creation.
                    // If they don't have it now, it means they explicitly DELETED it.
                    // We should NOT re-sync it to respect their decision.
                    if ($templateDate && $salon->created_at && $salon->created_at->gt($templateDate)) {
                        continue;
                    }

                    // Dynamic relationship method based on model class
                    $relationName = $this->getRelationName($this->modelClass);
                    
                    if (!$relationName) {
                        Log::error("Could not determine relationship for model: {$this->modelClass}");
                        return;
                    }

                    // SAFETY CHECK 2:
                    // Don't create duplicates if it already exists
                    $exists = $salon->{$relationName}()->where('name', $this->name)->exists();
                    
                    if (!$exists) {
                        $salon->{$relationName}()->create(['name' => $this->name]);
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to sync template to salon {$salon->id}: " . $e->getMessage());
                }
            }
        });

        Log::info("Completed sync for global template: {$this->modelClass} - {$this->name}");
    }

    private function getRelationName(string $modelClass): ?string
    {
        return match ($modelClass) {
            'App\Models\Profession' => 'professions',
            'App\Models\HowIntroduced' => 'howIntroduceds',
            'App\Models\CustomerGroup' => 'customerGroups',
            default => null,
        };
    }
}
