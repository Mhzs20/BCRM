<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'status',
        'description',
        'transaction_id',
        'referral_id',
        'order_id',
        'admin_id',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:0',
        'balance_before' => 'decimal:0',
        'balance_after' => 'decimal:0',
        'metadata' => 'array',
    ];

    // Transaction Types
    const TYPE_REFERRAL_SIGNUP = 'referral_signup';
    const TYPE_REFERRAL_PURCHASE = 'referral_purchase';
    const TYPE_REFERRAL_REWARD = 'referral_reward';
    const TYPE_ORDER_REWARD = 'order_reward';
    const TYPE_MANUAL_CREDIT = 'manual_credit';
    const TYPE_MANUAL_DEBIT = 'manual_debit';
    const TYPE_ADMIN_CREDIT = 'admin_credit';
    const TYPE_ADMIN_DEBIT = 'admin_debit';
    const TYPE_WITHDRAWAL = 'withdrawal';
    const TYPE_WITHDRAW = 'withdraw';
    const TYPE_WITHDRAW_REFUND = 'withdraw_refund';
    const TYPE_PACKAGE_PURCHASE = 'package_purchase';
    const TYPE_SMS_PACKAGE_PURCHASE = 'sms_package_purchase';
    const TYPE_PURCHASE = 'purchase';
    const TYPE_WALLET_CHARGE = 'wallet_charge';

    // Transaction Status
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the user that owns the transaction
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who performed the transaction (if applicable)
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Get the referral associated with this transaction
     */
    public function referral()
    {
        return $this->belongsTo(UserReferral::class, 'referral_id');
    }

    /**
     * Get the order associated with this transaction
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Scope for credit transactions (positive amounts)
     */
    public function scopeCredits($query)
    {
        return $query->where('amount', '>', 0);
    }

    /**
     * Scope for debit transactions (negative amounts)
     */
    public function scopeDebits($query)
    {
        return $query->where('amount', '<', 0);
    }

    /**
     * Scope for completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Get type display name in Persian
     */
    public function getTypeDisplayAttribute()
    {
        $types = [
            self::TYPE_REFERRAL_SIGNUP => 'پاداش ثبت‌نام معرفی',
            self::TYPE_REFERRAL_PURCHASE => 'پاداش خرید معرفی',
            self::TYPE_REFERRAL_REWARD => 'پاداش رفرال',
            self::TYPE_ORDER_REWARD => 'پاداش خرید',
            self::TYPE_MANUAL_CREDIT => 'شارژ دستی',
            self::TYPE_MANUAL_DEBIT => 'کسر دستی',
            self::TYPE_ADMIN_CREDIT => 'واریز توسط ادمین',
            self::TYPE_ADMIN_DEBIT => 'برداشت توسط ادمین',
            self::TYPE_WITHDRAWAL => 'درخواست برداشت',
            self::TYPE_WITHDRAW => 'برداشت',
            self::TYPE_WITHDRAW_REFUND => 'بازگشت برداشت',
            self::TYPE_PACKAGE_PURCHASE => 'خرید پکیج امکانات',
            self::TYPE_SMS_PACKAGE_PURCHASE => 'خرید پکیج پیامک',
            self::TYPE_PURCHASE => 'خرید',
        ];

        return $types[$this->type] ?? $this->type;
    }

    /**
     * Get status display name in Persian
     */
    public function getStatusDisplayAttribute()
    {
        $statuses = [
            self::STATUS_PENDING => 'در انتظار',
            self::STATUS_COMPLETED => 'تکمیل شده',
            self::STATUS_FAILED => 'ناموفق',
            self::STATUS_CANCELLED => 'لغو شده',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Check if transaction is a credit (positive amount)
     */
    public function isCredit()
    {
        return $this->amount > 0;
    }

    /**
     * Check if transaction is a debit (negative amount)
     */
    public function isDebit()
    {
        return $this->amount < 0;
    }

    /**
     * Create a wallet transaction and update user balance
     */
    public static function createAndUpdateBalance($data)
    {
        DB::beginTransaction();
        
        try {
            $user = User::find($data['user_id']);
            if (!$user) {
                throw new \Exception('کاربر یافت نشد');
            }
            
            // Calculate balance before and after
            $balanceBefore = $user->wallet_balance;
            $balanceAfter = $balanceBefore + $data['amount'];
            
            // Add balance fields to data
            $data['balance_before'] = $balanceBefore;
            $data['balance_after'] = $balanceAfter;
            
            // Set default status if not provided
            if (!isset($data['status'])) {
                $data['status'] = self::STATUS_COMPLETED;
            }
            
            $transaction = self::create($data);
            
            if ($transaction->status === self::STATUS_COMPLETED) {
                $user->wallet_balance = $balanceAfter;
                $user->save();
            }
            
            DB::commit();
            return $transaction;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}