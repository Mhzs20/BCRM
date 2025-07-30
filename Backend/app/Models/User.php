<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject; // برای JWT
use Hekmatinasser\Verta\Verta;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',
        'business_name',
        'business_category_id',
        'business_subcategory_id',
        'avatar',
        'otp_code',
        'otp_expires_at',
        'active_salon_id',
        'is_verified',
        'profile_completed',
        'gender',
        'date_of_birth',
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'otp_code',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = []; // Ensure no appended attributes

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'password' => 'hashed',
        'is_verified' => 'boolean',
        'is_superadmin' => 'boolean',
        'profile_completed' => 'boolean',
        'active_salon_id' => 'integer',
        'business_category_id' => 'integer',
        'business_subcategory_id' => 'integer',
        // Do NOT cast date_of_birth here, let the accessor handle it
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [
            'user_id' => $this->id,
            'name' => $this->name,
            'mobile' => $this->mobile,
            'active_salon_id' => $this->active_salon_id,
        ];
    }

    /**
     * Get the business category associated with the user.
     */
    public function businessCategory()
    {
        return $this->belongsTo(BusinessCategory::class, 'business_category_id');
    }

    /**
     * Get all salons created by this user.
     */
    public function salons()
    {
        return $this->hasMany(Salon::class, 'user_id');
    }

    /**
     * Get the business subcategory associated with the user.
     */
    public function businessSubcategory()
    {
        return $this->belongsTo(BusinessSubcategory::class, 'business_subcategory_id');
    }

    /**
     * Get the currently active salon for the user.
     */
    public function activeSalon()
    {
        return $this->belongsTo(Salon::class, 'active_salon_id');
    }

    public function salon()
    {
        return $this->hasOne(Salon::class, 'user_id');
    }

    /**
     * Get the SMS balance for the user.
     */
    public function smsBalance()
    {
        return $this->hasOne(UserSmsBalance::class, 'user_id');
    }

    /**
     * Helper method to check if the user has a specific role.
     */
    public function hasRole(string $roleName): bool
    {
        if ($roleName === 'salon_owner' && $this->activeSalon()->exists()) {
            return true;
        }
        if ($roleName === 'admin' && $this->email === 'admin@example.com') {
            return true;
        }
        return false;
    }

    /**
     * Get the user's date of birth in Jalali format.
     *
     * @param  string|null  $value
     * @return string|null
     */
    public function getDateOfBirthAttribute(?string $value): ?string
    {
        if ($value) {
            try {
                // Parse the Gregorian date from DB and format it as Jalali
                return Verta::parse($value)->format('Y-m-d');
            } catch (\Exception $e) {
                Log::warning("User model: Could not convert date_of_birth '{$value}' to Jalali: " . $e->getMessage());
                return null; // Return null if conversion fails
            }
        }
        return null;
    }
}
