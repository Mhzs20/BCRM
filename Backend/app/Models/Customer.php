<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'salon_id',
        'name',
        'phone_number',
        'email',
        'birth_date',
        'address',
        'how_introduced_id',
        'customer_group_id',
        'job_id',
        'age_range_id',
        'gender',
        'description',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];


    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function howIntroduced()
    {
        return $this->belongsTo(HowIntroduced::class, 'how_introduced_id');
    }

    public function customerGroup()
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function ageRange()
    {
        return $this->belongsTo(AgeRange::class, 'age_range_id');
    }
}
