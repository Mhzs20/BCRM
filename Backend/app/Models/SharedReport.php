<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SharedReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'salon_id',
        'created_by',
        'report_type',
        'token',
        'filters',
        'data',
        'expires_at',
        'view_count',
    ];

    protected $casts = [
        'filters' => 'array',
        'data' => 'array',
        'expires_at' => 'datetime',
        'view_count' => 'integer',
    ];

    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function incrementViewCount()
    {
        $this->increment('view_count');
    }
}
