<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Events\PaymentSuccessful;
use App\Listeners\UpdateOrderAndBalance;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        PaymentSuccessful::class => [
            UpdateOrderAndBalance::class,
        ],
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
