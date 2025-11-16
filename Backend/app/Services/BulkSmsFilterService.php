<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class BulkSmsFilterService
{
    /**
     * Apply bulk SMS filters to a salon query builder instance.
     */
    public static function apply(Builder $query, array $filters): Builder
    {
        if (!empty($filters['search'])) {
            $query->whereSearch($filters['search']);
        }

        if (array_key_exists('status', $filters) && $filters['status'] !== null && $filters['status'] !== '') {
            $query->where('is_active', (int) $filters['status']);
        }

        if (!empty($filters['province_id'])) {
            $query->whereHas('city', function ($q) use ($filters) {
                $q->where('province_id', $filters['province_id']);
            });
        }

        if (!empty($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }

        if (!empty($filters['business_category_id'])) {
            $query->where('business_category_id', $filters['business_category_id']);
        }

        if (!empty($filters['business_subcategory_id'])) {
            $query->where('business_subcategory_id', $filters['business_subcategory_id']);
        }

        if (!empty($filters['sms_balance_status'])) {
            $query->whereHas('smsBalance', function ($q) use ($filters) {
                if ($filters['sms_balance_status'] === 'less_than_50') {
                    $q->where('balance', '<', 50);
                } elseif ($filters['sms_balance_status'] === 'less_than_200') {
                    $q->where('balance', '<', 200);
                } elseif ($filters['sms_balance_status'] === 'zero') {
                    $q->where('balance', 0);
                }
            }, '>=', 1);
        }

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

        if (!empty($filters['last_sms_purchase'])) {
            $now = Carbon::now();
            if ($filters['last_sms_purchase'] === 'last_month') {
                $query->whereHas('smsTransactions', function ($q) use ($now) {
                    $q->where('created_at', '>=', $now->copy()->subMonth());
                });
            } elseif ($filters['last_sms_purchase'] === 'last_3_months') {
                $query->whereHas('smsTransactions', function ($q) use ($now) {
                    $q->where('created_at', '>=', $now->copy()->subMonths(3));
                });
            } elseif ($filters['last_sms_purchase'] === 'last_6_months') {
                $query->whereHas('smsTransactions', function ($q) use ($now) {
                    $q->where('created_at', '>=', $now->copy()->subMonths(6));
                });
            } elseif ($filters['last_sms_purchase'] === 'more_than_6_months') {
                $query->whereHas('smsTransactions', function ($q) use ($now) {
                    $q->where('created_at', '<', $now->copy()->subMonths(6));
                });
            } elseif ($filters['last_sms_purchase'] === 'never') {
                $query->whereDoesntHave('smsTransactions', function ($q) {
                    $q->where('sms_type', 'purchase');
                });
            }
        }

        if (!empty($filters['monthly_sms_consumption'])) {
            $condition = self::getMonthlyConsumptionCondition($filters['monthly_sms_consumption']);
            if ($condition !== '') {
                $query->whereHas('smsTransactions', function ($subQuery) use ($condition) {
                    $subQuery->selectRaw('SUM(amount) as total_amount')
                        ->whereBetween('created_at', [Carbon::now()->subMonth(), Carbon::now()])
                        ->groupBy('salon_id')
                        ->havingRaw($condition);
                });
            }
        }

        if (!empty($filters['gender'])) {
            $query->whereHas('owner', function ($q) use ($filters) {
                $q->where('gender', $filters['gender']);
            });
        }

        $minAge = $filters['min_age'] ?? null;
        $maxAge = $filters['max_age'] ?? null;

        if ($minAge !== null || $maxAge !== null) {
            $query->whereHas('owner', function ($q) use ($minAge, $maxAge) {
                if ($minAge !== null && $maxAge !== null) {
                    $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN ? AND ?', [$minAge, $maxAge]);
                } elseif ($minAge !== null) {
                    $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= ?', [$minAge]);
                } elseif ($maxAge !== null) {
                    $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?', [$maxAge]);
                }
            });
        }

        return $query;
    }

    private static function getMonthlyConsumptionCondition(string $status): string
    {
        return match ($status) {
            'high' => 'total_amount > 500',
            'medium' => 'total_amount >= 100 AND total_amount <= 500',
            'low' => 'total_amount < 100',
            default => '',
        };
    }
}
