<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'salon_id',
        'sms_package_id',
        'customer_id',
        'appointment_id',
        'receptor',
        'sms_type',
        'content',
        'original_content',
        'edited_content',
        'sent_at',
        'status',
        'external_response',
        'amount',
        'transaction_id',
        'approval_status',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'batch_id',
        'recipients_type',
        'recipients_count',
        'sms_parts',
        'balance_deducted_at_submission',
        'sms_count',
        'description',
        'reference_id',
        'type',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

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

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function smsPackage()
    {
        return $this->belongsTo(SmsPackage::class);
    }
}
