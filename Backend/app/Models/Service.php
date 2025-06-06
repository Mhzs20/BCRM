<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'salon_id',
        'name',
        'price',
        'duration_minutes',
        'description',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $dates = ['deleted_at'];

    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    /**
     * The appointments that include this service.
     */
    public function appointments()
    {
        return $this->belongsToMany(Appointment::class, 'appointment_service')
            ->withPivot('price_at_booking', 'duration_at_booking')
            ->withTimestamps();
    }

    /**
     * The staff members who can perform this service.
     */
    public function staff()
    {

        return $this->belongsToMany(Staff::class, 'service_staff', 'service_id', 'staff_id');
    }
}
