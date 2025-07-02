<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TripController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\LuggageController;
use App\Http\Middleware\EnsureRole;
use App\Enums\UserRoleEnum;
use App\Http\Controllers\Api\V1\BookingItemController;
use App\Http\Controllers\Api\V1\BookingStatusHistoryController;

/*
|--------------------------------------------------------------------------
| 🌐 API v1 – Public (auth non requise)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login',    [AuthController::class, 'login'])->name('auth.login');
});

/*
|--------------------------------------------------------------------------
| 🔐 API v1 – Protégée (auth:sanctum obligatoire)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('v1')->name('api.v1.')->group(function () {

    Route::get('/me',         [AuthController::class, 'me'])->name('auth.me');
    Route::post('/logout',    [AuthController::class, 'logout'])->name('auth.logout');
    Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('auth.logoutAll');

    // ✈️ TRIPS
    Route::middleware(EnsureRole::class . ':' . UserRoleEnum::TRAVELER->value)->group(function () {
        Route::post('/trips',             [TripController::class, 'store'])->name('trips.store');
        Route::put('/trips/{trip}',       [TripController::class, 'update'])->name('trips.update');
        Route::delete('/trips/{trip}',    [TripController::class, 'destroy'])->name('trips.destroy');
    });
    Route::get('/trips',             [TripController::class, 'index'])->name('trips.index');
    Route::get('/trips/{trip}',      [TripController::class, 'show'])->name('trips.show');

    // 📦 BOOKINGS
    Route::middleware(EnsureRole::class . ':' . UserRoleEnum::SENDER->value)->group(function () {
        Route::post('/bookings',             [BookingController::class, 'store'])->name('bookings.store');
        Route::put('/bookings/{booking}',    [BookingController::class, 'update'])->name('bookings.update');
        Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy');
    });
    Route::get('/bookings',              [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}',    [BookingController::class, 'show'])->name('bookings.show');

    Route::post('/bookings/{booking}/confirm', [BookingController::class, 'confirm'])
        ->middleware(EnsureRole::class . ':' . UserRoleEnum::TRAVELER->value)
        ->name('bookings.confirm');

    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])
        ->middleware(EnsureRole::class . ':' . UserRoleEnum::TRAVELER->value . ',' . UserRoleEnum::SENDER->value)
        ->name('bookings.cancel');

    Route::post('/bookings/{booking}/complete', [BookingController::class, 'complete'])
        ->middleware(EnsureRole::class . ':' . UserRoleEnum::TRAVELER->value)
        ->name('bookings.complete');
    Route::prefix('bookings/{booking}')->name('bookings.')->group(function () {
        Route::get('items', [BookingItemController::class, 'index'])->name('items.index');
        Route::post('items', [BookingItemController::class, 'store'])->name('items.store');
    });

    Route::prefix('booking-items')->name('booking_items.')->group(function () {
        Route::put('{item}', [BookingItemController::class, 'update'])->name('update');
        Route::delete('{item}', [BookingItemController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('bookings/{booking}')->name('bookings.')->group(function () {
        Route::get('items', [BookingItemController::class, 'index'])->name('items.index');
        Route::post('items', [BookingItemController::class, 'store'])->name('items.store');

        // 📜 Statuts (BookingStatusHistory)
        Route::get('status-histories', [BookingStatusHistoryController::class, 'index'])->name('status_histories.index');
        Route::post('status-histories', [BookingStatusHistoryController::class, 'store'])->name('status_histories.store');
    });


    // 🎒 LUGGAGE
    Route::middleware(EnsureRole::class . ':' . UserRoleEnum::SENDER->value)->group(function () {
        Route::post('/luggages',             [LuggageController::class, 'store'])->name('luggages.store');
        Route::put('/luggages/{luggage}',    [LuggageController::class, 'update'])->name('luggages.update');
        Route::delete('/luggages/{luggage}', [LuggageController::class, 'destroy'])->name('luggages.destroy');
    });
    Route::get('/luggages',            [LuggageController::class, 'index'])->name('luggages.index');
    Route::get('/luggages/{luggage}',  [LuggageController::class, 'show'])->name('luggages.show');
});

/*
|--------------------------------------------------------------------------
| 🚨 Fallback – Route non trouvée
|--------------------------------------------------------------------------
*/
Route::fallback(function () {
    return response()->json([
        'message' => '🔍 Route introuvable.',
        'status'  => 404,
    ], 404);
});
