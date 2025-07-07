<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Setting::updateOrCreate(
            ['key' => 'sms_character_limit'],
            ['value' => '70']
        );

        \App\Models\Setting::updateOrCreate(
            ['key' => 'enable_reminder_sms_globally'],
            ['value' => 'true']
        );

        \App\Models\Setting::updateOrCreate(
            ['key' => 'enable_satisfaction_sms_globally'],
            ['value' => 'true']
        );
    }
}
