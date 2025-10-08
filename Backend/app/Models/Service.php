<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'services';

    protected $fillable = [
        'salon_id',
        'name',
        'price',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    /**
     * The staff that perform this service.
     */
    public function staff()
    {
        return $this->belongsToMany(Staff::class, 'service_staff');
    }

    /**
     * The appointments that include this service.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function appointments()
    {
        // فرض شده است که یک جدول واسط به نام appointment_service وجود دارد.
        // این رابطه برای شمارش رزروها ضروری است.
        return $this->belongsToMany(Appointment::class, 'appointment_service');
    }

    /**
     * Get the renewal reminder setting for this service.
     */
    public function renewalSetting()
    {
        return $this->hasOne(ServiceRenewalSetting::class);
    }
}
