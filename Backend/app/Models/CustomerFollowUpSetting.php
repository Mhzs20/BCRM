<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerFollowUpSetting extends Model
{
    use HasFactory;

    protected $table = 'customer_followup_settings';

    protected $fillable = [
        'salon_id',
        'template_id',
        'is_global_active',
    ];

    protected $casts = [
        'is_global_active' => 'boolean',
    ];

    /**
     * تنظیمات مربوط به سالن
     */
    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    /**
     * قالب پیش‌فرض برای پیگیری
     */
    public function template()
    {
        return $this->belongsTo(SalonSmsTemplate::class, 'template_id');
    }

    /**
     * تنظیمات گروه‌های مشتری
     */
    public function groupSettings()
    {
        return $this->hasMany(CustomerFollowUpGroupSetting::class, 'customer_followup_setting_id');
    }
}
