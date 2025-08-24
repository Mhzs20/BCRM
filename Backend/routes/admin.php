<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SmsPackageController;
use App\Http\Controllers\Admin\SmsTemplateController;
use App\Http\Controllers\Admin\AdminSmsSettingController;
use App\Http\Controllers\Admin\AppUpdateController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\HowIntroducedController; 
use App\Http\Controllers\Admin\ProfessionController; 
use App\Http\Controllers\Admin\CustomerGroupController;
use App\Http\Controllers\Admin\FileController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\AdminSalonController;
use App\Http\Controllers\Admin\AdminBulkSmsGiftController; // New controller
use App\Http\Controllers\ManualSmsController;
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
    Route::resource('sms-templates', SmsTemplateController::class);

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

    // Bulk SMS Gift
    Route::get('bulk-sms-gift', [AdminBulkSmsGiftController::class, 'index'])->name('bulk-sms-gift.index');
    Route::post('bulk-sms-gift', [AdminBulkSmsGiftController::class, 'sendGift'])->name('bulk-sms-gift.send');
    Route::get('bulk-sms-gift/history', [AdminBulkSmsGiftController::class, 'giftHistory'])->name('bulk-sms-gift.history');
});
