<?php // این خط اول فایل است

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Validator::extend('j_date_format', function ($attribute, $value, $parameters, $validator) {
            try {
                $format = $parameters[0] ?? 'Y-m-d';
                $normalizedValue = str_replace('/', '-', $value);
                Jalalian::fromFormat($format, $normalizedValue);
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }, 'فرمت تاریخ شمسی صحیح نیست (مثال: 1404-03-18).');


        Validator::extend('j_after_or_equal', function ($attribute, $value, $parameters, $validator) {
            try {
                $inputCarbonDate = Jalalian::fromFormat('Y-m-d', str_replace('/', '-', $value))->toCarbon();

                $todayCarbon = Carbon::today();

                return $inputCarbonDate->gte($todayCarbon);

            } catch (\Exception $e) {
                return false;
            }
        });
        // =============================================================

        Validator::replacer('j_after_or_equal', function ($message, $attribute, $rule, $parameters) {
            return 'تاریخ نوبت نمی‌تواند در گذشته باشد.';
        });
    }
}
