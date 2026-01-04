<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'customer_id',
        'salon_id',
        'notes',
        'images',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    /**
     * Get the appointment that owns the attachment.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the customer that owns the attachment.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the salon that owns the attachment.
     */
    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    /**
     * Get full URL paths for images.
     *
     * @return array
     */
    public function getImageUrlsAttribute(): array
    {
        if (empty($this->images)) {
            return [];
        }

        return array_map(function ($image) {
            return asset('storage/' . $image);
        }, $this->images);
    }

    /**
     * Scope to filter attachments by date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $startDate
     * @param  string  $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereHas('appointment', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('appointment_date', [$startDate, $endDate]);
        });
    }

    /**
     * Scope to filter attachments by service type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $serviceId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByService($query, $serviceId)
    {
        return $query->whereHas('appointment.services', function ($q) use ($serviceId) {
            $q->where('services.id', $serviceId);
        });
    }
}
