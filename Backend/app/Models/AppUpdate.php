<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppUpdate extends Model
{
    protected $fillable = [
        'version',
        'direct_link',
        'google_play_link',
        'cafe_bazaar_link',
        'app_store_link',
    ];
}
