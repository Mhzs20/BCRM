<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerFollowUpServiceSetting extends Model
{
    use HasFactory;

    protected $table = 'customer_followup_service_settings';

    protected $fillable = [
        'customer_followup_setting_id',
        'service_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function customerFollowUpSetting()
    {
        return $this->belongsTo(CustomerFollowUpSetting::class, 'customer_followup_setting_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
