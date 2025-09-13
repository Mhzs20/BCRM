<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'title',
        'message',
        'is_important',
    ];

    public function salons()
    {
        return $this->belongsToMany(Salon::class, 'notification_salon')
            ->withPivot('is_read')
            ->withTimestamps();
    }
}
