<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'salon_id',
        'package_id',
        'order_id',
        'amount_paid',
        'status',
        'purchased_at',
        'expires_at',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:0',
        'purchased_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user who purchased the package
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the salon that owns this package
     */
    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    /**
     * Get the package
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Get the order
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
