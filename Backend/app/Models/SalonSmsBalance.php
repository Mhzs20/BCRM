<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalonSmsBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'salon_id',
        'current_sms_count',
    ];

    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }
}
