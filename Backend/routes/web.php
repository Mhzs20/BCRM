<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AppointmentDetailsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SatisfactionController;
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
Route::get('/view/appointment/{hash}', [AppointmentDetailsController::class, 'showByHash'])->name('appointments.view.hash');

Route::get('/s/{hash}', [SatisfactionController::class, 'showByHash'])->name('satisfaction.show.hash');
Route::post('/s/{hash}', [SatisfactionController::class, 'storeByHash'])->name('satisfaction.store.hash');

// Debug route for testing discount codes (only local)
if (app()->environment('local')) {
    Route::get('/debug-discount/{salonId}', function($salonId) {
        $salon = \App\Models\Salon::with(['user', 'city.province', 'province', 'businessCategory', 'businessSubcategories', 'smsBalance'])->findOrFail($salonId);

        $discountCodes = \App\Models\DiscountCode::where('is_active', true)->get();

        $results = [];
        foreach ($discountCodes as $code) {
            $canUse = $code->canUserUse($salon->user);
            $results[] = [
                'code' => $code->code,
                'user_filter_type' => $code->user_filter_type,
                'target_users' => $code->target_users,
                'can_use' => $canUse,
                'salon_info' => [
                    'id' => $salon->id,
                    'name' => $salon->name,
                    'province_id' => $salon->province_id,
                    'city_id' => $salon->city_id,
                    'business_category_id' => $salon->business_category_id,
                    'is_active' => $salon->is_active,
                ]
            ];
        }

        return response()->json([
            'salon' => $salon,
            'results' => $results
        ]);
    })->name('debug.discount');
}
