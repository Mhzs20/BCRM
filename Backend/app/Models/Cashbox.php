<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cashbox extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'salon_id',
        'name',
        'type',
        'initial_balance',
        'current_balance',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // انواع صندوق
    const TYPE_CASH = 'cash';           // نقدی
    const TYPE_POS = 'pos';             // دستگاه پوز
    const TYPE_BANK = 'bank_account';   // حساب بانکی
    const TYPE_ONLINE = 'online';       // درگاه آنلاین

    /**
     * Relationships
     */
    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    public function transactions()
    {
        return $this->hasMany(CashboxTransaction::class, 'cashbox_id');
    }

    public function paymentsReceived()
    {
        return $this->hasMany(Payment::class, 'cashbox_id');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'cashbox_id');
    }

    public function transfersFrom()
    {
        return $this->hasMany(CashboxTransaction::class, 'from_cashbox_id')
            ->where('type', CashboxTransaction::TYPE_TRANSFER);
    }

    public function transfersTo()
    {
        return $this->hasMany(CashboxTransaction::class, 'to_cashbox_id')
            ->where('type', CashboxTransaction::TYPE_TRANSFER);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForSalon($query, $salonId)
    {
        return $query->where('salon_id', $salonId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * محاسبه موجودی واقعی از روی تراکنش‌ها
     */
    public function calculateBalance(): float
    {
        $income = $this->transactions()
            ->where('type', CashboxTransaction::TYPE_INCOME)
            ->sum('amount');

        $expense = $this->transactions()
            ->where('type', CashboxTransaction::TYPE_EXPENSE)
            ->sum('amount');

        $transferIn = $this->transfersTo()->sum('amount');
        $transferOut = $this->transfersFrom()->sum('amount');

        return $this->initial_balance + $income - $expense + $transferIn - $transferOut;
    }

    /**
     * به‌روزرسانی موجودی فعلی
     */
    public function updateBalance(): void
    {
        $this->current_balance = $this->calculateBalance();
        $this->save();
    }

    /**
     * افزایش موجودی (دریافتی یا انتقال به)
     */
    public function increaseBalance(float $amount): void
    {
        $this->increment('current_balance', $amount);
    }

    /**
     * کاهش موجودی (پرداختی یا انتقال از)
     */
    public function decreaseBalance(float $amount): void
    {
        $this->decrement('current_balance', $amount);
    }

    /**
     * بررسی کافی بودن موجودی
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->current_balance >= $amount;
    }
}
