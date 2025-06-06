<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffSchedule extends Model
{
    use HasFactory;

    protected $table = 'staff_schedules';

    protected $fillable = [
        'staff_id',
        'day_of_week',  // 0 for Sunday, 1 for Monday, ..., 6 for Saturday (مطابق با Carbon::dayOfWeek)
        'start_time',   // e.g., "09:00" or "09:00:00"
        'end_time',     // e.g., "17:00" or "17:00:00"
        'is_active',    // boolean: true if it's a working day/slot, false if it's a day off
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the staff member that this schedule belongs to.
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}
