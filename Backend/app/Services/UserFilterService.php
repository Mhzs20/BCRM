<?php

namespace App\Services;

use App\Models\Salon;
use App\Models\Province;
use App\Models\City;
use App\Models\BusinessCategory;
use App\Models\BusinessSubcategory;
use Carbon\Carbon;

class UserFilterService
{
    /**
     * Get filtered salons based on criteria
     */
    public function getFilteredSalons(array $filters = [])
    {
        $query = Salon::with(['owner', 'city.province', 'smsBalance', 'smsTransactions', 'businessCategory', 'businessSubcategories']);

        // Apply search filter
        if (!empty($filters['search'])) {
            $query->whereSearch($filters['search']);
        }

        // Apply status filter
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('is_active', $filters['status']);
        }

        // Apply province filter
        if (!empty($filters['province_id'])) {
            $query->whereHas('city', function ($q) use ($filters) {
                $q->where('province_id', $filters['province_id']);
            });
        }

        // Apply city filter
        if (!empty($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }

        // Apply business category filter
        if (!empty($filters['business_category_id'])) {
            $query->where('business_category_id', $filters['business_category_id']);
        }

        // Apply business subcategory filter
        if (!empty($filters['business_subcategory_id'])) {
            $query->where('business_subcategory_id', $filters['business_subcategory_id']);
        }

        // Apply SMS balance status filter
        if (!empty($filters['sms_balance_status'])) {
            $query->whereHas('smsBalance', function ($q) use ($filters) {
                switch ($filters['sms_balance_status']) {
                    case 'less_than_50':
                        $q->where('balance', '<', 50);
                        break;
                    case 'less_than_200':
                        $q->where('balance', '<', 200);
                        break;
                    case 'zero':
                        $q->where('balance', 0);
                        break;
                }
            }, '>=', 1);
        }

        // Apply last SMS purchase filter
        if (!empty($filters['last_sms_purchase'])) {
            $now = Carbon::now();
            switch ($filters['last_sms_purchase']) {
                case 'last_month':
                    $query->whereHas('smsTransactions', function ($q) use ($now) {
                        $q->where('created_at', '>=', $now->copy()->subMonth());
                    });
                    break;
                case 'last_3_months':
                    $query->whereHas('smsTransactions', function ($q) use ($now) {
                        $q->where('created_at', '>=', $now->copy()->subMonths(3));
                    });
                    break;
                case 'last_6_months':
                    $query->whereHas('smsTransactions', function ($q) use ($now) {
                        $q->where('created_at', '>=', $now->copy()->subMonths(6));
                    });
                    break;
                case 'more_than_6_months':
                    $query->whereHas('smsTransactions', function ($q) use ($now) {
                        $q->where('created_at', '<', $now->copy()->subMonths(6));
                    });
                    break;
                case 'never':
                    $query->whereDoesntHave('smsTransactions', function ($q) {
                        $q->where('sms_type', 'purchase');
                    });
                    break;
            }
        }

        // Apply monthly SMS consumption filter
        if (!empty($filters['monthly_sms_consumption'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereHas('smsTransactions', function ($subQ) use ($filters) {
                    $subQ->selectRaw('SUM(amount) as total_amount')
                        ->whereBetween('created_at', [Carbon::now()->subMonth(), Carbon::now()])
                        ->groupBy('salon_id')
                        ->havingRaw($this->getMonthlyConsumptionCondition($filters['monthly_sms_consumption']));
                });
            });
        }

        return $query;
    }

    /**
     * Get filter options for dropdown lists
     */
    public function getFilterOptions()
    {
        return [
            'provinces' => Province::all(),
            'businessCategories' => BusinessCategory::all(),
            'cities' => collect(), // Will be loaded via AJAX
            'businessSubcategories' => collect(), // Will be loaded via AJAX
        ];
    }

    /**
     * Get count of filtered salons
     */
    public function getFilteredSalonsCount(array $filters = []): int
    {
        return $this->getFilteredSalons($filters)->count();
    }

    /**
     * Format filters for display
     */
    public function formatFiltersForDisplay(array $filters): array
    {
        $formatted = [];

        if (!empty($filters['province_id'])) {
            $province = Province::find($filters['province_id']);
            $formatted['استان'] = $province ? $province->name : 'نامشخص';
        }

        if (!empty($filters['city_id'])) {
            $city = City::find($filters['city_id']);
            $formatted['شهر'] = $city ? $city->name : 'نامشخص';
        }

        if (!empty($filters['business_category_id'])) {
            $category = BusinessCategory::find($filters['business_category_id']);
            $formatted['دسته‌بندی کسب‌وکار'] = $category ? $category->name : 'نامشخص';
        }

        if (!empty($filters['business_subcategory_id'])) {
            $subcategory = BusinessSubcategory::find($filters['business_subcategory_id']);
            $formatted['زیردسته کسب‌وکار'] = $subcategory ? $subcategory->name : 'نامشخص';
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $formatted['وضعیت'] = $filters['status'] ? 'فعال' : 'غیرفعال';
        }

        if (!empty($filters['sms_balance_status'])) {
            $statusLabels = [
                'less_than_50' => 'کمتر از ۵۰',
                'less_than_200' => 'کمتر از ۲۰۰',
                'zero' => 'صفر'
            ];
            $formatted['موجودی پیامک'] = $statusLabels[$filters['sms_balance_status']] ?? 'نامشخص';
        }

        if (!empty($filters['last_sms_purchase'])) {
            $purchaseLabels = [
                'last_month' => 'یک ماه اخیر',
                'last_3_months' => 'سه ماه اخیر',
                'last_6_months' => 'شش ماه اخیر',
                'more_than_6_months' => 'بیشتر از شش ماه',
                'never' => 'تاکنون خرید نکرده'
            ];
            $formatted['آخرین خرید پیامک'] = $purchaseLabels[$filters['last_sms_purchase']] ?? 'نامشخص';
        }

        if (!empty($filters['monthly_sms_consumption'])) {
            $consumptionLabels = [
                'high' => 'زیاد (بیشتر از ۵۰۰)',
                'medium' => 'متوسط (۱۰۰ تا ۵۰۰)',
                'low' => 'کم (کمتر از ۱۰۰)'
            ];
            $formatted['مصرف ماهانه پیامک'] = $consumptionLabels[$filters['monthly_sms_consumption']] ?? 'نامشخص';
        }

        return $formatted;
    }

    /**
     * Get monthly consumption condition for SQL
     */
    private function getMonthlyConsumptionCondition(string $status): string
    {
        return match ($status) {
            'high' => 'total_amount > 500',
            'medium' => 'total_amount >= 100 AND total_amount <= 500',
            'low' => 'total_amount < 100',
            default => '',
        };
    }
}
