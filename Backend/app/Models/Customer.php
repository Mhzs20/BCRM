<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Profession;
class Customer extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'salon_id',
        'name',
        'phone_number',
        'birth_date',
        'gender',
        'address',
        'notes',
        'emergency_contact',
        'how_introduced_id',
        'customer_group_id',
        'profession_id',
        'age_range_id',
        'city_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
    ];

    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function howIntroduced()
    {
        return $this->belongsTo(HowIntroduced::class);
    }

    public function customerGroup()
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    public function profession()
    {
        return $this->belongsTo(Profession::class, 'profession_id');
    }

    public function ageRange()
    {
        return $this->belongsTo(AgeRange::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
