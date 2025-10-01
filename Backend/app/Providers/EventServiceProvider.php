<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Events\PaymentSuccessful;
use App\Events\FeaturePackagePurchased;
use App\Listeners\UpdateOrderAndBalance;
use App\Listeners\ActivateFeaturePackage;

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
        FeaturePackagePurchased::class => [
            ActivateFeaturePackage::class,
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
