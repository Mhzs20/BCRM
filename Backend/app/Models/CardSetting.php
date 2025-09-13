<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CardSetting extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'is_active',
        'card_number',
        'card_holder_name',
        'description',
    ];
}
