<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExclusiveLinkPreparation extends Model
{
    protected $table = 'exclusive_link_preparations';

    protected $fillable = [
        'salon_id',
        'template_id',
        'recipients_type',
        'recipients',
        'recipients_count',
        'estimated_parts',
        'estimated_cost',
        'sample',
        'expires_at',
    ];

    protected $casts = [
        'recipients' => 'array',
        'sample' => 'array',
        'estimated_cost' => 'decimal:2',
        'expires_at' => 'datetime',
    ];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SalonSmsTemplate::class, 'template_id');
    }
}
