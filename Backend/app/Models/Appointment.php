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
        'reminder_time',
        'send_reminder_sms',
        'reminder_sms_status',
        'reminder_sms_message_id',
        'send_satisfaction_sms',
        'satisfaction_sms_status',
        'satisfaction_sms_message_id',
        'deposit_amount',
        'deposit_payment_method',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'start_time' => 'datetime',
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

    // Accessors
    public function getJalaliAppointmentDateAttribute(): ?string
    {
        if ($this->appointment_date) {
            $dateToConvert = $this->appointment_date;
            return Jalalian::fromCarbon($dateToConvert)->format('Y/m/d');
        }
        return null;
    }

    public function getAppointmentDateAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('Y-m-d');
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
}
