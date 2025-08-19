<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerFeedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'rating',
        'text_feedback',
        'strengths_selected',
        'weaknesses_selected',
    ];

    protected $casts = [
        'strengths_selected' => 'array',
        'weaknesses_selected' => 'array',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
