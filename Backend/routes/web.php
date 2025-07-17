<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AppointmentDetailsController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/a/{hash}', [AppointmentDetailsController::class, 'showByHash'])->name('appointments.show.hash');
