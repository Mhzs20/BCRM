<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Morilog\Jalali\Jalalian;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'hash',
        'salon_id',
        'customer_id',
        'staff_id',
        'appointment_date',
        'repair_date',
        'start_time',
        'end_time',
        'total_price',
        'total_duration',
        'status',
        'notes',
        'internal_note',
        'deposit_required',
        'deposit_paid',
        'reminder_sms_sent_at',
        'survey_sms_sent_at',
        'feedback_id',
        'reminder_time',
        'send_reminder_sms',
        'reminder_sms_status',
        'reminder_sms_message_id',
        'send_satisfaction_sms',
        'satisfaction_sms_status',
        'satisfaction_sms_message_id',
        'send_confirmation_sms',
        'confirmation_sms_template_id',
        'reminder_sms_template_id',
        'deposit_amount',
        'deposit_payment_method',
    ];

    protected $appends = ['jalali_appointment_date']; // Keep jalali_appointment_date appended

    protected $casts = [
        'appointment_date' => 'date',
        'repair_date' => 'date',
        'start_time' => 'string',
        'end_time' => 'string', // Ensure end_time is also cast as string
        'deposit_required' => 'boolean',
        'deposit_paid' => 'boolean',
        'reminder_sms_sent_at' => 'datetime',
        'survey_sms_sent_at' => 'datetime',
        'total_price' => 'decimal:2',
        'total_duration' => 'integer',
        'deposit_amount' => 'decimal:2',
        'deposit_payment_method' => 'string',
    ];

    protected $dates = ['deleted_at'];

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        // If the attribute being serialized is 'appointment_date',
        // combine it with 'start_time' and format in Asia/Tehran timezone.
        if ($this->isDateAttribute('appointment_date') && $this->appointment_date && $this->appointment_date->equalTo($date)) {
            // Create a Carbon instance from appointment_date and start_time in Tehran timezone
            $tehranDateTime = \Carbon\Carbon::parse($this->appointment_date->format('Y-m-d') . ' ' . $this->start_time, 'Asia/Tehran');
            // Format as ISO 8601 in Asia/Tehran timezone with offset
            return $tehranDateTime->toIso8601String();
        }
        // For all other datetime attributes, format them in Asia/Tehran timezone.
        return \Carbon\Carbon::instance($date)->setTimezone('Asia/Tehran')->toIso8601String();
    }

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
            ->withPivot('price_at_booking') // Removed duration_at_booking
            ->withTimestamps();
    }

    public function renewalLogs()
    {
        return $this->hasMany(RenewalReminderLog::class);
    }

    // Accessors
    public function getJalaliAppointmentDateAttribute(): ?string
    {
        if ($this->appointment_date && $this->start_time) {
            $dateTime = \Carbon\Carbon::parse($this->appointment_date->format('Y-m-d') . ' ' . $this->start_time, 'Asia/Tehran');
            return Jalalian::fromCarbon($dateTime)->format('Y/m/d');
        }
        return null;
    }

    // Scopes
    public function scopeMonthlyCount($query, $year, $month)
    {
        // Convert Jalali year and month to Gregorian start and end dates for the month
        $jalaliStartDate = Jalalian::fromFormat('Y-m-d', "$year-$month-01");
        $startDate = $jalaliStartDate->toCarbon()->startOfDay();
        $endDate = $jalaliStartDate->toCarbon()->endOfMonth()->endOfDay();

        return $query->whereBetween('appointment_date', [$startDate, $endDate])
                     ->select(DB::raw('appointment_date, COUNT(*) as count'))
                     ->groupBy('appointment_date')
                     ->orderBy('appointment_date');
    }

    public function feedback()
    {
        return $this->hasOne(CustomerFeedback::class);
    }

    public function confirmationSmsTemplate()
    {
        return $this->belongsTo(SalonSmsTemplate::class, 'confirmation_sms_template_id');
    }

    public function reminderSmsTemplate()
    {
        return $this->belongsTo(SalonSmsTemplate::class, 'reminder_sms_template_id');
    }
}
