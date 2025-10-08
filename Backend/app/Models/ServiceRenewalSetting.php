<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRenewalSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'salon_id',
        'service_id',
        'is_active',
        'renewal_period_days',
        'reminder_days_before',
        'reminder_time',
        'template_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'reminder_time' => 'string',
    ];

    /**
     * Get the salon that owns the service renewal setting.
     */
    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    /**
     * Get the service that owns the renewal setting.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the template for this service renewal.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(SalonSmsTemplate::class, 'template_id');
    }
}