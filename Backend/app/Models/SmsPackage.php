<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsPackage extends Model
{
    protected $fillable = [
        'name',
        'sms_count',
        'price',
        'is_active',
        'purchase_link',
    ];
}
