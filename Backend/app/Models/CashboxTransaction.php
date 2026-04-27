<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Morilog\Jalali\Jalalian;

class CashboxTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'salon_id',
        'type',
        'cashbox_id',
        'from_cashbox_id',
        'to_cashbox_id',
        'amount',
        'description',
        'category_id',
        'subcategory_id',
        'category',
        'subcategory',
        'payment_id',
        'expense_id',
        'commission_transaction_id',
        'transaction_date',
        'transaction_time',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    // انواع تراکنش
    const TYPE_INCOME = 'income';       // دریافتی
    const TYPE_EXPENSE = 'expense';     // پرداختی
    const TYPE_TRANSFER = 'transfer';   // انتقال موجودی

    /**
     * Relationships
     */
    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    public function cashbox()
    {
        return $this->belongsTo(Cashbox::class);
    }

    public function fromCashbox()
    {
        return $this->belongsTo(Cashbox::class, 'from_cashbox_id');
    }

    public function toCashbox()
    {
        return $this->belongsTo(Cashbox::class, 'to_cashbox_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function commissionTransaction()
    {
        return $this->belongsTo(StaffCommissionTransaction::class, 'commission_transaction_id');
    }

    public function transactionCategory()
    {
        return $this->belongsTo(TransactionCategory::class, 'category_id');
    }

    public function transactionSubcategory()
    {
        return $this->belongsTo(TransactionSubcategory::class, 'subcategory_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scopes
     */
    public function scopeIncome($query)
    {
        return $query->where('type', self::TYPE_INCOME);
    }

    public function scopeExpense($query)
    {
        return $query->where('type', self::TYPE_EXPENSE);
    }

    public function scopeTransfer($query)
    {
        return $query->where('type', self::TYPE_TRANSFER);
    }

    public function scopeForCashbox($query, $cashboxId)
    {
        return $query->where(function ($q) use ($cashboxId) {
            $q->where('cashbox_id', $cashboxId)
              ->orWhere('from_cashbox_id', $cashboxId)
              ->orWhere('to_cashbox_id', $cashboxId);
        });
    }

    public function scopeForSalon($query, $salonId)
    {
        return $query->where('salon_id', $salonId);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Accessors - تاریخ شمسی
     */
    public function getJalaliTransactionDateAttribute(): ?string
    {
        if (!$this->transaction_date) {
            return null;
        }
        return Jalalian::fromDateTime($this->transaction_date)->format('Y/m/d');
    }

    public function getJalaliCreatedAtAttribute(): ?string
    {
        if (!$this->created_at) {
            return null;
        }
        return Jalalian::fromDateTime($this->created_at)->format('Y/m/d H:i:s');
    }

    /**
     * متد‌های کمکی
     */
    public function isIncome(): bool
    {
        return $this->type === self::TYPE_INCOME;
    }

    public function isExpense(): bool
    {
        return $this->type === self::TYPE_EXPENSE;
    }

    public function isTransfer(): bool
    {
        return $this->type === self::TYPE_TRANSFER;
    }

    /**
     * دریافت صندوق مرتبط بر اساس نوع تراکنش
     */
    public function getRelatedCashbox()
    {
        if ($this->isTransfer()) {
            return [
                'from' => $this->fromCashbox,
                'to' => $this->toCashbox
            ];
        }
        return $this->cashbox;
    }
}
