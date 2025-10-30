<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'salon_id',
        'name',
    ];

    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    public function customers()
    {
        return $this->belongsToMany(Customer::class)->withTimestamps();
    }
    // Add relationship to BirthdayReminder
    public function birthdayReminders()
    {
        return $this->belongsToMany(BirthdayReminder::class, 'birthday_reminder_customer_group', 'customer_group_id', 'birthday_reminder_id')
            ->withPivot('is_active', 'send_days_before', 'send_time')
            ->withTimestamps();
    }
}
