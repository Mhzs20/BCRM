<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Facades\Excel;
use Morilog\Jalali\Jalalian;

class ExportController extends Controller
{
    public function exportSalons(Request $request)
    {
        $query = Salon::with(['owner', 'city', 'businessCategory', 'businessSubcategories', 'smsBalance', 'smsTransactions' => function($q) {
            $q->selectRaw('salon_id, SUM(CASE WHEN type != "purchase" THEN amount ELSE 0 END) as total_consumed')
              ->groupBy('salon_id');
        }]);

        // اعمال فیلترها
        $this->applyFilters($query, $request);

        $salons = $query->get();

        return Excel::download(new SalonsExport($salons), 'salons_' . now()->format('Y-m-d_H-i-s') . '.xlsx');
    }

    public function exportBulkSmsUsers(Request $request)
    {
        $query = Salon::with(['owner', 'city', 'businessCategory', 'businessSubcategories', 'smsBalance', 'smsTransactions' => function($q) {
            $q->selectRaw('salon_id, SUM(CASE WHEN type != "purchase" THEN amount ELSE 0 END) as total_consumed')
              ->groupBy('salon_id');
        }]);

        // اعمال فیلترها
        $this->applyFilters($query, $request);

        $salons = $query->get();

        return Excel::download(new BulkSmsUsersExport($salons), 'bulk_sms_users_' . now()->format('Y-m-d_H-i-s') . '.xlsx');
    }

    public function exportBulkSmsGiftUsers(Request $request)
    {
        $query = Salon::with(['owner', 'city', 'businessCategory', 'businessSubcategories', 'smsBalance', 'smsTransactions' => function($q) {
            $q->selectRaw('salon_id, SUM(CASE WHEN type != "purchase" THEN amount ELSE 0 END) as total_consumed')
              ->groupBy('salon_id');
        }]);

        // اعمال فیلترها
        $this->applyFilters($query, $request);

        $salons = $query->get();

        return Excel::download(new BulkSmsGiftUsersExport($salons), 'bulk_sms_gift_users_' . now()->format('Y-m-d_H-i-s') . '.xlsx');
    }

    public function exportDiscountCodeUsers(Request $request)
    {
        $query = Salon::with(['owner', 'city', 'businessCategory', 'businessSubcategories', 'smsBalance', 'smsTransactions' => function($q) {
            $q->selectRaw('salon_id, SUM(CASE WHEN type != "purchase" THEN amount ELSE 0 END) as total_consumed')
              ->groupBy('salon_id');
        }]);

        // اعمال فیلترها
        $this->applyFilters($query, $request);

        $salons = $query->get();

        return Excel::download(new DiscountCodeUsersExport($salons), 'discount_code_users_' . now()->format('Y-m-d_H-i-s') . '.xlsx');
    }

    private function applyFilters($query, Request $request)
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('owner', function ($ownerQuery) use ($search) {
                      $ownerQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('mobile', 'like', "%{$search}%");
                  })
                  ->orWhereHas('city', function ($cityQuery) use ($search) {
                      $cityQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === '1');
        }

        if ($request->filled('province_id')) {
            $query->whereHas('city', function ($cityQuery) use ($request) {
                $cityQuery->where('province_id', $request->province_id);
            });
        }

        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        if ($request->filled('business_category_id')) {
            $query->where('business_category_id', $request->business_category_id);
        }

        if ($request->filled('business_subcategory_id')) {
            $query->whereHas('businessSubcategories', function ($subQuery) use ($request) {
                $subQuery->where('business_subcategory_id', $request->business_subcategory_id);
            });
        }

        if ($request->filled('created_at_start')) {
            $query->where('created_at', '>=', $request->created_at_start);
        }

        if ($request->filled('created_at_end')) {
            $query->where('created_at', '<=', $request->created_at_end);
        }

        if ($request->filled('gender')) {
            $query->whereHas('owner', function ($ownerQuery) use ($request) {
                $ownerQuery->where('gender', $request->gender);
            });
        }

        if ($request->filled('owner_min_age')) {
            $query->whereHas('owner', function ($ownerQuery) use ($request) {
                $ownerQuery->whereRaw('TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) >= ?', [$request->owner_min_age]);
            });
        }

        if ($request->filled('owner_max_age')) {
            $query->whereHas('owner', function ($ownerQuery) use ($request) {
                $ownerQuery->whereRaw('TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) <= ?', [$request->owner_max_age]);
            });
        }

        if ($request->filled('min_sms_balance')) {
            $query->whereHas('smsBalance', function ($smsQuery) use ($request) {
                $smsQuery->where('balance', '>=', $request->min_sms_balance);
            });
        }

        if ($request->filled('max_sms_balance')) {
            $query->whereHas('smsBalance', function ($smsQuery) use ($request) {
                $smsQuery->where('balance', '<=', $request->max_sms_balance);
            });
        }

        if ($request->filled('last_sms_purchase_start')) {
            $query->whereHas('smsBalance', function ($smsQuery) use ($request) {
                $smsQuery->where('last_purchase_date', '>=', $request->last_sms_purchase_start);
            });
        }

        if ($request->filled('last_sms_purchase_end')) {
            $query->whereHas('smsBalance', function ($smsQuery) use ($request) {
                $smsQuery->where('last_purchase_date', '<=', $request->last_sms_purchase_end);
            });
        }

        if ($request->filled('min_monthly_consumption')) {
            $query->whereHas('smsBalance', function ($smsQuery) use ($request) {
                $smsQuery->where('monthly_consumption', '>=', $request->min_monthly_consumption);
            });
        }

        if ($request->filled('max_monthly_consumption')) {
            $query->whereHas('smsBalance', function ($smsQuery) use ($request) {
                $smsQuery->where('monthly_consumption', '<=', $request->max_monthly_consumption);
            });
        }
    }
}

// کلاس‌های Export برای هر نوع
class SalonsExport implements FromCollection, WithHeadings, WithMapping
{
    private $salons;

    public function __construct($salons)
    {
        $this->salons = $salons;
    }

    public function collection()
    {
        return $this->salons;
    }

    public function headings(): array
    {
        return [
            'شناسه',
            'نام سالن',
            'شماره تماس',
            'مالک',
            'شهر',
            'استان',
            'دسته‌بندی فعالیت',
            'زیردسته‌بندی فعالیت',
            'وضعیت',
            'تاریخ ثبت‌نام',
            'اعتبار پیامک',
            'مصرف کل پیامک'
        ];
    }

    public function map($salon): array
    {
        return [
            $salon->id,
            $salon->name,
            $salon->owner->mobile ?? 'N/A',
            $salon->owner->name ?? 'N/A',
            $salon->city->name ?? 'N/A',
            $salon->city->province->name ?? 'N/A',
            $salon->businessCategory->name ?? 'N/A',
            $salon->businessSubcategories->pluck('name')->implode(', ') ?: 'N/A',
            $salon->is_active ? 'فعال' : 'غیرفعال',
            Jalalian::forge($salon->created_at)->format('Y/m/d'),
            $salon->smsBalance->balance ?? 0,
            $salon->smsTransactions->first()->total_consumed ?? 0
        ];
    }
}

class BulkSmsUsersExport implements FromCollection, WithHeadings, WithMapping
{
    private $salons;

    public function __construct($salons)
    {
        $this->salons = $salons;
    }

    public function collection()
    {
        return $this->salons;
    }

    public function headings(): array
    {
        return [
            'شناسه',
            'نام سالن',
            'شماره تماس',
            'مالک',
            'شهر',
            'استان',
            'اعتبار پیامک',
            'مصرف کل پیامک',
            'مصرف ماهانه'
        ];
    }

    public function map($salon): array
    {
        return [
            $salon->id,
            $salon->name,
            $salon->owner->mobile ?? 'N/A',
            $salon->owner->name ?? 'N/A',
            $salon->city->name ?? 'N/A',
            $salon->city->province->name ?? 'N/A',
            $salon->smsBalance->balance ?? 0,
            $salon->smsTransactions->first()->total_consumed ?? 0,
            $salon->smsBalance->monthly_consumption ?? 0
        ];
    }
}

class BulkSmsGiftUsersExport implements FromCollection, WithHeadings, WithMapping
{
    private $salons;

    public function __construct($salons)
    {
        $this->salons = $salons;
    }

    public function collection()
    {
        return $this->salons;
    }

    public function headings(): array
    {
        return [
            'شناسه',
            'نام سالن',
            'شماره تماس',
            'مالک',
            'شهر',
            'استان',
            'اعتبار پیامک',
            'مصرف کل پیامک'
        ];
    }

    public function map($salon): array
    {
        return [
            $salon->id,
            $salon->name,
            $salon->owner->mobile ?? 'N/A',
            $salon->owner->name ?? 'N/A',
            $salon->city->name ?? 'N/A',
            $salon->city->province->name ?? 'N/A',
            $salon->smsBalance->balance ?? 0,
            $salon->smsTransactions->first()->total_consumed ?? 0
        ];
    }
}

class DiscountCodeUsersExport implements FromCollection, WithHeadings, WithMapping
{
    private $salons;

    public function __construct($salons)
    {
        $this->salons = $salons;
    }

    public function collection()
    {
        return $this->salons;
    }

    public function headings(): array
    {
        return [
            'شناسه',
            'نام سالن',
            'شماره تماس',
            'مالک',
            'شهر',
            'استان',
            'دسته‌بندی فعالیت',
            'زیردسته‌بندی فعالیت',
            'تاریخ ثبت‌نام',
            'اعتبار پیامک',
            'مصرف کل پیامک'
        ];
    }

    public function map($salon): array
    {
        return [
            $salon->id,
            $salon->name,
            $salon->owner->mobile ?? 'N/A',
            $salon->owner->name ?? 'N/A',
            $salon->city->name ?? 'N/A',
            $salon->city->province->name ?? 'N/A',
            $salon->businessCategory->name ?? 'N/A',
            $salon->businessSubcategories->pluck('name')->implode(', ') ?: 'N/A',
            Jalalian::forge($salon->created_at)->format('Y/m/d'),
            $salon->smsBalance->balance ?? 0,
            $salon->smsTransactions->first()->total_consumed ?? 0
        ];
    }
}