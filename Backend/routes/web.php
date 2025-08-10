<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AppointmentDetailsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ZarinpalController;

Route::get('/', function () {
    return view('welcome');
});

// Auth Routes for Admin Panel
Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'webLogin']);
Route::post('logout', [AuthController::class, 'webLogout'])->name('logout');

// Zarinpal Payment Routes
Route::post('zarinpal/purchase/{packageId}', [ZarinpalController::class, 'purchase'])->name('zarinpal.purchase');
Route::get('zarinpal/callback', [ZarinpalController::class, 'callback'])->name('zarinpal.callback');

Route::get('/a/{hash}', [AppointmentDetailsController::class, 'showByHash'])->name('appointments.show.hash');
