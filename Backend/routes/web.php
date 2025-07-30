<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AppointmentDetailsController;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

// Auth Routes for Admin Panel
Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'webLogin']);
Route::post('logout', [AuthController::class, 'webLogout'])->name('logout');

Route::get('/a/{hash}', [AppointmentDetailsController::class, 'showByHash'])->name('appointments.show.hash');
