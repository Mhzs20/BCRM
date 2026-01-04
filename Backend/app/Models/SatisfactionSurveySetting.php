<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatisfactionSurveySetting extends Model
{
    protected $fillable = [
        'salon_id',
        'template_id',
        'is_global_active',
    ];

    protected $casts = [
        'is_global_active' => 'boolean',
    ];

    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    public function template()
    {
        return $this->belongsTo(SalonSmsTemplate::class, 'template_id');
    }

    public function customerGroups()
    {
        return $this->belongsToMany(CustomerGroup::class, 'satisfaction_survey_group_settings', 'satisfaction_survey_setting_id', 'customer_group_id')
            ->withPivot('is_active', 'send_hours_after')
            ->withTimestamps();
    }

    public function groupSettings()
    {
        return $this->hasMany(SatisfactionSurveyGroupSetting::class, 'satisfaction_survey_setting_id');
    }
}
