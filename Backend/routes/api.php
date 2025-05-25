<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessCategoryController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\SalonController;
use Illuminate\Support\Facades\Route;
 
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('auth')->group(function () {
    Route::post('check-user', [AuthController::class, 'checkUser']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('verify', [AuthController::class, 'verifyOtp']);
    Route::post('complete-profile', [AuthController::class, 'completeProfile'])->middleware('auth:api');
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('refresh', [AuthController::class, 'refreshToken']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
    Route::get('/business-categories', [BusinessCategoryController::class, 'getCategories']);
    Route::get('/business-categories/{categoryId}/subcategories', [BusinessCategoryController::class, 'getSubcategories']);
    Route::get('/business-categories-with-subcategories', [BusinessCategoryController::class, 'getAllCategoriesWithSubcategories']);
});

Route::prefix('locations')->group(function () {
    Route::get('/provinces', [LocationController::class, 'getProvinces']);
    Route::get('/provinces/{provinceId}/cities', [LocationController::class, 'getCities']);
    Route::get('/provinces-with-cities', [LocationController::class, 'getAllProvincesWithCities']);
});

Route::middleware('auth:api')->prefix('salons')->group(function () {
    Route::get('/', [SalonController::class, 'getUserSalons']);
    Route::post('/', [SalonController::class, 'createSalon']);
    Route::get('/{id}', [SalonController::class, 'getSalon']);
    Route::put('/{id}', [SalonController::class, 'updateSalon']);
    Route::delete('/{id}', [SalonController::class, 'deleteSalon']);
});

