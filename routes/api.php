<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\TripController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 🔓 Routes publiques
Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

// 🔐 Routes protégées par Sanctum
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Auth user
    Route::get('/me',     [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Trip API REST (index, store, show, update, destroy)
    Route::apiResource('trips', TripController::class)->only([
        'index',
        'store',
        'show',
        'update',
        'destroy'
    ]);
    // Booking API REST (index, store, show, update, destroy)
    Route::apiResource('bookings', BookingController::class)->only([
        'index',
        'store',
        'show',
        'update',
        'destroy'
    ]);
});
