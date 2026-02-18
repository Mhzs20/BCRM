<?php

namespace Database\Seeders;

use App\Models\Salon;
use App\Models\Service;
use App\Models\TransactionCategory;
use App\Models\TransactionSubcategory;
use Illuminate\Database\Seeder;

class TransactionCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // این seeder برای سالن‌های موجود، دسته "خدمات" و زیردسته‌هایش رو ایجاد می‌کنه
        $salons = Salon::all();

        foreach ($salons as $salon) {
            $this->createServicesCategory($salon);
        }
    }

    /**
     * ایجاد دسته "خدمات" برای سالن
     */
    public static function createServicesCategory(Salon $salon): TransactionCategory
    {
        // بررسی اینکه آیا قبلاً ساخته شده یا نه
        $category = TransactionCategory::forSalon($salon->id)
            ->system()
            ->where('name', 'خدمات')
            ->first();

        if (!$category) {
            $category = TransactionCategory::create([
                'salon_id' => $salon->id,
                'name' => 'خدمات',
                'type' => TransactionCategory::TYPE_INCOME, // خدمات فقط برای ورودی
                'description' => 'دسته‌بندی خدمات ارائه شده توسط سالن',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 0,
            ]);
        }

        // ایجاد زیردسته برای هر خدمت سالن
        static::syncServicesAsSubcategories($salon, $category);

        return $category;
    }

    /**
     * همگام‌سازی خدمات سالن با زیردسته‌ها
     */
    public static function syncServicesAsSubcategories(Salon $salon, TransactionCategory $category): void
    {
        $services = Service::where('salon_id', $salon->id)->get();

        foreach ($services as $service) {
            // بررسی اینکه آیا زیردسته برای این خدمت وجود دارد
            $existingSubcategory = TransactionSubcategory::forCategory($category->id)
                ->where('service_id', $service->id)
                ->first();

            if (!$existingSubcategory) {
                TransactionSubcategory::create([
                    'category_id' => $category->id,
                    'salon_id' => $salon->id,
                    'name' => $service->name,
                    'description' => $service->description,
                    'service_id' => $service->id,
                    'is_active' => true,
                    'sort_order' => 0,
                ]);
            }
        }
    }
}
