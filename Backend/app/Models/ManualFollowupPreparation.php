<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualFollowupPreparation extends Model
{
    use HasFactory;

    protected $fillable = [
        'salon_id',
        'customer_group_ids',
        'service_ids',
        'days_since_last_visit',
        'customer_ids',
        'customer_count',
        'message_parts',
        'cost_per_message',
        'total_cost',
        'sample_message',
        'expires_at',
    ];

    protected $casts = [
        'customer_group_ids' => 'array',
        'service_ids' => 'array',
        'customer_ids' => 'array',
        'expires_at' => 'datetime',
    ];

    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    public function isExpired()
    {
        return $this->expires_at->isPast();
    }
}
