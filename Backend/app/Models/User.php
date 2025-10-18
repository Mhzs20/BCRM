<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Hekmatinasser\Verta\Verta;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;
    /**
     * Validate that the password contains only digits.
     *
     * @param string $password
     * @return bool
     */
    public static function isPasswordNumeric(string $password): bool
    {
        return preg_match('/^\d+$/', $password) === 1;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',
        'business_name',
        'business_category_id',
        'avatar',
        'otp_code',
        'otp_expires_at',
        'active_salon_id',
        'is_verified',
        'profile_completed',
        'gender',
        'date_of_birth',
        'last_login_at',
        'referral_code',
        'referrer_id',
        'wallet_balance',
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'otp_code',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = []; // Ensure no appended attributes

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'password' => 'hashed',
        'is_verified' => 'boolean',
        'is_superadmin' => 'boolean',
        'profile_completed' => 'boolean',
        'active_salon_id' => 'integer',
        'business_category_id' => 'integer',
        'last_login_at' => 'datetime',
        'referrer_id' => 'integer',
        'wallet_balance' => 'decimal:0',
        // Do NOT cast date_of_birth here, let the accessor handle it
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [
            'user_id' => $this->id,
            'name' => $this->name,
            'mobile' => $this->mobile,
            'active_salon_id' => $this->active_salon_id,
        ];
    }

    /**
     * Get the business category associated with the user.
     */
    public function businessCategory()
    {
        return $this->belongsTo(BusinessCategory::class, 'business_category_id');
    }

    /**
     * Get all salons created by this user.
     */
    public function salons()
    {
        return $this->hasMany(Salon::class, 'user_id');
    }

    /**
     * Get the business subcategories associated with the user.
     */
    public function businessSubcategories()
    {
        return $this->belongsToMany(BusinessSubcategory::class, 'user_business_subcategory', 'user_id', 'business_subcategory_id');
    }

    /**
     * Get the currently active salon for the user.
     */
    public function activeSalon()
    {
        return $this->belongsTo(Salon::class, 'active_salon_id');
    }

    public function salon()
    {
        return $this->hasOne(Salon::class, 'user_id');
    }

    /**
     * Get the user's purchased packages
     */
    public function userPackages()
    {
        return $this->hasMany(UserPackage::class);
    }

    /**
     * Get the user's active package
     */
    public function activePackage()
    {
        return $this->hasOne(UserPackage::class)->where('status', 'active');
    }

    /**
     * Get the user who referred this user
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * Get all users referred by this user
     */
    public function referrals()
    {
        return $this->hasMany(UserReferral::class, 'referrer_id');
    }

    /**
     * Get all users referred by this user
     */
    public function referredUsers()
    {
        return $this->hasMany(User::class, 'referrer_id');
    }

    /**
     * Get the referral record for this user (as referred)
     */
    public function referralRecord()
    {
        return $this->hasOne(UserReferral::class, 'referred_id');
    }

    /**
     * Get wallet transactions for this user
     */
    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get withdraw requests for this user
     */
    public function withdrawRequests()
    {
        return $this->hasMany(WithdrawRequest::class)->orderBy('created_at', 'desc');
    }




    /**
     * Helper method to check if the user has a specific role.
     */
    public function hasRole(string $roleName): bool
    {
        if ($roleName === 'salon_owner' && $this->activeSalon()->exists()) {
            return true;
        }
        if ($roleName === 'admin' && $this->email === 'admin@example.com') {
            return true;
        }
        return false;
    }

    /**
     * Get the user's date of birth in Jalali format.
     *
     * @param  string|null  $value
     * @return string|null
     */
    public function getDateOfBirthAttribute(?string $value): ?string
    {
        if ($value) {
            try {
                // Parse the Gregorian date from DB and format it as Jalali
                return Verta::parse($value)->format('Y-m-d');
            } catch (\Exception $e) {
                Log::warning("User model: Could not convert date_of_birth '{$value}' to Jalali: " . $e->getMessage());
                return null; // Return null if conversion fails
            }
        }
        return null;
    }

    /**
     * Get the business_subcategory_ids attribute.
     *
     * @param  string|null  $value
     * @return array
     */

    /**
     * Generate a unique referral code for the user
     */
    public function generateReferralCode()
    {
        do {
            $code = 'REF' . strtoupper(substr(md5($this->id . $this->mobile . time()), 0, 8));
        } while (User::where('referral_code', $code)->exists());
        
        $this->referral_code = $code;
        $this->save();
        
        return $code;
    }

    /**
     * Get the referral code for this user
     */
    public function getReferralCode()
    {
        if (!$this->referral_code) {
            $this->generateReferralCode();
        }
        
        return $this->referral_code;
    }

    /**
     * Get referral statistics for this user
     */
    public function getReferralStats()
    {
        $totalReferrals = $this->referrals()->count();
        $successfulReferrals = $this->referrals()
            ->whereIn('status', [UserReferral::STATUS_REGISTERED, UserReferral::STATUS_PURCHASED, UserReferral::STATUS_REWARDED])
            ->count();
        $thisMonthReferrals = $this->referrals()
            ->thisMonthSuccessful($this->id)
            ->count();
        $totalEarnings = $this->referrals()->sum('total_reward_amount');
        
        return [
            'total_referrals' => $totalReferrals,
            'successful_referrals' => $successfulReferrals,
            'this_month_referrals' => $thisMonthReferrals,
            'total_earnings' => $totalEarnings,
            'wallet_balance' => $this->wallet_balance,
        ];
    }

    /**
     * Check if user can refer more users this month
     */
    public function canReferMore()
    {
        $settings = ReferralSetting::getActiveSettings();
        if (!$settings->is_active) {
            return false;
        }
        
        $thisMonthCount = $this->referrals()->thisMonthSuccessful($this->id)->count();
        return $thisMonthCount < $settings->monthly_referral_limit;
    }

    /**
     * Process a purchase for referral rewards
     */
    public function processPurchaseForReferral($amount)
    {
        if ($this->referrer_id) {
            $referral = UserReferral::where('referrer_id', $this->referrer_id)
                ->where('referred_id', $this->id)
                ->first();
                
            if ($referral && $referral->canReceivePurchaseReward()) {
                $referral->updateStatus(UserReferral::STATUS_PURCHASED, $amount);
            }
        }
    }

    /**
     * Update wallet balance
     */
    public function updateWalletBalance($amount, $description, $type = WalletTransaction::TYPE_ADMIN_CREDIT, $metadata = [])
    {
        $transaction = WalletTransaction::createAndUpdateBalance([
            'user_id' => $this->id,
            'type' => $type,
            'amount' => $amount,
            'status' => WalletTransaction::STATUS_COMPLETED,
            'description' => $description,
            'metadata' => $metadata,
        ]);

        return $transaction;
    }

    /**
     * Check if user has sufficient wallet balance for a purchase
     */
    public function hasSufficientBalance($amount)
    {
        return $this->wallet_balance >= $amount;
    }

    /**
     * Deduct amount from wallet for purchase
     */
    public function deductFromWallet($amount, $description, $type = WalletTransaction::TYPE_PACKAGE_PURCHASE, $orderId = null)
    {
        if (!$this->hasSufficientBalance($amount)) {
            throw new \Exception('موجودی کیف پول کافی نیست.');
        }

        $transaction = WalletTransaction::createAndUpdateBalance([
            'user_id' => $this->id,
            'type' => $type,
            'amount' => -$amount, // Negative for deduction
            'status' => WalletTransaction::STATUS_COMPLETED,
            'description' => $description,
            'order_id' => $orderId,
        ]);

        return $transaction;
    }
}
