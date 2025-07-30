<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SmsPackageController;
use App\Http\Controllers\Admin\SmsTemplateController;
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
    Route::resource('sms-templates', SmsTemplateController::class);
});
