<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'salon_id',
        'user_id',
        'filters',
        'message',
        'customer_count',
        'total_cost',
        'status',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'uses_template',
        'sms_template_id',
    ];

    protected $casts = [
        'filters' => 'array',
        'approved_at' => 'datetime',
    ];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SmsCampaignMessage::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function smsTemplate(): BelongsTo
    {
        return $this->belongsTo(SalonSmsTemplate::class, 'sms_template_id');
    }
}
