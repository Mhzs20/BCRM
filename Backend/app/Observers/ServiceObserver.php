<?php

namespace App\Observers;

use App\Models\Service;
use App\Models\TransactionCategory;
use App\Models\TransactionSubcategory;

class ServiceObserver
{
    /**
     * Handle the Service "created" event.
     */
    public function created(Service $service): void
    {
        // پیدا کردن دسته "خدمات" برای این سالن
        $servicesCategory = TransactionCategory::forSalon($service->salon_id)
            ->system()
            ->where('name', 'خدمات')
            ->first();

        if ($servicesCategory) {
            // ایجاد زیردسته برای این خدمت
            TransactionSubcategory::create([
                'category_id' => $servicesCategory->id,
                'salon_id' => $service->salon_id,
                'name' => $service->name,
                'description' => $service->description,
                'service_id' => $service->id,
                'is_active' => true,
                'sort_order' => 0,
            ]);
        }
    }

    /**
     * Handle the Service "updated" event.
     */
    public function updated(Service $service): void
    {
        // به‌روزرسانی نام زیردسته مربوط به این خدمت
        $subcategory = TransactionSubcategory::where('service_id', $service->id)->first();

        if ($subcategory) {
            $subcategory->update([
                'name' => $service->name,
                'description' => $service->description,
            ]);
        }
    }

    /**
     * Handle the Service "deleted" event.
     */
    public function deleted(Service $service): void
    {
        // حذف زیردسته مربوط به این خدمت
        TransactionSubcategory::where('service_id', $service->id)->delete();
    }
}
