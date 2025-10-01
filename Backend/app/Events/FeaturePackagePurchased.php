<?php

namespace App\Events;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FeaturePackagePurchased
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $transaction;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, Transaction $transaction)
    {
        $this->order = $order;
        $this->transaction = $transaction;
    }
}
