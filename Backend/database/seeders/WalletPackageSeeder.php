<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\WalletPackage;

class WalletPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'title' => 'پکیج برنزی',
                'description' => 'پکیج مناسب برای شروع',
                'amount' => 50000,  // 50,000 ریال شارژ
                'price' => 45000,   // 45,000 ریال قیمت
                'discount_percentage' => 10,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
                'icon' => 'ri-copper-coin-line',
                'color' => '#CD7F32'
            ],
            [
                'title' => 'پکیج نقره‌ای',
                'description' => 'پکیج محبوب کاربران',
                'amount' => 100000, // 100,000 ریال شارژ
                'price' => 85000,   // 85,000 ریال قیمت
                'discount_percentage' => 15,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
                'icon' => 'ri-medal-line',
                'color' => '#C0C0C0'
            ],
            [
                'title' => 'پکیج طلایی',
                'description' => 'پکیج ویژه با بیشترین تخفیف',
                'amount' => 250000, // 250,000 ریال شارژ
                'price' => 200000,  // 200,000 ریال قیمت
                'discount_percentage' => 20,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 3,
                'icon' => 'ri-vip-crown-line',
                'color' => '#FFD700'
            ],
            [
                'title' => 'پکیج الماسی',
                'description' => 'پکیج پرمیوم برای کاربران VIP',
                'amount' => 500000, // 500,000 ریال شارژ
                'price' => 400000,  // 400,000 ریال قیمت
                'discount_percentage' => 20,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 4,
                'icon' => 'ri-gem-line',
                'color' => '#B9F2FF'
            ],
            [
                'title' => 'پکیج استارتاپی',
                'description' => 'پکیج کوچک برای تست',
                'amount' => 20000,  // 20,000 ریال شارژ
                'price' => 20000,   // 20,000 ریال قیمت
                'discount_percentage' => 0,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 0,
                'icon' => 'ri-seedling-line',
                'color' => '#4CAF50'
            ],
            [
                'title' => 'پکیج ویژه ماهانه',
                'description' => 'پکیج ویژه با تخفیف محدود',
                'amount' => 150000, // 150,000 ریال شارژ
                'price' => 120000,  // 120,000 ریال قیمت
                'discount_percentage' => 20,
                'is_active' => false, // غیرفعال برای نمایش
                'is_featured' => true,
                'sort_order' => 5,
                'icon' => 'ri-calendar-check-line',
                'color' => '#FF5722'
            ]
        ];

        foreach ($packages as $package) {
            WalletPackage::create($package);
        }
    }
}
