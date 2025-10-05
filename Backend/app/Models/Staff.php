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
        'hire_date',
        'total_appointments',
        'completed_appointments',
        'canceled_appointments',
        'total_income',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = []; // Remove profile_image_url from appends as we are overriding the original attribute

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'total_appointments' => 'integer',
        'completed_appointments' => 'integer',
        'canceled_appointments' => 'integer',
        'total_income' => 'decimal:2',
    ];

    /**
     * Get the staff's profile image path.
     *
     * @param  string|null  $value
     * @return string|null
     */
    public function getProfileImageAttribute($value)
    {
        return $value ? '/storage/' . $value : null;
    }

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

    /**
     * Update staff statistics based on appointments.
     */
    public function updateStatistics()
    {
        $stats = $this->appointments()
            ->selectRaw('
                COUNT(*) as total_appointments,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_appointments,
                SUM(CASE WHEN status IN ("canceled", "cancelled") THEN 1 ELSE 0 END) as canceled_appointments,
                SUM(CASE WHEN status = "completed" THEN total_price ELSE 0 END) as total_income
            ')
            ->first();

        $this->update([
            'total_appointments' => $stats->total_appointments ?? 0,
            'completed_appointments' => $stats->completed_appointments ?? 0,
            'canceled_appointments' => $stats->canceled_appointments ?? 0,
            'total_income' => $stats->total_income ?? 0,
        ]);
    }
}
