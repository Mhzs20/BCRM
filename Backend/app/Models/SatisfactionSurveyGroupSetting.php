<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatisfactionSurveyGroupSetting extends Model
{
    protected $fillable = [
        'satisfaction_survey_setting_id',
        'customer_group_id',
        'is_active',
        'send_hours_after',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function satisfactionSurveySetting()
    {
        return $this->belongsTo(SatisfactionSurveySetting::class, 'satisfaction_survey_setting_id');
    }

    public function customerGroup()
    {
        return $this->belongsTo(CustomerGroup::class);
    }
}
