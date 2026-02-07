<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AdminOtpVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'mobile',
        'otp_code',
        'temp_token',
        'is_verified',
        'expires_at',
        'verified_at',
        'ip_address',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Generate a random OTP code.
     */
    public static function generateOtpCode($length = 6)
    {
        return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a temporary token for registration.
     */
    public static function generateTempToken()
    {
        return Str::random(64);
    }

    /**
     * Check if OTP is expired.
     */
    public function isExpired()
    {
        return now()->greaterThan($this->expires_at);
    }

    /**
     * Check if OTP is valid (not expired and not verified yet).
     */
    public function isValid()
    {
        return !$this->is_verified && !$this->isExpired();
    }

    /**
     * Mark OTP as verified and generate temp token.
     */
    public function markAsVerified()
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
            'temp_token' => self::generateTempToken(),
        ]);

        return $this->temp_token;
    }

    /**
     * Scope to get unverified OTPs.
     */
    public function scopeUnverified($query)
    {
        return $query->where('is_verified', false);
    }

    /**
     * Scope to get valid OTPs (not expired and not verified).
     */
    public function scopeValid($query)
    {
        return $query->where('is_verified', false)
            ->where('expires_at', '>', now());
    }

    /**
     * Scope to filter by mobile.
     */
    public function scopeByMobile($query, $mobile)
    {
        return $query->where('mobile', $mobile);
    }
}
