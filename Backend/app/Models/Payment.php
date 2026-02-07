<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments_received';

    protected $fillable = [
        'salon_id',
        'customer_id',
        'appointment_id',
        'staff_id',
        'date',
        'amount',
        'description',
        'payment_method',
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

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function cashbox()
    {
        return $this->belongsTo(Cashbox::class);
    }

    public function cashboxTransaction()
    {
        return $this->hasOne(CashboxTransaction::class, 'payment_id');
    }

    /**
     * Get the services from the appointment.
     */
    public function services()
    {
        return $this->appointment ? $this->appointment->services() : collect([]);
    }
}
