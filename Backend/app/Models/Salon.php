<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salon extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'user_id',
        'business_category_id',
        'business_subcategory_id',
        'image',
        'credit_score',
        'credit_expiry_date',
        'province_id',
        'city_id',
    ];

    protected $casts = [
        'credit_expiry_date' => 'date',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function businessCategory()
    {
        return $this->belongsTo(BusinessCategory::class);
    }


    public function businessSubcategory()
    {
        return $this->belongsTo(BusinessSubcategory::class);
    }


    public function province()
    {
        return $this->belongsTo(Province::class);
    }


    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
