<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;
use App\Models\Appointment;
use App\Observers\AppointmentObserver;
use App\Models\Salon;
use App\Observers\SalonObserver;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
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
        Paginator::useTailwind(); // Use Tailwind CSS for pagination links

        Appointment::observe(AppointmentObserver::class);
        Salon::observe(SalonObserver::class);

        Schema::defaultStringLength(191);

        /**
         * FIX: Renamed 'j_date_format' to 'jdate_format' to match usage in request files.
         * FIX: Corrected the validation logic to properly use the format parameter.
         *
         * Custom validator for Jalali date format.
         * Usage: 'birth_date' => 'jdate_format:Y/m/d'
         */
        Validator::extend('jdate_format', function ($attribute, $value, $parameters, $validator) {
            // The rule requires the value to be a string.
            if (!is_string($value)) {
                return false;
            }

            // The format is expected as the first parameter (e.g., 'Y/m/d').
            $format = $parameters[0] ?? 'Y/m/d';

            try {
                // We directly try to create a date object from the given value and format.
                // If the value does not match the format, it will throw an exception.
                Jalalian::fromFormat($format, $value);
                return true;
            } catch (\Exception $e) {
                // If an exception is caught, it means the validation failed.
                return false;
            }
        });

        // Optional: Custom error message for the jdate_format rule.
        Validator::replacer('jdate_format', function ($message, $attribute, $rule, $parameters) {
            $format = $parameters[0] ?? 'Y/m/d';
            // Provide a user-friendly error message.
            return "فرمت تاریخ وارد شده برای فیلد :attribute صحیح نیست. لطفا از فرمت {$format} استفاده کنید.";
        });


        // =============================================================
        // Your other custom validation rule is kept as is.
        // =============================================================

        Validator::extend('j_after_or_equal', function ($attribute, $value, $parameters, $validator) {
            try {
                $inputCarbonDate = Jalalian::fromFormat('Y-m-d', str_replace('/', '-', $value))->toCarbon();
                $todayCarbon = Carbon::today();
                return $inputCarbonDate->gte($todayCarbon);
            } catch (\Exception $e) {
                return false;
            }
        });

        Validator::replacer('j_after_or_equal', function ($message, $attribute, $rule, $parameters) {
            return 'تاریخ نوبت نمی‌تواند در گذشته باشد.';
        });
    }
}
