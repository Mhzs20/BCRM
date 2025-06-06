<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // اضافه شد

class Staff extends Model
{
    use HasFactory, SoftDeletes; // SoftDeletes اضافه شد

    protected $table = 'salon_staff'; // نام جدول شما

    protected $fillable = [
        'salon_id',
        'full_name',        // مدل شما 'full_name' داشت
        'specialty',
        'gender',
        'phone_number',
        'address',
        'profile_image',
        'is_active',        // این فیلد در مدل شما وجود داشت
        // 'user_id' // اگر پرسنل به یک کاربر در جدول users متصل است
        // سایر فیلدهایی که ممکن است در مایگریشن salon_staff شما وجود داشته باشد
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // تاریخ‌های حذف نرم
    protected $dates = ['deleted_at'];

    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    /**
     * Get the appointments for the staff member.
     */
    public function appointments() // قبلاً appointment بود
    {
        return $this->hasMany(Appointment::class, 'staff_id');
    }

    /**
     * The services that this staff member can perform.
     */
    public function services()
    {
        // نام جدول واسط service_staff است
        return $this->belongsToMany(Service::class, 'service_staff', 'staff_id', 'service_id');
    }

    /**
     * Get the schedules for the staff member.
     */
    public function schedules()
    {
        return $this->hasMany(StaffSchedule::class, 'staff_id');
    }
}
