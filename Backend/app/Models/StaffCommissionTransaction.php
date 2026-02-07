<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

/**
 * تراکنش‌های پورسانت کارکنان
 */
class StaffCommissionTransaction extends Model
{
    use HasFactory;

    protected $table = 'staff_commission_transactions';

    protected $fillable = [
        'salon_id',
        'staff_id',
        'appointment_id',
        'service_id',
        'transaction_type',
        'service_price',
        'discount_amount',
        'base_amount',
        'commission_rate',
        'commission_type',
        'amount',
        'payment_status',
        'paid_at',
        'description',
        'created_by',
    ];

    protected $casts = [
        'service_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    protected $appends = ['jalali_created_at', 'jalali_paid_at'];

    // ثابت‌های نوع تراکنش
    const TYPE_COMMISSION = 'commission';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_PAYMENT = 'payment';

    // ثابت‌های وضعیت پرداخت
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';

    /**
     * رابطه با سالن
     */
    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    /**
     * رابطه با کارکن
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * رابطه با نوبت
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * رابطه با خدمت
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * رابطه با کاربر ایجادکننده
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * تاریخ شمسی ایجاد
     */
    public function getJalaliCreatedAtAttribute(): ?string
    {
        if ($this->created_at) {
            return Jalalian::fromCarbon($this->created_at)->format('Y/m/d H:i');
        }
        return null;
    }

    /**
     * تاریخ شمسی پرداخت
     */
    public function getJalaliPaidAtAttribute(): ?string
    {
        if ($this->paid_at) {
            return Jalalian::fromCarbon(Carbon::parse($this->paid_at))->format('Y/m/d H:i');
        }
        return null;
    }

    /**
     * اسکوپ برای فیلتر بر اساس وضعیت پرداخت
     */
    public function scopePending($query)
    {
        return $query->where('payment_status', self::STATUS_PENDING);
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', self::STATUS_PAID);
    }

    /**
     * اسکوپ برای فیلتر بر اساس نوع تراکنش
     */
    public function scopeCommissions($query)
    {
        return $query->where('transaction_type', self::TYPE_COMMISSION);
    }

    public function scopeAdjustments($query)
    {
        return $query->where('transaction_type', self::TYPE_ADJUSTMENT);
    }

    public function scopePayments($query)
    {
        return $query->where('transaction_type', self::TYPE_PAYMENT);
    }

    /**
     * اسکوپ برای فیلتر بر اساس بازه زمانی
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * اسکوپ برای گرفتن تراکنش‌های یک ماه مشخص (شمسی)
     */
    public function scopeInJalaliMonth($query, int $year, int $month)
    {
        $startJalali = Jalalian::fromFormat('Y-m-d', sprintf('%d-%02d-01', $year, $month));
        $startDate = $startJalali->toCarbon()->startOfDay();
        
        // آخرین روز ماه شمسی
        $daysInMonth = $startJalali->getMonthDays();
        $endJalali = Jalalian::fromFormat('Y-m-d', sprintf('%d-%02d-%02d', $year, $month, $daysInMonth));
        $endDate = $endJalali->toCarbon()->endOfDay();

        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * علامت‌گذاری به عنوان پرداخت شده
     */
    public function markAsPaid(): bool
    {
        $this->payment_status = self::STATUS_PAID;
        $this->paid_at = now();
        return $this->save();
    }
}
