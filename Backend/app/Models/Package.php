<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'gift_sms_count',
        'duration_days',
        'is_active',
        'is_gift_only',
    ];

    protected $casts = [
        'price' => 'decimal:0',
        'gift_sms_count' => 'integer',
        'duration_days' => 'integer',
        'is_active' => 'boolean',
        'is_gift_only' => 'boolean',
    ];

    /**
     * Get the options associated with this package
     */
    public function options()
    {
        return $this->belongsToMany(Option::class, 'package_option');
    }

    /**
     * Get the users who have purchased this package
     */
    public function userPackages()
    {
        return $this->hasMany(UserPackage::class);
    }
}
