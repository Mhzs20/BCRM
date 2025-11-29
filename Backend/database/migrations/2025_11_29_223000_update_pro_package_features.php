<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Package;
use App\Models\Option;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $optionName = 'لینک اختصاصی رزرو آنلاین سالن';
        $option = Option::where('name', $optionName)->first();

        if (!$option) {
            // If option doesn't exist, create it (just in case)
            $option = Option::create([
                'name' => $optionName,
                'details' => 'یک لینک اختصاصی و حرفه‌ای برای سالن شما که مشتریان می‌توانند در هر زمان و مکان به‌راحتی نوبت خود را رزرو کنند. این قابلیت علاوه بر راحتی مشتری، باعث کاهش تماس‌های تلفنی و افزایش نظم در رزروها می‌شود.',
                'is_active' => true,
            ]);
        }

        // Related options to add
        $relatedOptions = [
            'پیامک هوشمند و زمانبندی شده',
            'پیامک یادآوری ترمیم و تولد',
            'لینک اختصاصی رزرو آنلاین سالن'
        ];

        // Find Pro package
        // Try different variations of the name
        $proPackages = Package::where('name', 'LIKE', '%پرو%')
            ->orWhere('name', 'LIKE', '%Pro%')
            ->get();

        foreach ($proPackages as $package) {
            foreach ($relatedOptions as $optName) {
                $opt = Option::where('name', $optName)->first();
                if ($opt) {
                    // Attach if not already attached
                    if (!$package->options()->where('option_id', $opt->id)->exists()) {
                        $package->options()->attach($opt->id);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We don't necessarily want to detach in down() as it might have been added manually
    }
};
