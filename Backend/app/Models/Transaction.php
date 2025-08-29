<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'order_id',
        'gateway',
        'amount',
        'transaction_id',
        'reference_id',
        'status',
        'description',
    ];

    /**
     * Get the order that the transaction belongs to.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
