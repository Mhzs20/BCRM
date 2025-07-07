<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Customer;
use App\Models\Salon;

class Profession extends Model
{
    use HasFactory;

    protected $table = 'professions';

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
        return $this->hasMany(Customer::class, 'profession_id');
    }
}
