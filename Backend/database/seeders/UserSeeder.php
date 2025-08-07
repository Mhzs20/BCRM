<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create a default user if one doesn't exist
        User::firstOrCreate(
            ['mobile' => '09123456789'], // Use mobile as the unique identifier
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'), // You should change this in production
                'is_superadmin' => true,
                'mobile' => '09123456789', // Example mobile number
            ]
        );
    }
}
