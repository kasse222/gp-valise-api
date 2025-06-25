<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TripController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\LuggageController;
use App\Http\Middleware\EnsureRole;

/*
|--------------------------------------------------------------------------
| 🔓 Authentification publique
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| 🔐 Routes protégées (auth:sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {

    // 👤 Utilisateur connecté
    Route::get('/me',     [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    /*
    |--------------------------------------------------------------------------
    | ✈️ TripController — Gestion des trajets
    |--------------------------------------------------------------------------
    */

    // ➕ Création / édition / suppression → réservé aux voyageurs
    Route::middleware([EnsureRole::class . ':voyageur'])->group(function () {
        Route::post('/trips',             [TripController::class, 'store']);
        Route::put('/trips/{trip}',       [TripController::class, 'update']);
        Route::delete('/trips/{trip}',    [TripController::class, 'destroy']);
    });

    // 👁️ Lecture des trajets → tous utilisateurs connectés
    Route::get('/trips',                [TripController::class, 'index']);
    Route::get('/trips/{trip}',         [TripController::class, 'show']);

    /*
    |--------------------------------------------------------------------------
    | 📦 BookingController — Réservations
    |--------------------------------------------------------------------------
    */

    // ➕ Création / modification / suppression → expéditeur uniquement
    Route::middleware([EnsureRole::class . ':expediteur'])->group(function () {
        Route::post('/bookings',               [BookingController::class, 'store']);
        Route::put('/bookings/{booking}',      [BookingController::class, 'update']);
        Route::delete('/bookings/{booking}',   [BookingController::class, 'destroy']);
    });

    // 👁️ Lecture des réservations → tous connectés
    Route::get('/bookings',               [BookingController::class, 'index']);
    Route::get('/bookings/{booking}',     [BookingController::class, 'show']);

    // 🔁 Transitions métier → selon les rôles
    Route::post('/bookings/{booking}/confirm',  [BookingController::class, 'confirm'])
        ->middleware(EnsureRole::class . ':voyageur');

    Route::post('/bookings/{booking}/cancel',   [BookingController::class, 'cancel'])
        ->middleware(EnsureRole::class . ':voyageur,expediteur');

    Route::post('/bookings/{booking}/complete', [BookingController::class, 'complete'])
        ->middleware(EnsureRole::class . ':voyageur');

    /*
    |--------------------------------------------------------------------------
    | 🎒 LuggageController — Gestion des valises
    |--------------------------------------------------------------------------
    */

    // ➕ Création / édition / suppression → expéditeur uniquement
    Route::middleware([EnsureRole::class . ':expediteur'])->group(function () {
        Route::post('/luggages',              [LuggageController::class, 'store']);
        Route::put('/luggages/{luggage}',     [LuggageController::class, 'update']);
        Route::delete('/luggages/{luggage}',  [LuggageController::class, 'destroy']);
    });

    // 👁️ Lecture → tous les utilisateurs connectés
    Route::get('/luggages',                [LuggageController::class, 'index']);
    Route::get('/luggages/{luggage}',      [LuggageController::class, 'show']);
});
