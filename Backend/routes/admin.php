<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SmsPackageController;
use App\Http\Controllers\Admin\SmsTemplateController;
use App\Http\Controllers\Admin\AdminSmsSettingController;
use App\Http\Controllers\Admin\AppUpdateController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\HowIntroducedController; // Add this line
use App\Http\Controllers\Admin\ProfessionController; // Add this line
use App\Http\Controllers\Admin\CustomerGroupController; // Add this line
use App\Http\Controllers\ManualSmsController; // Add this line
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
    Route::post('manual-sms-approval/{batchId}/approve', [ManualSmsController::class, 'approveManualSms'])->name('manual_sms.approve');
    Route::post('manual-sms-approval/{batchId}/reject', [ManualSmsController::class, 'rejectManualSms'])->name('manual_sms.reject');
    Route::resource('sms-templates', SmsTemplateController::class);

    // SMS Settings Routes
    Route::get('sms-settings', [AdminSmsSettingController::class, 'index'])->name('sms_settings.index');
    Route::post('sms-settings', [AdminSmsSettingController::class, 'update'])->name('sms_settings.update');

    // App Updates
    Route::resource('app-updates', AppUpdateController::class)->except(['show', 'edit', 'update', 'destroy']);

    // Notifications
    Route::resource('notifications', NotificationController::class);

    // How Introduced
    Route::resource('how-introduced', HowIntroducedController::class);

    // Professions
    Route::resource('professions', ProfessionController::class);

    // Customer Groups
    Route::resource('customer-groups', CustomerGroupController::class);
});
