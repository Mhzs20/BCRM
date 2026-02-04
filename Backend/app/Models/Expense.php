<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'salon_id',
        'date',
        'description',
        'amount',
        'category',
        'expense_type',
        'staff_id',
        'related_payment_id',
        'cashbox_id',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function relatedPayment()
    {
        return $this->belongsTo(Payment::class, 'related_payment_id');
    }

    public function cashbox()
    {
        return $this->belongsTo(Cashbox::class);
    }

    public function cashboxTransaction()
    {
        return $this->hasOne(CashboxTransaction::class, 'expense_id');
    }
}
