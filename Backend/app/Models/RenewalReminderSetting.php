<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RenewalReminderSetting extends Model
{
    use HasFactory;

    protected $table = 'renewal_reminder_settings';

    protected $fillable = [
        'salon_id',
        'is_active',
        'active_template_id',
        'reminder_days_before',
        'reminder_time',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'reminder_time' => 'string',
    ];

    /**
     * Get the salon that owns the renewal reminder setting.
     */
    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    /**
     * Get the active template for renewal reminders.
     */
    public function activeTemplate(): BelongsTo
    {
        return $this->belongsTo(SalonSmsTemplate::class, 'active_template_id');
    }
}