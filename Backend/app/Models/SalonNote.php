<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalonNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'salon_id',
        'user_id',
        'content',
    ];

    /**
     * Get the salon that owns the note.
     */
    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    /**
     * Get the user that created the note.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
