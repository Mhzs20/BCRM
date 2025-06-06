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
        return $this->hasMany(Customer::class);
    }
}
