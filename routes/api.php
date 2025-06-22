<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\TripController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

// 🔐 Authentification requise
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {

    // 👤 Infos utilisateur connecté
    Route::get('/me',     [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ✈️ Trips — Écriture réservée aux voyageurs
    Route::middleware([EnsureRole::class . ':voyageur'])->group(function () {
        Route::post('/trips',    [TripController::class, 'store']);
        Route::put('/trips/{trip}', [TripController::class, 'update']);
        Route::delete('/trips/{trip}', [TripController::class, 'destroy']);
    });

    // ✈️ Trips — Lecture ouverte à tous les connectés
    Route::get('/trips',         [TripController::class, 'index']);
    Route::get('/trips/{trip}',  [TripController::class, 'show']);

    // 📦 Bookings — Écriture réservée aux expéditeurs
    Route::middleware([EnsureRole::class . ':expediteur'])->group(function () {
        Route::post('/bookings',      [BookingController::class, 'store']);
        Route::put('/bookings/{booking}', [BookingController::class, 'update']);
        Route::delete('/bookings/{booking}', [BookingController::class, 'destroy']);
    });

    // 📦 Bookings — Lecture ouverte à tous les connectés
    Route::get('/bookings',         [BookingController::class, 'index']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);

    // 📌 Actions métier personnalisées sur bookings
    Route::post('/bookings/{booking}/confirm',  [BookingController::class, 'confirm'])
        ->middleware(EnsureRole::class . ':voyageur');

    Route::post('/bookings/{booking}/cancel',   [BookingController::class, 'cancel'])
        ->middleware(EnsureRole::class . ':voyageur,expediteur');

    Route::post('/bookings/{booking}/complete', [BookingController::class, 'complete'])
        ->middleware(EnsureRole::class . ':voyageur');
});
