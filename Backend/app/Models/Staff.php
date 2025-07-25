<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'salon_staff';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'salon_id',
        'full_name',
        'specialty',
        'phone_number',
        'address',
        'profile_image',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the salon that owns the staff.
     */
    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    /**
     * Get the appointments for the staff.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'staff_id');
    }

    /**
     * The services that belong to the staff.
     */
    public function services()
    {
        return $this->belongsToMany(Service::class, 'service_staff', 'staff_id', 'service_id');
    }

    /**
     * Get the schedules for the staff.
     */
    public function schedules()
    {
        return $this->hasMany(StaffSchedule::class, 'staff_id');
    }
}
