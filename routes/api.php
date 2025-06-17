<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\TripController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//🔓 Routes publiques
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {

    // 👤 Accessible à tous les rôles connectés
    Route::get('/me',     [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ✈️ Trips : réservé aux voyageurs
    Route::middleware([EnsureRole::class . ':voyageur'])->group(function () {
        Route::apiResource('trips', TripController::class)->only([
            'store',
            'update',
            'destroy'
        ]);
    });
    // Lecture possible pour tout le monde connecté
    Route::apiResource('trips', TripController::class)->only([
        'index',
        'show'
    ]);

    // 📦 Bookings : réservé aux expéditeurs
    Route::middleware([EnsureRole::class . ':expediteur'])->group(function () {
        Route::apiResource('bookings', BookingController::class)->only([
            'store',
            'update',
            'destroy'
        ]);
    });
    // Lecture possible pour tout le monde connecté
    Route::apiResource('bookings', BookingController::class)->only([
        'index',
        'show'
    ]);

    // 🎯 Actions personnalisées Booking (v1 métier)
    Route::post('/bookings/{booking}/confirm',  [BookingController::class, 'confirm'])->middleware(EnsureRole::class . ':voyageur');
    Route::post('/bookings/{booking}/cancel',   [BookingController::class, 'cancel'])->middleware(EnsureRole::class . ':expediteur,voyageur');
    Route::post('/bookings/{booking}/complete', [BookingController::class, 'complete'])->middleware(EnsureRole::class . ':voyageur');
});
