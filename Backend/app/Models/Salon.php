<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\User;
use App\Models\BusinessCategory;
use App\Models\BusinessSubcategory;
use App\Models\Province;
use App\Models\City;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\SalonSmsTemplate;

class Salon extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'business_category_id',
        'business_subcategory_id',
        'province_id',
        'city_id',
        'address',
        'mobile',
        'phone',
        'website',
        'instagram',
        'telegram',
        'whatsapp',
        'location',
        'image',
        'description',
        'is_active',
        'credit_score',
    ];

    protected $casts = [
        'credit_expiry_date' => 'date',
    ];

    /**
     * Get the user that owns the salon.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the business category of the salon.
     */
    public function businessCategory()
    {
        return $this->belongsTo(BusinessCategory::class);
    }

    /**
     * Get the business subcategory of the salon.
     */
    public function businessSubcategory()
    {
        return $this->belongsTo(BusinessSubcategory::class);
    }

    /**
     * Get the province of the salon.
     */
    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * Get the city of the salon.
     */
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the customers for the salon.
     */
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get the staff for the salon.
     */
    public function staff()
    {
        return $this->hasMany(Staff::class);
    }

    /**
     * Get the services offered by the salon.
     */
    public function services()
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Get the appointments for the salon.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get the activity logs for the salon.
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Get the payments received by the salon.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the SMS templates for the salon.
     */
    public function smsTemplates()
    {
        return $this->hasMany(SalonSmsTemplate::class);
    }

    /**
     * Retrieves a specific SMS template for the salon based on the event type.
     *
     * @param string $eventType The type of the event (e.g., 'appointment_confirmation').
     * @return SalonSmsTemplate|null The SMS template if found, otherwise null.
     */
    public function getSmsTemplate(string $eventType): ?SalonSmsTemplate
    {
        return $this->smsTemplates()->where('event_type', $eventType)->first();
    }
    public function howIntroduceds()
    {
        return $this->hasMany(HowIntroduced::class);
    }

    public function customerGroups()
    {
        return $this->hasMany(CustomerGroup::class);
    }

    public function jobs()
    {
        return $this->hasMany(Profession::class);
    }

    public function ageRanges()
    {
        return $this->hasMany(AgeRange::class);
    }
}
