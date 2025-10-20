<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PendingAppointment extends Model
{
    use HasFactory;

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
        'internal_note',
        'deposit_required',
        'deposit_paid',
        'deposit_amount',
        'deposit_payment_method',
        'reminder_time',
        'send_reminder_sms',
        'send_satisfaction_sms',
        'send_confirmation_sms',
        'confirmation_sms_template_id',
        'reminder_sms_template_id',
        'service_ids',
        'new_customer_data',
        'conflicting_appointments',
        'expires_at',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'total_price' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'deposit_required' => 'boolean',
        'deposit_paid' => 'boolean',
        'send_reminder_sms' => 'boolean',
        'send_satisfaction_sms' => 'boolean',
        'send_confirmation_sms' => 'boolean',
        'service_ids' => 'array',
        'new_customer_data' => 'array',
        'conflicting_appointments' => 'array',
        'expires_at' => 'datetime',
    ];

    // Relations
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

    // Scopes
    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>', Carbon::now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', Carbon::now());
    }

    // Methods
    public function isExpired(): bool
    {
        return $this->expires_at <= Carbon::now();
    }

    public function getExpiresInMinutes(): int
    {
        return max(0, Carbon::now()->diffInMinutes($this->expires_at, false));
    }

    // Auto-cleanup expired records
    public static function cleanupExpired(): int
    {
        return self::expired()->delete();
    }
}
