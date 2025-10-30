<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BirthdayReminderCustomerGroup extends Model
{
    protected $table = 'birthday_reminder_customer_group';
    protected $fillable = [
        'birthday_reminder_id',
        'customer_group_id',
        'is_active',
        'send_days_before',
    ];
}
