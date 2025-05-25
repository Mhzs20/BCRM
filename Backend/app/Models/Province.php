<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'slug',
        'tel_prefix',
        'latitude',
        'longitude'
    ];


    public function cities()
    {
        return $this->hasMany(City::class);
    }
}