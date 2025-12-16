<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HowIntroduced;
use App\Models\Salon; // Assuming HowIntroduced is tied to a Salon

class HowIntroducedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $defaultOptions = [
            'اینستاگرام',
            'گوگل / اینترنت',
            'وب سایت',
            'بروشور/ تراکت',
            'معرفی دوستان',
            'معرفی مشتریان',
            'معرفی پرسنل',
            'پیامک',
            'تماس',
            'پیام واتساپ',
            'بیلبورد',
            'همایش / نمایشگاه',
            'تبلیغات',
            'تابلوی سالن',
            'اتفاقی',
            'سایر', // This will be the "Other" option
        ];

        // Assuming you want to seed these for all existing salons, or a specific one.
        // For now, let's assume we seed for all existing salons.
        // If there are no salons, or if you want to seed for a specific salon,
        // you might need to adjust this logic.
        // Get all existing salons
        $salons = Salon::all();

        // If no salons exist, create a default one for seeding purposes
        if ($salons->isEmpty()) {
            // You might want to create a more robust default salon or link it to a default user
            // For now, creating a basic one to ensure seeding works.
            $defaultSalon = Salon::firstOrCreate(
                ['name' => 'Default Salon', 'user_id' => 1], // Assuming user_id 1 exists or is created by another seeder
                [
                    'name' => 'Default Salon',
                    'user_id' => 1, // Link to a default user, e.g., super admin
                    'address' => 'Default Address',
                    'city_id' => 1, // Assuming city_id 1 exists
                    'province_id' => 1, // Assuming province_id 1 exists
                    'business_category_id' => 1, // Assuming business_category_id 1 exists
                    'business_subcategory_id' => 1, // Assuming business_subcategory_id 1 exists
                ]
            );
            $salons = collect([$defaultSalon]); // Use collect to make it iterable like Salon::all()
        }

        // Create Global Templates (salon_id = null)
        foreach ($defaultOptions as $option) {
            HowIntroduced::firstOrCreate(
                ['salon_id' => null, 'name' => $option],
                ['salon_id' => null, 'name' => $option]
            );
        }

        foreach ($salons as $salon) {
            foreach ($defaultOptions as $option) {
                HowIntroduced::firstOrCreate(
                    ['salon_id' => $salon->id, 'name' => $option],
                    ['salon_id' => $salon->id, 'name' => $option]
                );
            }
        }
    }
}
