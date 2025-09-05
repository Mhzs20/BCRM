<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalonSmsTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'salon_id',
        'category_id',
        'event_type',
        'title',
        'template',
        'is_active',
        'template_type',
    ];

    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    public function category()
    {
        return $this->belongsTo(SmsTemplateCategory::class, 'category_id');
    }
}
