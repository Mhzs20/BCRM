<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerFeedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'staff_id',
        'service_id',
        'rating',
        'text_feedback',
        'strengths_selected',
        'weaknesses_selected',
        'is_submitted',
        'submitted_at',
    ];

    protected $casts = [
        'strengths_selected' => 'array',
        'weaknesses_selected' => 'array',
        'is_submitted' => 'boolean',
        'submitted_at' => 'datetime',
        'rating' => 'integer',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function customer()
    {
        return $this->appointment ? $this->appointment->customer : null;
    }
}
