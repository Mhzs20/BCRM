<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ReferralSetting;

class ReferralSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ReferralSetting::create([
            'signup_reward' => 50000, // 50,000 تومان پاداش ثبت‌نام
            'purchase_percentage' => 5, // 5 درصد پاداش خرید
            'monthly_referral_limit' => 10, // 10 دعوت در ماه
            'minimum_withdrawal_amount' => 50000, // حداقل 50,000 تومان برداشت
            'is_active' => true,
            'reward_type' => 'cash',
            'max_purchase_reward_amount' => 200000, // حداکثر 200,000 تومان پاداش خرید
        ]);
    }
}
