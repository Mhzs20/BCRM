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

        // Apply SMS balance range filter
        if (!empty($filters['min_sms_balance']) || !empty($filters['max_sms_balance'])) {
            $query->whereHas('smsBalance', function ($q) use ($filters) {
                if (!empty($filters['min_sms_balance']) && !empty($filters['max_sms_balance'])) {
                    $q->whereBetween('balance', [$filters['min_sms_balance'], $filters['max_sms_balance']]);
                } elseif (!empty($filters['min_sms_balance'])) {
                    $q->where('balance', '>=', $filters['min_sms_balance']);
                } elseif (!empty($filters['max_sms_balance'])) {
                    $q->where('balance', '<=', $filters['max_sms_balance']);
                }
            });
        }

        // Apply SMS balance status filter (for backward compatibility)
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

        // Apply last SMS purchase date range filter
        if (!empty($filters['last_sms_purchase_start']) || !empty($filters['last_sms_purchase_end'])) {
            $query->whereHas('smsTransactions', function ($q) use ($filters) {
                if (!empty($filters['last_sms_purchase_start'])) {
                    try {
                        $startDate = Carbon::parse($filters['last_sms_purchase_start'])->startOfDay();
                        $q->where('created_at', '>=', $startDate);
                    } catch (\Exception $e) {
                        \Log::error('Invalid last_sms_purchase_start date format: ' . $filters['last_sms_purchase_start']);
                    }
                }
                if (!empty($filters['last_sms_purchase_end'])) {
                    try {
                        $endDate = Carbon::parse($filters['last_sms_purchase_end'])->endOfDay();
                        $q->where('created_at', '<=', $endDate);
                    } catch (\Exception $e) {
                        \Log::error('Invalid last_sms_purchase_end date format: ' . $filters['last_sms_purchase_end']);
                    }
                }
                $q->where('type', 'purchase'); // Only purchase transactions
            });
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

        // Apply monthly SMS consumption range filter
        if (!empty($filters['min_monthly_consumption']) || !empty($filters['max_monthly_consumption'])) {
            $query->whereHas('smsTransactions', function ($q) use ($filters) {
                $q->selectRaw('salon_id, SUM(amount) as total_consumption')
                  ->whereBetween('created_at', [Carbon::now()->subMonth(), Carbon::now()])
                  ->where('type', '!=', 'purchase') // Exclude purchase transactions for consumption calculation
                  ->groupBy('salon_id')
                  ->having(function ($havingQ) use ($filters) {
                      if (!empty($filters['min_monthly_consumption']) && !empty($filters['max_monthly_consumption'])) {
                          $havingQ->havingRaw('total_consumption BETWEEN ? AND ?', [$filters['min_monthly_consumption'], $filters['max_monthly_consumption']]);
                      } elseif (!empty($filters['min_monthly_consumption'])) {
                          $havingQ->havingRaw('total_consumption >= ?', [$filters['min_monthly_consumption']]);
                      } elseif (!empty($filters['max_monthly_consumption'])) {
                          $havingQ->havingRaw('total_consumption <= ?', [$filters['max_monthly_consumption']]);
                      }
                  });
            });
        }

        // Apply gender filter
        if (!empty($filters['gender'])) {
            $query->whereHas('owner', function ($q) use ($filters) {
                $q->where('gender', $filters['gender']);
            });
        }

        // Apply age range filter
        if (!empty($filters['min_age']) || !empty($filters['max_age'])) {
            $query->whereHas('owner', function ($q) use ($filters) {
                if (!empty($filters['min_age']) && !empty($filters['max_age'])) {
                    $q->whereRaw("TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN ? AND ?", [$filters['min_age'], $filters['max_age']]);
                } elseif (!empty($filters['min_age'])) {
                    $q->whereRaw("TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= ?", [$filters['min_age']]);
                } elseif (!empty($filters['max_age'])) {
                    $q->whereRaw("TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?", [$filters['max_age']]);
                }
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

        if (!empty($filters['min_sms_balance']) || !empty($filters['max_sms_balance'])) {
            $balanceText = '';
            if (!empty($filters['min_sms_balance']) && !empty($filters['max_sms_balance'])) {
                $balanceText = $filters['min_sms_balance'] . ' تا ' . $filters['max_sms_balance'];
            } elseif (!empty($filters['min_sms_balance'])) {
                $balanceText = 'بیشتر از ' . $filters['min_sms_balance'];
            } elseif (!empty($filters['max_sms_balance'])) {
                $balanceText = 'کمتر از ' . $filters['max_sms_balance'];
            }
            $formatted['محدوده اعتبار پیامک'] = $balanceText;
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

        if (!empty($filters['last_sms_purchase_start']) || !empty($filters['last_sms_purchase_end'])) {
            $dateText = '';
            if (!empty($filters['last_sms_purchase_start']) && !empty($filters['last_sms_purchase_end'])) {
                $dateText = $filters['last_sms_purchase_start'] . ' تا ' . $filters['last_sms_purchase_end'];
            } elseif (!empty($filters['last_sms_purchase_start'])) {
                $dateText = 'از ' . $filters['last_sms_purchase_start'];
            } elseif (!empty($filters['last_sms_purchase_end'])) {
                $dateText = 'تا ' . $filters['last_sms_purchase_end'];
            }
            $formatted['محدوده تاریخ آخرین خرید پیامک'] = $dateText;
        }

        if (!empty($filters['monthly_sms_consumption'])) {
            $consumptionLabels = [
                'high' => 'زیاد (بیشتر از ۵۰۰)',
                'medium' => 'متوسط (۱۰۰ تا ۵۰۰)',
                'low' => 'کم (کمتر از ۱۰۰)'
            ];
            $formatted['مصرف ماهانه پیامک'] = $consumptionLabels[$filters['monthly_sms_consumption']] ?? 'نامشخص';
        }

        if (!empty($filters['min_monthly_consumption']) || !empty($filters['max_monthly_consumption'])) {
            $consumptionText = '';
            if (!empty($filters['min_monthly_consumption']) && !empty($filters['max_monthly_consumption'])) {
                $consumptionText = $filters['min_monthly_consumption'] . ' تا ' . $filters['max_monthly_consumption'];
            } elseif (!empty($filters['min_monthly_consumption'])) {
                $consumptionText = 'بیشتر از ' . $filters['min_monthly_consumption'];
            } elseif (!empty($filters['max_monthly_consumption'])) {
                $consumptionText = 'کمتر از ' . $filters['max_monthly_consumption'];
            }
            $formatted['محدوده مصرف ماهانه پیامک'] = $consumptionText;
        }

        if (!empty($filters['gender'])) {
            $genderLabels = [
                'male' => 'مرد',
                'female' => 'زن',
                'other' => 'سایر'
            ];
            $formatted['جنسیت'] = $genderLabels[$filters['gender']] ?? 'نامشخص';
        }

        if (!empty($filters['min_age']) || !empty($filters['max_age'])) {
            $ageText = '';
            if (!empty($filters['min_age']) && !empty($filters['max_age'])) {
                $ageText = $filters['min_age'] . ' تا ' . $filters['max_age'] . ' سال';
            } elseif (!empty($filters['min_age'])) {
                $ageText = 'بیشتر از ' . $filters['min_age'] . ' سال';
            } elseif (!empty($filters['max_age'])) {
                $ageText = 'کمتر از ' . $filters['max_age'] . ' سال';
            }
            $formatted['رنج سنی'] = $ageText;
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

    /**
     * Get age range for SQL query
     */
    private function getAgeRange(string $ageRange): array
    {
        return match ($ageRange) {
            '18-25' => [18, 25],
            '26-35' => [26, 35],
            '36-45' => [36, 45],
            '46-60' => [46, 60],
            '60+' => [60, 150], // Assuming max age 150
            default => [0, 150],
        };
    }
}
