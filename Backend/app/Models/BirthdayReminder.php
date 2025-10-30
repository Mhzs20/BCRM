<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BirthdayReminder extends Model
{
    protected $fillable = [
        'salon_id',
        'template_id',
        'is_global_active',
        'send_time',
    ];

    public function customerGroups()
    {
        return $this->belongsToMany(CustomerGroup::class, 'birthday_reminder_customer_group')
            ->withPivot('is_active', 'send_days_before', 'send_time')
            ->withTimestamps();
    }
}
