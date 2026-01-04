<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerFollowUpGroupSetting extends Model
{
    use HasFactory;

    protected $table = 'customer_followup_group_settings';

    protected $fillable = [
        'customer_followup_setting_id',
        'customer_group_id',
        'is_active',
        'days_since_last_visit',
        'check_frequency_days',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'days_since_last_visit' => 'integer',
        'check_frequency_days' => 'integer',
    ];

    /**
     * تنظیمات اصلی پیگیری مشتری
     */
    public function customerFollowUpSetting()
    {
        return $this->belongsTo(CustomerFollowUpSetting::class, 'customer_followup_setting_id');
    }

    /**
     * گروه مشتری
     */
    public function customerGroup()
    {
        return $this->belongsTo(CustomerGroup::class);
    }
}
