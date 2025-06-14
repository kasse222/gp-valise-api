<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\TripController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//🔓 Routes publiques
Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

// 🔐 Routes protégées par Sanctum
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // 👤 Authenticated user
    Route::get('/me',     [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ✈️ API REST - Trips (standard CRUD)
    Route::apiResource('trips', TripController::class)->only([
        'index',
        'store',
        'show',
        'update',
        'destroy'
    ]);

    // 📦 API REST - Bookings (standard CRUD)
    Route::apiResource('bookings', BookingController::class)->only([
        'index',
        'store',
        'show',
        'update',
        'destroy'
    ]);

    // 🎯 Actions personnalisées Booking (v1 métier)
    Route::post('/bookings/{booking}/confirm',  [BookingController::class, 'confirm']);
    Route::post('/bookings/{booking}/cancel',   [BookingController::class, 'cancel']);
    Route::post('/bookings/{booking}/complete', [BookingController::class, 'complete']);
});
