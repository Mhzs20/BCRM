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
use App\Http\Controllers\AppointmentReportController;
use App\Http\Controllers\ManualSmsController;
use App\Http\Controllers\SmsTransactionController;
use App\Http\Controllers\SmsCampaignController;
use App\Http\Controllers\Api\AppController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\BannerController as ApiBannerController;
use App\Http\Controllers\SatisfactionController;
use App\Http\Controllers\ContactPickerController;
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

    // New routes for cascading dropdowns
    Route::get('provinces/{provinceId}/cities', [AppController::class, 'getCitiesByProvince'])->name('provinces.cities');
    Route::get('business-categories/{categoryId}/subcategories', [AppController::class, 'getSubcategoriesByCategory'])->name('business_categories.subcategories_by_category');
});


Route::middleware('auth:api')->group(function () {
    Route::get('sms-balance', [SmsPackageController::class, 'getSmsBalance']);
    Route::get('sms-statistics', [SmsPackageController::class, 'getSmsStatistics']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    })->name('user.profile.default');

    // کارت
    Route::get('card-info', [\App\Http\Controllers\Api\CardSettingController::class, 'showCardInfo']);

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
            Route::post('customers/import/excel', [DashboardController::class, 'importCustomers'])->name('customers.import.excel');
            Route::post('customers/import/contacts', [CustomerController::class, 'importContacts'])->name('customers.import.contacts');
            Route::apiResource('customers', CustomerController::class)->except(['create', 'edit']);

            // Contact API Picker Routes
            Route::prefix('contact-picker')->name('contact_picker.')->group(function() {
                Route::get('available-contacts', [ContactPickerController::class, 'getAvailableContacts'])->name('available_contacts');
                Route::post('pick-and-import', [ContactPickerController::class, 'pickAndImportContacts'])->name('pick_and_import');
                Route::post('validate', [ContactPickerController::class, 'validateContacts'])->name('validate');
                Route::post('bulk-select-phone', [ContactPickerController::class, 'bulkSelectByPhoneNumbers'])->name('bulk_select_phone');
                Route::get('import-history', [ContactPickerController::class, 'getImportHistory'])->name('import_history');
            });

            Route::get('staff/booking-list', [StaffController::class, 'getBookingList'])->name('staff.bookingList');
            Route::get('staff/search', [StaffController::class, 'search'])->name('staff.search');
            Route::apiResource('staff', StaffController::class)->except(['create', 'edit']);

            Route::get('services/booking-list', [ServiceController::class, 'getBookingList'])->name('services.bookingList');
            Route::get('services/search', [ServiceController::class, 'search'])->name('services.search');
            Route::apiResource('services', ServiceController::class)->except(['create', 'edit']);

            Route::get('appointments/available-slots', [AppointmentController::class, 'getAvailableSlots'])->name('appointments.availableSlots');
            Route::get('appointments/calendar', [AppointmentController::class, 'getCalendarAppointments'])->name('appointments.calendar');
            Route::get('appointments-by-month/{year}/{month}/{day}', [AppointmentController::class, 'getAppointmentsByMonthAndDay'])
                ->whereNumber('year')->whereNumber('month')->whereNumber('day');

            Route::get('appointments-by-month/{year}/{month}', [AppointmentController::class, 'getAppointmentsByMonth'])
                ->whereNumber('year')->whereNumber('month');

            Route::get('appointments', [AppointmentController::class, 'getAppointments']);

            Route::post('appointments/{appointment}/send-reminder', [AppointmentController::class, 'sendReminderSms'])->name('appointments.send_reminder');
            Route::post('appointments/{appointment}/send-modification-sms', [AppointmentController::class, 'sendModificationSms'])->name('appointments.send_modification_sms');

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

            Route::prefix('reports/appointments')->name('reports.appointments.')->group(function () {
                Route::get('time-based', [AppointmentReportController::class, 'getAppointmentTimeReports'])->name('time_based');
                Route::get('overall-status', [AppointmentReportController::class, 'getOverallAppointmentStatusReports'])->name('overall_status');
                Route::get('analytical', [AppointmentReportController::class, 'getAnalyticalReports'])->name('analytical');
                Route::get('detailed', [AppointmentReportController::class, 'getDetailedReports'])->name('detailed');
                Route::get('daily-summary', [AppointmentReportController::class, 'getDailySummaryReport'])->name('daily_summary');
                Route::get('daily-list', [AppointmentReportController::class, 'getDailyAppointmentsList'])->name('daily_list');
            });

            Route::prefix('sms-account')->name('sms_account.')->group(function () {
                Route::get('packages', [SmsPackageController::class, 'index'])->name('packages.index');
                Route::post('purchase-package', [UserSmsBalanceController::class, 'purchasePackage'])->name('packages.purchase');
                Route::get('transactions', [SmsTransactionController::class, 'index'])->name('transactions.index');
                Route::get('financial-transactions', [SmsTransactionController::class, 'financialTransactions'])->name('financial_transactions.index');
                Route::get('sent-messages', [SmsTransactionController::class, 'salonSentMessages'])->name('sent_messages.index');
            });

            Route::prefix('sms-campaign')->name('sms_campaign.')->group(function () {
                Route::post('prepare', [SmsCampaignController::class, 'prepareCampaign'])->name('prepare');
                Route::post('{campaign}/send', [SmsCampaignController::class, 'sendCampaign'])->name('send');
                Route::get('{campaign}/status', [SmsCampaignController::class, 'getCampaignStatus'])->name('status');
            });

            // Feature Packages API
            Route::prefix('feature-packages')->name('feature_packages.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\PackageController::class, 'index'])->name('index');
                Route::get('/active', [\App\Http\Controllers\Api\PackageController::class, 'myPackage'])->name('active');
                Route::get('/history', [\App\Http\Controllers\Api\PackageController::class, 'myPackages'])->name('history');
                Route::get('/{id}', [\App\Http\Controllers\Api\PackageController::class, 'show'])->name('show');
                Route::post('/{id}/purchase', [\App\Http\Controllers\Api\PackageController::class, 'purchase'])->name('purchase');
                Route::post('/verify', [\App\Http\Controllers\Api\PackageController::class, 'verify'])->name('verify');
            });
        });
    });

    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('summary', [DashboardController::class, 'summary'])->name('summary');
        Route::get('all-salon-appointments', [DashboardController::class, 'allSalonAppointments'])->name('all_appointments');
        Route::get('recent-activities', [DashboardController::class, 'recentActivities'])->name('recent_activities');
        Route::get('sms-balance', [AppController::class, 'getSmsBalance'])->name('sms_balance.show');
        Route::post('{salon}/import-customers', [DashboardController::class, 'importCustomers'])->name('import_customers');
    });

    Route::prefix('salon-settings')->name('salon_settings.')->group(function () {
        Route::get('sms-templates', [SalonSmsTemplateController::class, 'index'])->name('sms_templates.index');
        Route::post('sms-templates', [SalonSmsTemplateController::class, 'storeOrUpdate'])->name('sms_templates.store_or_update');
    // Custom categories
    Route::post('sms-template-categories', [SalonSmsTemplateController::class, 'createCategory'])->name('sms_template_categories.create');
    Route::put('sms-template-categories/{category}', [SalonSmsTemplateController::class, 'updateCategory'])->name('sms_template_categories.update');
    Route::delete('sms-template-categories/{category}', [SalonSmsTemplateController::class, 'deleteCategory'])->name('sms_template_categories.delete');
    // Custom templates
    Route::post('custom-sms-templates', [SalonSmsTemplateController::class, 'createCustomTemplate'])->name('custom_sms_templates.create');
    Route::put('custom-sms-templates/{template}', [SalonSmsTemplateController::class, 'updateCustomTemplate'])->name('custom_sms_templates.update');
    Route::delete('custom-sms-templates/{template}', [SalonSmsTemplateController::class, 'deleteCustomTemplate'])->name('custom_sms_templates.delete');
    });

    Route::prefix('payment')->name('payment.')->middleware('throttle:10,1')->group(function () {
        Route::post('purchase', [ZarinpalController::class, 'purchase'])->name('purchase');
        Route::post('verify', [ZarinpalController::class, 'verify'])->name('verify');
    });

    Route::prefix('discount')->name('discount.')->group(function () {
        Route::post('validate', [\App\Http\Controllers\DiscountCodeController::class, 'validateCode'])->name('validate');
        Route::post('apply', [\App\Http\Controllers\DiscountCodeController::class, 'applyCode'])->name('apply');
        Route::get('statistics', [\App\Http\Controllers\DiscountCodeController::class, 'getStatistics'])->name('statistics');
    });
});

Route::middleware(['auth:api', 'superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::apiResource('sms-packages', SmsPackageController::class);
    Route::apiResource('sms-templates', SalonSmsTemplateController::class);

    // Manual SMS Approval Routes
    Route::prefix('manual-sms')->name('manual_sms.')->group(function () {
        Route::get('pending', [ManualSmsController::class, 'listPendingManualSms'])->name('pending');
        Route::post('{smsTransactionId}/approve', [ManualSmsController::class, 'approveManualSms'])->name('approve');
        Route::post('{smsTransactionId}/reject', [ManualSmsController::class, 'rejectManualSms'])->name('reject');
    });
});

// Route for submitting manual SMS request (accessible by regular salon users)
// The salonId is now part of the URL for clarity and direct association.
Route::middleware('auth:api')->post('manual-sms/{salon}/send', [ManualSmsController::class, 'sendManualSms'])
    ->whereNumber('salon')
    ->name('manual_sms.send');

Route::get('/app-history', [AppController::class, 'latestHistory']);
Route::get('/staff/{staffId}/appointments', [AppController::class, 'getStaffAppointments'])->whereNumber('staffId');

Route::middleware('auth:api')->group(function () {
    Route::get('/salons/{salonId}/notifications', [NotificationController::class, 'index'])->whereNumber('salonId');
    Route::patch('/salons/{salonId}/notifications/{id}/read', [NotificationController::class, 'updateReadStatus'])->whereNumber('salonId');
});

Route::get('/banners', [ApiBannerController::class, 'index']);

Route::middleware('auth:api')->post('/appointments/{appointment}/send-satisfaction-survey', [SatisfactionController::class, 'sendSurvey'])->name('api.appointments.send-satisfaction-survey');

Route::fallback(function(){
    return response()->json(['message' => 'مسیر API درخواستی یافت نشد یا متد HTTP مجاز نیست.'], 404);
});
