<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * تنظیمات پورسانت هر خدمت برای هر کارکن
 */
class StaffServiceCommission extends Model
{
    use HasFactory;

    protected $table = 'staff_service_commissions';

    protected $fillable = [
        'staff_id',
        'service_id',
        'salon_id',
        'commission_type',
        'commission_value',
        'is_active',
    ];

    protected $casts = [
        'commission_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * رابطه با کارکن
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * رابطه با خدمت
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * رابطه با سالن
     */
    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    /**
     * محاسبه مبلغ پورسانت بر اساس مبلغ ورودی
     */
    public function calculateCommission(float $amount): float
    {
        if (!$this->is_active) {
            return 0;
        }

        if ($this->commission_type === 'percentage') {
            return round(($amount * $this->commission_value) / 100, 2);
        }

        // مبلغ ثابت
        return (float) $this->commission_value;
    }
}
