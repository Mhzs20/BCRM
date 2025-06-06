<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Salon;
use App\Policies\SalonPolicy;
use App\Models\Appointment;
use App\Policies\AppointmentPolicy;
use App\Models\Customer;
use App\Policies\CustomerPolicy;
use App\Models\Staff;
use App\Policies\StaffPolicy;
use App\Models\Service;
use App\Policies\ServicePolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Salon::class => SalonPolicy::class,
        Appointment::class => AppointmentPolicy::class,
        Customer::class => CustomerPolicy::class,
        Staff::class => StaffPolicy::class,
        Service::class => ServicePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

    }
}
