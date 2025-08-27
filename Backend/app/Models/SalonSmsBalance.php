<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalonSmsBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'salon_id',
        'balance',
    ];

    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }
}
