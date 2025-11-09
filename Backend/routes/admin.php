<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SmsPackageController;
use App\Http\Controllers\Admin\SmsTemplateController;
use App\Http\Controllers\Admin\SmsTemplateCategoryController;
use App\Http\Controllers\Admin\AdminSmsSettingController;
use App\Http\Controllers\Admin\AppUpdateController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\HowIntroducedController; 
use App\Http\Controllers\Admin\ProfessionController; 
use App\Http\Controllers\Admin\CustomerGroupController;
use App\Http\Controllers\Admin\FileController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\AdminSalonController;
use App\Http\Controllers\Admin\AdminBulkSmsGiftController;  
use App\Http\Controllers\Admin\AdminBulkSmsController;  
use App\Http\Controllers\Admin\AdminAppointmentController;  
use App\Http\Controllers\Admin\AdminTransactionController;
use App\Http\Controllers\Admin\ReferralManagementController;
use App\Http\Controllers\Admin\ReferralSettingsController;
use App\Http\Controllers\Admin\WalletManagementController;
use App\Http\Controllers\ManualSmsController;
use App\Http\Controllers\SmsCampaignController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:web', SuperAdminMiddleware::class])->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('sms-packages', SmsPackageController::class);
    Route::get('manual-sms-approval', [ManualSmsController::class, 'showApprovalPage'])->name('manual_sms.approval');
    Route::get('manual-sms-reports', [ManualSmsController::class, 'showReportsPage'])->name('manual_sms.reports');
    Route::post('manual-sms-approval/{batchId}/update-content', [ManualSmsController::class, 'updateManualSmsContent'])->name('manual_sms.update_content');
    Route::post('manual-sms-approval/{batchId}/approve', [ManualSmsController::class, 'approveManualSms'])->name('manual_sms.approve');
    Route::post('manual-sms-approval/{batchId}/reject', [ManualSmsController::class, 'rejectManualSms'])->name('manual_sms.reject');

        // SMS Campaign Approval Routes
    Route::prefix('sms-campaign-approval')->group(function () {
        Route::get('/', [SmsCampaignController::class, 'index'])->name('sms_campaign_approval.index');
        Route::get('/pending', [SmsCampaignController::class, 'getPendingCampaigns'])->name('sms_campaign_approval.pending');
        Route::post('/{campaign}/approve', [SmsCampaignController::class, 'approveCampaign'])->name('sms_campaign_approval.approve');
        Route::post('/{campaign}/reject', [SmsCampaignController::class, 'rejectCampaign'])->name('sms_campaign_approval.reject');
        Route::put('/{campaign}/update-content', [SmsCampaignController::class, 'updateContent'])->name('sms_campaign_approval.update-content');
    });

    // SMS Campaign Reports
    Route::get('sms-campaign-reports', [SmsCampaignController::class, 'reports'])->name('sms-campaign-reports.index');
    Route::post('sms-templates/system-update', [SmsTemplateController::class, 'systemUpdate'])->name('sms-templates.system-update');
    Route::resource('sms-templates', SmsTemplateController::class)->except(['show']);
    Route::post('sms-template-categories', [SmsTemplateCategoryController::class, 'store'])->name('sms-template-categories.store');
    Route::delete('sms-template-categories/{smsTemplateCategory}', [SmsTemplateCategoryController::class, 'destroy'])->name('sms-template-categories.destroy');

    // SMS Settings Routes
    Route::get('sms-settings', [AdminSmsSettingController::class, 'index'])->name('sms_settings.index');
    Route::post('sms-settings', [AdminSmsSettingController::class, 'update'])->name('sms_settings.update');

    // App Updates
    Route::resource('app-updates', AppUpdateController::class);

    // Notifications
    Route::resource('notifications', NotificationController::class);

    // How Introduced
    Route::resource('how-introduced', HowIntroducedController::class);

    // Professions
    Route::resource('professions', ProfessionController::class);

    // Customer Groups
    Route::resource('customer-groups', CustomerGroupController::class);

    // Files
    Route::resource('files', FileController::class);

    // Banners
    Route::resource('banners', BannerController::class);

    // Salons
    Route::resource('salons', AdminSalonController::class);
    Route::post('salons/{salon}/toggle-status', [AdminSalonController::class, 'toggleStatus'])->name('salons.toggle-status');
    Route::post('salons/{salon}/reset-password', [AdminSalonController::class, 'resetPassword'])->name('salons.reset-password');
    Route::get('salons/{salon}/purchase-history', [AdminSalonController::class, 'purchaseHistory'])->name('salons.purchase-history');
    Route::post('salons/{salon}/notes', [AdminSalonController::class, 'storeNote'])->name('salons.store-note');
    Route::post('salons/{salon}/add-sms-credit', [AdminSalonController::class, 'addSmsCredit'])->name('salons.add-sms-credit');
    Route::post('salons/{salon}/reduce-sms-credit', [AdminSalonController::class, 'reduceSmsCredit'])->name('salons.reduce-sms-credit');
    Route::get('salons/{salon}/active-discount-codes', [AdminSalonController::class, 'getActiveDiscountCodes'])->name('salons.active-discount-codes');
    
    // Feature Packages Management
    Route::get('salons/{salon}/feature-packages', [AdminSalonController::class, 'getFeaturePackages'])->name('salons.feature-packages');
    Route::post('salons/{salon}/feature-packages/activate', [AdminSalonController::class, 'activateFeaturePackage'])->name('salons.activate-feature-package');
    Route::post('salons/{salon}/feature-packages/deactivate', [AdminSalonController::class, 'deactivateFeaturePackage'])->name('salons.deactivate-feature-package');

    // Bulk SMS Gift
    Route::get('bulk-sms-gift', [AdminBulkSmsGiftController::class, 'index'])->name('bulk-sms-gift.index');
    Route::post('bulk-sms-gift', [AdminBulkSmsGiftController::class, 'sendGift'])->name('bulk-sms-gift.send');
    Route::post('bulk-sms-gift/activate-package', [AdminBulkSmsGiftController::class, 'bulkActivatePackage'])->name('bulk-sms-gift.activate-package');
    Route::get('bulk-sms-gift/history', [AdminBulkSmsGiftController::class, 'giftHistory'])->name('bulk-sms-gift.history');

    // Bulk SMS
    Route::get('bulk-sms', [AdminBulkSmsController::class, 'index'])->name('bulk-sms.index');
    Route::post('bulk-sms', [AdminBulkSmsController::class, 'sendSms'])->name('bulk-sms.send');
    Route::get('bulk-sms/history', [AdminBulkSmsController::class, 'history'])->name('bulk-sms.history');

    // Discount Codes
    Route::resource('discount-codes', \App\Http\Controllers\Admin\DiscountCodeController::class);
    Route::post('discount-codes/preview-target-users', [\App\Http\Controllers\Admin\DiscountCodeController::class, 'previewTargetUsers'])->name('discount-codes.preview-target-users');
    Route::get('discount-codes/{discountCode}/target-users', [\App\Http\Controllers\Admin\DiscountCodeController::class, 'showTargetUsers'])->name('discount-codes.target-users');

    // Transactions (Payment) List
    Route::get('transactions', [AdminTransactionController::class, 'index'])->name('transactions.index');
    Route::put('transactions/orders/{order}/status', [AdminTransactionController::class, 'updateOrderStatus'])->name('transactions.orders.update-status');
    Route::put('transactions/transactions/{transaction}/status', [AdminTransactionController::class, 'updateTransactionStatus'])->name('transactions.transactions.update-status');

    // Appointments
    Route::get('appointments', [AdminAppointmentController::class, 'index'])->name('appointments.index');
    Route::get('appointments/{appointment}', [AdminAppointmentController::class, 'show'])->name('appointments.show');
    Route::put('appointments/{appointment}/status', [AdminAppointmentController::class, 'updateStatus'])->name('appointments.update-status');

    // Export Routes
    Route::get('export/salons', [\App\Http\Controllers\Admin\ExportController::class, 'exportSalons'])->name('export.salons');
    Route::get('export/bulk-sms-users', [\App\Http\Controllers\Admin\ExportController::class, 'exportBulkSmsUsers'])->name('export.bulk-sms-users');
    Route::get('export/bulk-sms-gift-users', [\App\Http\Controllers\Admin\ExportController::class, 'exportBulkSmsGiftUsers'])->name('export.bulk-sms-gift-users');
    Route::get('export/discount-code-users', [\App\Http\Controllers\Admin\ExportController::class, 'exportDiscountCodeUsers'])->name('export.discount-code-users');

    // Packages Management
    Route::resource('packages', \App\Http\Controllers\Admin\PackageController::class);
    
    // Referral Management
    Route::prefix('referral')->name('referral.')->group(function () {
        Route::get('/', [ReferralManagementController::class, 'dashboard'])->name('dashboard');
        Route::get('/users', [ReferralManagementController::class, 'users'])->name('users');
        Route::get('/referrals', [ReferralManagementController::class, 'referrals'])->name('referrals');
        Route::get('/wallet', [ReferralManagementController::class, 'wallet'])->name('wallet');
        Route::get('/users/{user}/referrals', [ReferralManagementController::class, 'userReferrals'])->name('users.referrals');
        Route::get('/users/{user}/wallet', [ReferralManagementController::class, 'userWallet'])->name('users.wallet');
        Route::post('/wallet/manual-credit', [ReferralManagementController::class, 'manualCredit'])->name('wallet.manual-credit');
        Route::post('/wallet/manual-debit', [ReferralManagementController::class, 'manualDebit'])->name('wallet.manual-debit');
        
        // Settings
        Route::get('/settings', [ReferralSettingsController::class, 'index'])->name('settings');
        Route::post('/settings', [ReferralSettingsController::class, 'update'])->name('settings.update');
    });
    
    // Wallet Management
    Route::prefix('wallet-management')->name('wallet.management.')->group(function () {
        Route::get('/', [WalletManagementController::class, 'index'])->name('index');
        Route::get('/charge', [WalletManagementController::class, 'showChargeForm'])->name('charge');
        Route::post('/charge', [WalletManagementController::class, 'createChargeRequest'])->name('charge.create');
        Route::get('/payment/{order}', [WalletManagementController::class, 'showPaymentPage'])->name('payment');
        Route::post('/payment/{order}/process', [WalletManagementController::class, 'processPayment'])->name('payment.process');
        Route::get('/payment/{order}/verify', [WalletManagementController::class, 'verifyPayment'])->name('payment.verify');
        Route::post('/manual-adjustment', [WalletManagementController::class, 'manualAdjustment'])->name('manual.adjustment');
        Route::get('/history', [WalletManagementController::class, 'getChargeHistory'])->name('history');
    });
    
    // Wallet Packages Management
    Route::prefix('wallet/packages')->name('wallet.packages.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\WalletPackageController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\WalletPackageController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\WalletPackageController::class, 'store'])->name('store');
        Route::get('/{package}', [\App\Http\Controllers\Admin\WalletPackageController::class, 'show'])->name('show');
        Route::get('/{package}/edit', [\App\Http\Controllers\Admin\WalletPackageController::class, 'edit'])->name('edit');
        Route::put('/{package}', [\App\Http\Controllers\Admin\WalletPackageController::class, 'update'])->name('update');
        Route::delete('/{package}', [\App\Http\Controllers\Admin\WalletPackageController::class, 'destroy'])->name('destroy');
        Route::post('/{package}/toggle-status', [\App\Http\Controllers\Admin\WalletPackageController::class, 'toggleStatus'])->name('toggle-status');
    });
    
    // Options Management (only list and toggle)
    Route::get('options', [\App\Http\Controllers\Admin\OptionController::class, 'index'])->name('options.index');
    Route::post('options/{option}/toggle-status', [\App\Http\Controllers\Admin\OptionController::class, 'toggleStatus'])->name('options.toggle-status');
});
        // تنظیمات کارت
        Route::resource('card-setting', \App\Http\Controllers\Admin\CardSettingController::class);
