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
use App\Models\Profession;
use App\Models\SalonSmsBalance;
use App\Models\SmsTransaction;
use App\Models\SalonNote; // New model for notes
use App\Models\Order;

class Salon extends Model
{
    use HasFactory;

    protected $with = ['businessCategory', 'businessSubcategories', 'user', 'city', 'province'];

    protected $fillable = [
        'user_id',
        'name',
        'business_category_id',
        'province_id',
        'city_id',
        'address',
        'mobile',
        'email', // Added email to fillable
        'phone',
        'website',
        'support_phone_number',
        'bio',
        'instagram',
        'telegram',
        'whatsapp',
        'lat',
        'lang',
        'location',
        'image',
        'description',
        'is_active',
        'credit_score',
    ];

    protected $casts = [
        'credit_expiry_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the salon.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the owner (user) that owns the salon. This is an alias for user() for clarity in admin panel.
     */
    public function owner()
    {
        return $this->user(); // Use the existing user relationship
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
    public function businessSubcategories()
    {
        return $this->belongsToMany(BusinessSubcategory::class, 'salon_business_subcategory', 'salon_id', 'business_subcategory_id');
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

    public function smsTemplateCategories()
    {
        return $this->hasMany(\App\Models\SmsTemplateCategory::class);
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

    public function professions()
    {
        return $this->hasMany(Profession::class);
    }

    public function ageRanges()
    {
        return $this->hasMany(AgeRange::class);
    }

    public function getImageAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }

    /**
     * Get the SMS balance for the salon.
     */
    public function smsBalance()
    {
        return $this->hasOne(SalonSmsBalance::class);
    }

    /**
     * Get the current SMS balance attribute for the salon.
     *
     * @return int
     */
    public function getCurrentSmsBalanceAttribute()
    {
        return $this->smsBalance->balance ?? 0;
    }

    /**
     * Get the SMS transactions for the salon.
     */
    public function smsTransactions()
    {
        return $this->hasMany(SmsTransaction::class);
    }

    /**
     * Get the notes for the salon.
     */
    public function notes()
    {
        return $this->hasMany(SalonNote::class);
    }

    /**
     * Get the last SMS purchase date for the salon.
     */
    public function getLastSmsPurchaseDateAttribute()
    {
        return $this->smsTransactions()->where('sms_type', 'purchase')->latest()->first()?->created_at;
    }

    /**
     * Get discount code usages for this salon.
     */
    public function discountCodeUsages()
    {
        return $this->hasMany(DiscountCodeSalonUsage::class);
    }

    /**
     * Check if salon has used a specific discount code.
     */
    public function hasUsedDiscountCode(string $discountCode): bool
    {
        return $this->discountCodeUsages()
            ->whereHas('discountCode', function ($query) use ($discountCode) {
                $query->where('code', $discountCode);
            })
            ->exists();
    }

    public function scopeWhereSearch($query, $search)
    {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', '%' . $search . '%')
                ->orWhere('mobile', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%')
                ->orWhereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('mobile', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                })
                ->orWhereHas('city', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                });
        });
    }

    /**
     * Get the SMS campaigns for the salon.
     */
    public function campaigns()
    {
        return $this->hasMany(SmsCampaign::class);
    }

    /**
     * Get the orders for the salon.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
