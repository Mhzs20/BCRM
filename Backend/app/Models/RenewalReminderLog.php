<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RenewalReminderLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'customer_id',
        'salon_id',
        'service_id',
        'template_id',
        'message_content',
        'status',
        'sms_message_id',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Get the appointment that owns the renewal reminder log.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the customer that owns the renewal reminder log.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the salon that owns the renewal reminder log.
     */
    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    /**
     * Get the service that owns the renewal reminder log.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the template used for this reminder.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(SalonSmsTemplate::class, 'template_id');
    }
}