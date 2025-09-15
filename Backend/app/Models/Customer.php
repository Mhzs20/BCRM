<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Profession;
use Morilog\Jalali\Jalalian;

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
        'birth_date' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'jalali_birthdate',
    ];

    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
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
     * Get the customer's birth date with correct timezone.
     *
     * @param  mixed  $value
     * @return \Carbon\Carbon|null
     */
    public function getBirthDateAttribute($value): ?\Carbon\Carbon
    {
        if ($value) {
            if ($value instanceof \Carbon\Carbon) {
                // Return as UTC for ISO format
                return $value->setTimezone('UTC');
            } else {
                // Parse string and return as UTC
                try {
                    return \Carbon\Carbon::parse($value)->setTimezone('UTC');
                } catch (\Exception $e) {
                    return null;
                }
            }
        }
        return null;
    }

    /**
     * Get the customer's birth date in Jalali format.
     *
     * @return string|null
     */
    public function getJalaliBirthdateAttribute(): ?string
    {
        $birthDate = $this->attributes['birth_date'] ?? null;
        if ($birthDate) {
            try {
                $carbon = \Carbon\Carbon::parse($birthDate)->setTimezone('Asia/Tehran');
                return Jalalian::fromCarbon($carbon)->format('Y/m/d');
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
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
