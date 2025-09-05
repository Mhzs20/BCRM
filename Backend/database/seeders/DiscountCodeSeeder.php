<?php

namespace Database\Seeders;

use App\Models\DiscountCode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DiscountCodeSeeder extends Seeder
{
    public function run(): void
    {
        // Create some sample discount codes
        $discountCodes = [
            [
                'code' => 'WELCOME10',
                'percentage' => 10,
                'expires_at' => Carbon::now()->addMonths(6),
                'is_active' => true,
            ],
            [
                'code' => 'SAVE20',
                'percentage' => 20,
                'expires_at' => Carbon::now()->addMonths(3),
                'is_active' => true,
            ],
            [
                'code' => 'FIRSTTIME15',
                'percentage' => 15,
                'expires_at' => Carbon::now()->addYear(),
                'is_active' => true,
            ],
            [
                'code' => 'WEEKEND25',
                'percentage' => 25,
                'expires_at' => Carbon::now()->addDays(30),
                'is_active' => true,
            ],
            [
                'code' => 'EXPIRED50',
                'percentage' => 50,
                'expires_at' => Carbon::now()->subDays(10),
                'is_active' => true,
            ],
            [
                'code' => 'INACTIVE30',
                'percentage' => 30,
                'expires_at' => Carbon::now()->addMonths(2),
                'is_active' => false,
            ],
        ];

        foreach ($discountCodes as $codeData) {
            DiscountCode::updateOrCreate(
                ['code' => $codeData['code']],
                $codeData
            );
        }

        $this->command->info('Sample discount codes created successfully!');
    }
}
