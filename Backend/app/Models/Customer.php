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
        'profile_image',
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
        return $this->belongsTo(Profession::class);
    }

    public function ageRange()
    {
        return $this->belongsTo(AgeRange::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function getProfileImageAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }

    /**
     * Set the customer's phone number.
     *
     * @param  string  $value
     * @return void
     */
    public function setPhoneNumberAttribute($value)
    {
        // Trim whitespace and remove non-numeric characters
        $this->attributes['phone_number'] = preg_replace('/[^0-9]/', '', $value);
    }
}
