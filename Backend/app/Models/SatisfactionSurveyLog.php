<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatisfactionSurveyLog extends Model
{
    protected $fillable = [
        'appointment_id',
        'customer_id',
        'salon_id',
        'scheduled_at',
        'sent_at',
        'status',
        'error_message',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }
}
