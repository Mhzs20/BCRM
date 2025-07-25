<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessCategoryController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\SalonController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SalonSmsTemplateController;
use App\Http\Controllers\UserSmsBalanceController;
use App\Http\Controllers\HowIntroducedController;
use App\Http\Controllers\CustomerGroupController;
use App\Http\Controllers\ProfessionController;
use App\Http\Controllers\AgeRangeController;
use App\Http\Controllers\SmsPackageController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ZarinpalController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('check-user', [AuthController::class, 'checkUser'])->name('auth.check_user');
    Route::post('register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('verify', [AuthController::class, 'verifyOtp'])->name('auth.verify_otp');
    Route::post('complete-profile', [AuthController::class, 'completeProfile'])->middleware('auth:api')->name('auth.complete_profile');
    Route::post('login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('auth.forgot_password');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('auth.reset_password');
    Route::post('refresh', [AuthController::class, 'refreshToken'])->middleware('auth:api')->name('auth.refresh');
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api')->name('auth.logout');
    Route::get('me', [AuthController::class, 'me'])->middleware('auth:api')->name('auth.me');
    Route::put('me', [AuthController::class, 'updateProfile'])->middleware('auth:api')->name('auth.update');
    Route::post('change-password', [AuthController::class, 'changePassword'])->middleware('auth:api')->name('auth.change_password');
});

Route::prefix('general')->name('general.')->group(function() {
    Route::get('business-categories', [BusinessCategoryController::class, 'getCategories'])->name('business_categories.index');
    Route::get('business-categories/{category}/subcategories', [BusinessCategoryController::class, 'getSubcategories'])->name('business_categories.subcategories');
    Route::get('business-categories-with-subcategories', [BusinessCategoryController::class, 'getAllCategoriesWithSubcategories'])->name('business_categories.with_subcategories');

    Route::get('locations/provinces', [LocationController::class, 'getProvinces'])->name('locations.provinces');
    Route::get('locations/provinces/{province}/cities', [LocationController::class, 'getCities'])->name('locations.cities');
    Route::get('locations/provinces-with-cities', [LocationController::class, 'getAllProvincesWithCities'])->name('locations.provinces_with_cities');
});


Route::middleware('auth:api')->group(function () {
    Route::get('sms-balance', [SmsPackageController::class, 'getSmsBalance']);
    Route::get('sms-statistics', [SmsPackageController::class, 'getSmsStatistics']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    })->name('user.profile.default');


    Route::prefix('salons')->name('salons.')->group(function () {
        Route::get('/', [SalonController::class, 'getUserSalons'])->name('user_index');
        Route::post('/', [SalonController::class, 'createSalon'])->name('store');
        Route::get('/active', [SalonController::class, 'getActiveSalon'])->name('active');

        Route::prefix('{salon}')->whereNumber('salon')->scopeBindings()->group(function() {
            Route::get('/', [SalonController::class, 'getSalon'])->name('show');
            Route::put('/', [SalonController::class, 'updateSalon'])->name('update');
            Route::delete('/', [SalonController::class, 'deleteSalon'])->name('destroy');
            Route::post('/select-active', [SalonController::class, 'selectActiveSalon'])->name('select_active');

            Route::post('customers/bulk-delete', [CustomerController::class, 'bulkDelete'])->name('customers.bulkDelete');
            Route::get('customers/{customer}/appointments', [CustomerController::class, 'listCustomerAppointments'])->name('customers.appointments');
//            Route::get('customers/search', [CustomerController::class, 'search'])->name('customers.search');
            Route::post('customers/import/excel', [CustomerController::class, 'importExcel'])->name('customers.import.excel');
            Route::post('customers/import/contacts', [CustomerController::class, 'importContacts'])->name('customers.import.contacts');
            Route::apiResource('customers', CustomerController::class)->except(['create', 'edit']);

            Route::get('staff/booking-list', [StaffController::class, 'getBookingList'])->name('staff.bookingList');
            Route::apiResource('staff', StaffController::class)->except(['create', 'edit']);

            Route::get('services/booking-list', [ServiceController::class, 'getBookingList'])->name('services.bookingList');
            Route::apiResource('services', ServiceController::class)->except(['create', 'edit']);

            Route::get('appointments/available-slots', [AppointmentController::class, 'getAvailableSlots'])->name('appointments.availableSlots');
            Route::get('appointments/calendar', [AppointmentController::class, 'getCalendarAppointments'])->name('appointments.calendar');
            Route::get('appointments-by-month/{year}/{month}/{day}', [AppointmentController::class, 'getAppointmentsByMonthAndDay'])
                ->whereNumber('year')->whereNumber('month')->whereNumber('day');

            Route::get('appointments-by-month/{year}/{month}', [AppointmentController::class, 'getAppointmentsByMonth'])
                ->whereNumber('year')->whereNumber('month');

            Route::get('appointments', [AppointmentController::class, 'getAppointments']);

             Route::apiResource('appointments', AppointmentController::class)->except(['create', 'edit']);

            Route::apiResource('payments', PaymentController::class)->except(['create', 'edit']);
            Route::apiResource('how-introduced', HowIntroducedController::class)->except(['create', 'edit'])->names('howIntroduced');
            Route::apiResource('customer-groups', CustomerGroupController::class)->except(['create', 'edit'])->names('customerGroups');
            Route::apiResource('professions', ProfessionController::class)->except(['create', 'edit'])->names('professions');
            Route::apiResource('age-ranges', AgeRangeController::class)->except(['create', 'edit'])->names('ageRanges');

            Route::get('overview/stats', [DashboardController::class, 'getSalonStats'])->name('overview.stats');

            Route::prefix('settings')->name('settings.')->group(function () {
                Route::get('/', [SettingController::class, 'index'])->name('index');
                Route::post('/', [SettingController::class, 'store'])->name('store');
            });
        });
    });

    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('summary', [DashboardController::class, 'summary'])->name('summary');
        Route::get('all-salon-appointments', [DashboardController::class, 'allSalonAppointments'])->name('all_appointments');
        Route::get('recent-activities', [DashboardController::class, 'recentActivities'])->name('recent_activities');
        Route::get('sms-balance', [DashboardController::class, 'showSmsBalance'])->name('sms_balance.show');
    });

    Route::prefix('salon-settings')->name('salon_settings.')->group(function () {
        Route::get('sms-templates', [SalonSmsTemplateController::class, 'index'])->name('sms_templates.index');
        Route::post('sms-templates', [SalonSmsTemplateController::class, 'storeOrUpdate'])->name('sms_templates.store_or_update');
    });

    Route::prefix('sms-account')->name('sms_account.')->group(function () {
        Route::get('packages', [UserSmsBalanceController::class, 'getSmsPackages'])->name('packages.index');
        Route::post('purchase-package', [UserSmsBalanceController::class, 'purchasePackage'])->name('packages.purchase');
    });

});

Route::middleware(['auth:api', 'superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::apiResource('sms-packages', SmsPackageController::class);
    Route::apiResource('sms-templates', SalonSmsTemplateController::class);
});

Route::post('/payment/purchase/{packageId}', [ZarinpalController::class, 'purchase']);
Route::post('/payment/callback', [ZarinpalController::class, 'callback']);

Route::fallback(function(){
    return response()->json(['message' => 'مسیر API درخواستی یافت نشد یا متد HTTP مجاز نیست.'], 404);
});
