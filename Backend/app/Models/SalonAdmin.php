<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class SalonAdmin extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'salon_id',
        'created_by',
        'first_name',
        'last_name',
        'mobile',
        'email',
        'password',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'email_verified_at' => 'datetime',
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
            'type' => 'salon_admin',
            'salon_id' => $this->salon_id,
        ];
    }

    /**
     * Get the salon that this admin belongs to.
     */
    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    /**
     * Get the user who created this admin.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all permissions assigned to this admin.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'salon_admin_permissions');
    }

    /**
     * Check if admin has a specific permission.
     */
    public function hasPermission($permissionName)
    {
        return $this->permissions()
            ->where('name', $permissionName)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Check if admin has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions)
    {
        return $this->permissions()
            ->whereIn('name', $permissions)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Check if admin has all of the given permissions.
     */
    public function hasAllPermissions(array $permissions)
    {
        $count = $this->permissions()
            ->whereIn('name', $permissions)
            ->where('is_active', true)
            ->count();

        return $count === count($permissions);
    }

    /**
     * Sync permissions for this admin.
     */
    public function syncPermissions(array $permissionIds)
    {
        $this->permissions()->sync($permissionIds);
    }

    /**
     * Get full name of the admin.
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Scope to get only active admins.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by salon.
     */
    public function scopeBySalon($query, $salonId)
    {
        return $query->where('salon_id', $salonId);
    }

    /**
     * Update last login timestamp.
     */
    public function updateLastLogin()
    {
        $this->update(['last_login_at' => now()]);
    }
}
