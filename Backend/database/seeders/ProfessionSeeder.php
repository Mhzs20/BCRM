<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Profession;
use App\Models\Salon;

class ProfessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $defaultProfessions = [
            'کارمند',
            'دانشجو',
            'خانه دار',
            'آزاد',
            'پزشک',
            'مهندس',
            'معلم',
            'بازنشسته',
            'ورزشکار',
            'هنرمند',
        ];

        $salons = Salon::all();

        if ($salons->isEmpty()) {
            $defaultSalon = Salon::firstOrCreate(
                ['name' => 'Default Salon', 'user_id' => 1],
                [
                    'name' => 'Default Salon',
                    'user_id' => 1,
                    'address' => 'Default Address',
                    'city_id' => 1,
                    'province_id' => 1,
                    'business_category_id' => 1,
                    'business_subcategory_id' => 1,
                ]
            );
            $salons = collect([$defaultSalon]);
        }

        foreach ($salons as $salon) {
            foreach ($defaultProfessions as $professionName) {
                Profession::firstOrCreate(
                    ['salon_id' => $salon->id, 'name' => $professionName],
                    ['salon_id' => $salon->id, 'name' => $professionName]
                );
            }
        }
    }
}
