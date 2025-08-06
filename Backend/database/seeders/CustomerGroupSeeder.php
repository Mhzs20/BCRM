<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CustomerGroup;
use App\Models\Salon;

class CustomerGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $defaultCustomerGroups = [
            'مشتریان جدید',
            'مشتریان وفادار',
            'مشتریان VIP',
            'مشتریان غیرفعال',
            'مشتریان بالقوه',
        ];

        $salons = Salon::all();

        if ($salons->isEmpty()) {
            $defaultSalon = Salon::firstOrCreate(
                ['name' => 'Default Salon', 'user_id' => 1],
                [
                    'name' => 'Default Salon',
                    'user_id' => 1,
                    'phone_number' => '09123456789',
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
            foreach ($defaultCustomerGroups as $groupName) {
                CustomerGroup::firstOrCreate(
                    ['salon_id' => $salon->id, 'name' => $groupName],
                    ['salon_id' => $salon->id, 'name' => $groupName]
                );
            }
        }
    }
}
