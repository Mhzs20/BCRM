<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject; // برای JWT

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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'password' => 'hashed',
        'is_verified' => 'boolean',
        'profile_completed' => 'boolean',
        'active_salon_id' => 'integer',
        'business_category_id' => 'integer',
        'business_subcategory_id' => 'integer',
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
        if ($roleName === 'admin' && $this->email === 'admin@example.com') { // این فقط یک مثال است!
            return true;
        }
        return false;
    }
}
