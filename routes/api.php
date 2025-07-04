<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\{
    AuthController,
    TripController,
    BookingController,
    BookingItemController,
    BookingStatusHistoryController,
    LuggageController,
    PlanController,
    UserController
};
use App\Http\Middleware\EnsureRole;
use App\Enums\UserRoleEnum;

/*
|--------------------------------------------------------------------------
| 🌐 API v1 – Routes publiques (auth NON requise)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    // 🔐 Enregistrement d’un nouvel utilisateur
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');

    // 🔐 Connexion utilisateur (Sanctum)
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
});

Route::prefix('v1')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::post('/users/{user}/change-password', [UserController::class, 'changePassword']);
        Route::post('/users/{user}/verify-phone', [UserController::class, 'verifyPhone']);
        Route::post('/users/{user}/verify-email', [UserController::class, 'verifyEmail']);
        Route::post('/users/{user}/upgrade-plan', [UserController::class, 'upgradePlan']);
    });
/*
|--------------------------------------------------------------------------
| 🔐 API v1 – Routes protégées (auth:sanctum obligatoire)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('v1')->name('api.v1.')->group(function () {

    // 👤 Infos de l’utilisateur connecté
    Route::get('/me', [AuthController::class, 'me'])->name('auth.me');

    // 🚪 Déconnexion simple
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

    // 🚪 Déconnexion de toutes les sessions
    Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('auth.logoutAll');

    /*
    |--------------------------------------------------------------------------
    | ✈️ Routes TRIPS (création/modif/suppression réservée aux voyageurs)
    |--------------------------------------------------------------------------
    */
    Route::middleware(EnsureRole::class . ':' . UserRoleEnum::TRAVELER->value)->group(function () {
        Route::post('/trips', [TripController::class, 'store'])->name('trips.store');
        Route::put('/trips/{trip}', [TripController::class, 'update'])->name('trips.update');
        Route::delete('/trips/{trip}', [TripController::class, 'destroy'])->name('trips.destroy');
    });

    // 🔍 Accès libre aux détails des trips
    Route::get('/trips', [TripController::class, 'index'])->name('trips.index');
    Route::get('/trips/{trip}', [TripController::class, 'show'])->name('trips.show');


    // 📦 Plans – Création, gestion réservée à l’admin
    Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
    Route::get('/plans/{plan}', [PlanController::class, 'show'])->name('plans.show');

    Route::middleware(EnsureRole::class . ':' . UserRoleEnum::ADMIN->value)->group(function () {
        Route::post('/plans', [PlanController::class, 'store'])->name('plans.store');
        Route::put('/plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
        Route::delete('/plans/{plan}', [PlanController::class, 'destroy'])->name('plans.destroy');
    });

    // ✅ Upgrade d’abonnement (admin uniquement pour le moment)
    Route::post('/plans/{user}/upgrade', [PlanController::class, 'upgradePlan'])->name('plans.upgrade');

    /*
    |--------------------------------------------------------------------------
    | 📦 Routes BOOKINGS (création modif supprim° pour les expéditeurs)
    |--------------------------------------------------------------------------
    */
    Route::middleware(EnsureRole::class . ':' . UserRoleEnum::SENDER->value)->group(function () {
        Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
        Route::put('/bookings/{booking}', [BookingController::class, 'update'])->name('bookings.update');
        Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy');
    });

    // 🔍 Accès à ses propres réservations
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');

    // ✅ Actions spécifiques liées à l’état de réservation
    Route::post('/bookings/{booking}/confirm', [BookingController::class, 'confirm'])
        ->middleware(EnsureRole::class . ':' . UserRoleEnum::TRAVELER->value)
        ->name('bookings.confirm');

    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])
        ->middleware(EnsureRole::class . ':' . UserRoleEnum::TRAVELER->value . ',' . UserRoleEnum::SENDER->value)
        ->name('bookings.cancel');

    Route::post('/bookings/{booking}/complete', [BookingController::class, 'complete'])
        ->middleware(EnsureRole::class . ':' . UserRoleEnum::TRAVELER->value)
        ->name('bookings.complete');

    /*
    |--------------------------------------------------------------------------
    | 📦 Booking Items (objets réservés dans une réservation)
    |--------------------------------------------------------------------------
    */
    Route::prefix('bookings/{booking}')->name('bookings.')->group(function () {
        // 📄 Liste des items d’un booking
        Route::get('items', [BookingItemController::class, 'index'])->name('items.index');
        // ➕ Création d’un item
        Route::post('items', [BookingItemController::class, 'store'])->name('items.store');

        /*
        |--------------------------------------------------------------------------
        | 📜 Booking Status History – Historique des statuts
        |--------------------------------------------------------------------------
        */
        Route::get('status-histories', [BookingStatusHistoryController::class, 'index'])
            ->name('status_histories.index');

        Route::post('status-histories', [BookingStatusHistoryController::class, 'store'])
            ->name('status_histories.store');

        Route::post('status', [BookingStatusHistoryController::class, 'store'])->name('status.store');
    });

    // ✏️ Mise à jour ou suppression directe via ID item
    Route::prefix('booking-items')->name('booking_items.')->group(function () {
        Route::put('{item}', [BookingItemController::class, 'update'])->name('update');
        Route::delete('{item}', [BookingItemController::class, 'destroy'])->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | 🎒 Routes LUGGAGE (CRUD valise – expéditeurs seulement)
    |--------------------------------------------------------------------------
    */
    Route::middleware(EnsureRole::class . ':' . UserRoleEnum::SENDER->value)->group(function () {
        Route::post('/luggages', [LuggageController::class, 'store'])->name('luggages.store');
        Route::put('/luggages/{luggage}', [LuggageController::class, 'update'])->name('luggages.update');
        Route::delete('/luggages/{luggage}', [LuggageController::class, 'destroy'])->name('luggages.destroy');
    });

    // 🔍 Tous peuvent consulter leurs propres valises
    Route::get('/luggages', [LuggageController::class, 'index'])->name('luggages.index');
    Route::get('/luggages/{luggage}', [LuggageController::class, 'show'])->name('luggages.show');
});

/*
|--------------------------------------------------------------------------
| 🚨 Route fallback – pour les erreurs 404 JSON
|--------------------------------------------------------------------------
*/
Route::fallback(function () {
    return response()->json([
        'message' => '🔍 Route introuvable.',
        'status'  => 404,
    ], 404);
});
