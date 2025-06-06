<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Morilog\Jalali\Jalalian;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'salon_id',
        'customer_id',
        'staff_id',
        'appointment_date',
        'start_time',
        'end_time',
        'total_price',
        'total_duration',
        'status',
        'notes',
        'deposit_required',
        'deposit_paid',
        'reminder_sms_sent_at',
        'survey_sms_sent_at',
        'feedback_id',
    ];
    // =================================================================

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'appointment_date' => 'date',
        'deposit_required' => 'boolean',
        'deposit_paid' => 'boolean',
        'reminder_sms_sent_at' => 'datetime',
        'survey_sms_sent_at' => 'datetime',
        'total_price' => 'decimal:2',
        'total_duration' => 'integer',
    ];

    /**
     * The dates that should be mutated to instances of Carbon.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    // Relationships
    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'appointment_service')
            ->withPivot('price_at_booking', 'duration_at_booking')
            ->withTimestamps();
    }

    public function feedback(): BelongsTo
    {
         return $this->belongsTo(CustomerFeedback::class, 'feedback_id');
    }

    // Accessors
    public function getJalaliAppointmentDateAttribute(): ?string
    {
        if ($this->appointment_date) {
            $dateToConvert = $this->appointment_date;
            return Jalalian::fromCarbon($dateToConvert)->format('Y/m/d');
        }
        return null;
    }
}
