<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerFollowUpHistory extends Model
{
    use HasFactory;

    protected $table = 'customer_followup_histories';

    protected $fillable = [
        'salon_id',
        'customer_id',
        'template_id',
        'message',
        'sent_at',
        'type', // 'manual' or 'automatic'
        'customer_group_ids',
        'service_ids',
        'total_customers',
        'sms_count',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'customer_group_ids' => 'array',
        'service_ids' => 'array',
    ];

    /**
     * سالن مربوطه
     */
    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    /**
     * مشتری مربوطه
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * قالب استفاده شده
     */
    public function template()
    {
        return $this->belongsTo(SalonSmsTemplate::class, 'template_id');
    }
}
