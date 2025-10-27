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
use App\Http\Controllers\PaymentGatewayController;
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
use App\Http\Controllers\RenewalReminderController;
use App\Http\Controllers\ServiceRenewalController;
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

    // Referral & Wallet APIs
    Route::prefix('referral')->name('referral.')->group(function () {
        Route::get('info', [\App\Http\Controllers\Api\ReferralController::class, 'getReferralInfo'])->name('info');
        Route::get('list', [\App\Http\Controllers\Api\ReferralController::class, 'getReferrals'])->name('list');
        Route::get('settings', [\App\Http\Controllers\Api\ReferralController::class, 'getSettings'])->name('settings');
    });

    Route::prefix('wallet')->name('wallet.')->group(function () {
        Route::get('info', [\App\Http\Controllers\Api\WalletController::class, 'getWalletInfo'])->name('info');
        Route::get('transactions', [\App\Http\Controllers\Api\WalletController::class, 'getTransactions'])->name('transactions');
        Route::get('packages', [\App\Http\Controllers\Api\WalletController::class, 'getAvailablePackages'])->name('packages');
        Route::post('add-credit', [\App\Http\Controllers\Api\WalletController::class, 'addCredit'])->name('add_credit');
        Route::post('purchase/package', [\App\Http\Controllers\Api\WalletController::class, 'purchasePackage'])->name('purchase.package');
        Route::post('purchase/sms-package', [\App\Http\Controllers\Api\WalletController::class, 'purchaseSmsPackage'])->name('purchase.sms_package');
        
        Route::post('purchase/feature-package', [\App\Http\Controllers\Api\WalletController::class, 'purchaseFeaturePackageWithWallet'])->name('purchase.feature_package');
        Route::post('charge', [\App\Http\Controllers\Api\WalletController::class, 'chargeWallet'])->name('charge');
        
        // Wallet charge packages
        Route::get('charge-packages', [\App\Http\Controllers\Api\WalletPackageController::class, 'index'])->name('charge_packages');
        Route::get('charge-packages/{package}', [\App\Http\Controllers\Api\WalletPackageController::class, 'show'])->name('charge_packages.show');
        Route::post('charge-packages/{package}/purchase', [\App\Http\Controllers\Api\WalletPackageController::class, 'purchase'])->name('charge_packages.purchase');
        Route::post('charge-packages/process-payment', [\App\Http\Controllers\Api\WalletPackageController::class, 'processPayment'])->name('charge_packages.process_payment');
        Route::get('charge/verify/{orderId}', [\App\Http\Controllers\Api\WalletController::class, 'verifyWalletCharge'])->name('charge.verify');
    });

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

            // Renewal Reminder Routes - Protected by Feature Package
            Route::prefix('renewal-reminders')->name('renewal_reminders.')
                ->middleware('feature:پیامک یادآوری ترمیم و تولد')
                ->group(function () {
                    Route::get('templates', [RenewalReminderController::class, 'getTemplates'])->name('templates');
                    Route::get('settings', [RenewalReminderController::class, 'getSettings'])->name('settings');
                    Route::put('settings', [RenewalReminderController::class, 'updateSettings'])->name('update_settings');
                    Route::post('preview-template', [RenewalReminderController::class, 'previewTemplate'])->name('preview_template');
                    
                    // Service-based renewal management
                    Route::get('services', [ServiceRenewalController::class, 'getServicesWithRenewalSettings'])->name('services');
                    Route::get('stats', [ServiceRenewalController::class, 'getRenewalStats'])->name('stats');
                    Route::post('global-toggle', [ServiceRenewalController::class, 'toggleGlobalReminder'])->name('global_toggle');
                    
                    Route::prefix('services/{service}')->group(function () {
                        Route::get('settings', [ServiceRenewalController::class, 'getServiceRenewalSetting'])->name('service_settings');
                        Route::put('settings', [ServiceRenewalController::class, 'updateServiceRenewalSetting'])->name('update_service_settings');
                        Route::post('toggle', [ServiceRenewalController::class, 'toggleServiceReminder'])->name('toggle_service');
                        Route::delete('settings', [ServiceRenewalController::class, 'deleteServiceRenewalSetting'])->name('delete_service_settings');
                    });
                });

            Route::get('staff/booking-list', [StaffController::class, 'getBookingList'])->name('staff.bookingList');
            Route::get('staff/search', [StaffController::class, 'search'])->name('staff.search');
            Route::apiResource('staff', StaffController::class)->except(['create', 'edit']);

            Route::get('services/booking-list', [ServiceController::class, 'getBookingList'])->name('services.bookingList');
            Route::get('services/search', [ServiceController::class, 'search'])->name('services.search');
            Route::apiResource('services', ServiceController::class)->except(['create', 'edit']);

            Route::get('appointments/available-slots', [AppointmentController::class, 'getAvailableSlots'])->name('appointments.availableSlots');

            // اندپوینت جدید paginated برای بازه‌های خالی سالن
            Route::get('appointments/available-slots/paginated', [AppointmentController::class, 'getAvailableSlotsPaginated'])->name('appointments.availableSlotsPaginated');
            Route::get('appointments/calendar', [AppointmentController::class, 'getCalendarAppointments'])->name('appointments.calendar');
            Route::post('appointments/prepare', [AppointmentController::class, 'prepareAppointment'])->name('appointments.prepare');
            Route::post('appointments/submit', [AppointmentController::class, 'submitAppointment'])->name('appointments.submit');
            Route::post('appointments/old', [AppointmentController::class, 'storeOldAppointment'])->name('appointments.old');
            Route::get('appointments/sms-templates', [\App\Http\Controllers\AppointmentSmsTemplateController::class, 'getAppointmentTemplates'])->name('appointments.sms_templates');
            Route::post('appointments/sms-templates/set-default', [\App\Http\Controllers\AppointmentSmsTemplateController::class, 'setDefaultTemplate'])->name('appointments.sms_templates.set_default');
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
                Route::get('{campaign}/pagination', [SmsCampaignController::class, 'getCampaignPagination'])->name('pagination');
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
        Route::get('gateway/{order}', [\App\Http\Controllers\PaymentGatewayController::class, 'redirect'])->name('gateway');
        Route::get('wallet/callback', [\App\Http\Controllers\PaymentGatewayController::class, 'walletCallback'])->name('wallet.callback');
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

    // Referral Management Routes
    Route::prefix('referral')->name('referral.')->group(function () {
        Route::get('users', [\App\Http\Controllers\Admin\ReferralManagementController::class, 'getUsers'])->name('users');
        Route::get('referrals', [\App\Http\Controllers\Admin\ReferralManagementController::class, 'getReferrals'])->name('referrals');
        Route::put('referrals/{referralId}/status', [\App\Http\Controllers\Admin\ReferralManagementController::class, 'updateReferralStatus'])->name('referrals.status');
        Route::get('wallet/transactions', [\App\Http\Controllers\Admin\ReferralManagementController::class, 'getWalletTransactions'])->name('wallet.transactions');
        Route::post('wallet/adjust', [\App\Http\Controllers\Admin\ReferralManagementController::class, 'adjustWalletBalance'])->name('wallet.adjust');
        Route::get('withdraw/requests', [\App\Http\Controllers\Admin\ReferralManagementController::class, 'getWithdrawRequests'])->name('withdraw.requests');
        Route::put('withdraw/requests/{requestId}', [\App\Http\Controllers\Admin\ReferralManagementController::class, 'processWithdrawRequest'])->name('withdraw.process');
        Route::get('statistics', [\App\Http\Controllers\Admin\ReferralManagementController::class, 'getStatistics'])->name('statistics');
    });

    // Referral Settings Routes
    Route::prefix('referral-settings')->name('referral_settings.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ReferralSettingsController::class, 'getSettings'])->name('index');
        Route::put('/', [\App\Http\Controllers\Admin\ReferralSettingsController::class, 'updateSettings'])->name('update');
        Route::get('history', [\App\Http\Controllers\Admin\ReferralSettingsController::class, 'getSettingsHistory'])->name('history');
        Route::post('toggle', [\App\Http\Controllers\Admin\ReferralSettingsController::class, 'toggleSystem'])->name('toggle');
    });
});

// Route for submitting manual SMS request (accessible by regular salon users)
// The salonId is now part of the URL for clarity and direct association.
Route::middleware('auth:api')->post('manual-sms/{salon}/send', [ManualSmsController::class, 'sendManualSms'])
    ->whereNumber('salon')
    ->name('manual_sms.send');

Route::get('/app-history', [AppController::class, 'latestHistory']);
Route::get('/staff/{staffId}/appointments', [AppController::class, 'getStaffAppointments'])->whereNumber('staffId');

// اندپوینت جدید paginated برای لیست نوبت‌های کارمند
Route::get('/staff/{staffId}/appointments/paginated', [AppController::class, 'getStaffAppointmentsPaginated'])->whereNumber('staffId');

// اندپوینت جدید paginated برای لیست نوبت‌های مشتری سالن
Route::get('/salons/{salon}/customers/{customer}/appointments/paginated', [CustomerController::class, 'listCustomerAppointmentsPaginated'])
    ->whereNumber('salon')
    ->whereNumber('customer');

Route::middleware('auth:api')->group(function () {
    Route::get('/salons/{salonId}/notifications', [NotificationController::class, 'index'])->whereNumber('salonId');
    Route::patch('/salons/{salonId}/notifications/{id}/read', [NotificationController::class, 'updateReadStatus'])->whereNumber('salonId');
});

Route::get('/banners', [ApiBannerController::class, 'index']);

Route::middleware('auth:api')->post('/appointments/{appointment}/send-satisfaction-survey', [SatisfactionController::class, 'sendSurvey'])->name('api.appointments.send-satisfaction-survey');

Route::fallback(function(){
    return response()->json(['message' => 'مسیر API درخواستی یافت نشد یا متد HTTP مجاز نیست.'], 404);
});
