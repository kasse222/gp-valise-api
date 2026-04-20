<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\{
    AuthController,
    TripController,
    BookingController,
    BookingItemController,
    BookingStatusHistoryController,
    InvitationController,
    LocationController,
    LuggageController,
    PaymentController,
    PlanController,
    ReportController,
    TransactionController,
    UserController,
    WebhookController
};
use App\Http\Middleware\EnsureRole;
use App\Enums\UserRoleEnum;

/*
|--------------------------------------------------------------------------
| 🌐 API v1 – Routes publiques
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

    /*
    |--------------------------------------------------------------------------
    | 🔔 Webhook paiement public mais signé
    |--------------------------------------------------------------------------
    */
    Route::post('/webhooks/payment', WebhookController::class)
        ->middleware(['webhook.signature', 'throttle:webhooks'])
        ->name('webhooks.payment');
});

/*
|--------------------------------------------------------------------------
| 🔐 API v1 – Routes protégées
|--------------------------------------------------------------------------
*/
Route::prefix('v1')
    ->middleware(['auth:sanctum'])
    ->name('api.v1.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | 🔑 Auth
        |--------------------------------------------------------------------------
        */
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('auth.logoutAll');

        /*
        |--------------------------------------------------------------------------
        | 👤 Users
        |--------------------------------------------------------------------------
        */
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/{user}', [UserController::class, 'show'])->name('show');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::post('/{user}/change-password', [UserController::class, 'changePassword'])->name('change_password');
            Route::post('/{user}/verify-phone', [UserController::class, 'verifyPhone'])->name('verify_phone');
            Route::post('/{user}/verify-email', [UserController::class, 'verifyEmail'])->name('verify_email');
            Route::post('/{user}/upgrade-plan', [UserController::class, 'upgradePlan'])->name('upgrade_plan');
        });

        /*
        |--------------------------------------------------------------------------
        | ✈️ Trips
        |--------------------------------------------------------------------------
        */
        Route::get('/trips', [TripController::class, 'index'])->name('trips.index');
        Route::get('/trips/{trip}', [TripController::class, 'show'])->name('trips.show');

        Route::middleware(EnsureRole::class . ':' . UserRoleEnum::TRAVELER->value)->group(function () {
            Route::post('/trips', [TripController::class, 'store'])->name('trips.store');
            Route::put('/trips/{trip}', [TripController::class, 'update'])->name('trips.update');
            Route::delete('/trips/{trip}', [TripController::class, 'destroy'])->name('trips.destroy');
        });

        /*
        |--------------------------------------------------------------------------
        | 📦 Plans
        |--------------------------------------------------------------------------
        */
        Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
        Route::get('/plans/{plan}', [PlanController::class, 'show'])->name('plans.show');

        Route::middleware(EnsureRole::class . ':' . UserRoleEnum::ADMIN->value)->group(function () {
            Route::post('/plans', [PlanController::class, 'store'])->name('plans.store');
            Route::put('/plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
            Route::delete('/plans/{plan}', [PlanController::class, 'destroy'])->name('plans.destroy');
        });

        Route::post('/plans/{user}/upgrade', [PlanController::class, 'upgradePlan'])->name('plans.upgrade');

        /*
        |--------------------------------------------------------------------------
        | 📍 Locations
        |--------------------------------------------------------------------------
        */
        Route::prefix('locations')->name('locations.')->group(function () {
            Route::get('/', [LocationController::class, 'index'])->name('index');
            Route::get('/{location}', [LocationController::class, 'show'])->name('show');

            Route::post('/', [LocationController::class, 'store'])
                ->middleware(EnsureRole::class . ':' . UserRoleEnum::ADMIN->value . ',' . UserRoleEnum::TRAVELER->value)
                ->name('store');
        });

        /*
        |--------------------------------------------------------------------------
        | 📦 Bookings
        |--------------------------------------------------------------------------
        */
        Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');

        Route::middleware(EnsureRole::class . ':' . UserRoleEnum::SENDER->value)->group(function () {
            Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
            Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy');
        });

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
        | 📦 Booking items + status histories
        |--------------------------------------------------------------------------
        */
        Route::prefix('bookings/{booking}')->name('bookings.')->group(function () {
            Route::get('items', [BookingItemController::class, 'index'])->name('items.index');
            Route::post('items', [BookingItemController::class, 'store'])->name('items.store');

            Route::get('status-histories', [BookingStatusHistoryController::class, 'index'])
                ->name('status_histories.index');
        });

        Route::prefix('booking-items')->name('booking_items.')->group(function () {
            Route::put('{item}', [BookingItemController::class, 'update'])->name('update');
            Route::delete('{item}', [BookingItemController::class, 'destroy'])->name('destroy');
        });

        /*
        |--------------------------------------------------------------------------
        | 🎒 Luggages
        |--------------------------------------------------------------------------
        */
        Route::get('/luggages', [LuggageController::class, 'index'])->name('luggages.index');
        Route::get('/luggages/{luggage}', [LuggageController::class, 'show'])->name('luggages.show');

        Route::middleware(EnsureRole::class . ':' . UserRoleEnum::SENDER->value)->group(function () {
            Route::post('/luggages', [LuggageController::class, 'store'])->name('luggages.store');
            Route::put('/luggages/{luggage}', [LuggageController::class, 'update'])->name('luggages.update');
            Route::delete('/luggages/{luggage}', [LuggageController::class, 'destroy'])->name('luggages.destroy');
        });

        /*
        |--------------------------------------------------------------------------
        | 📧 Invitations
        |--------------------------------------------------------------------------
        */
        Route::prefix('invitations')->name('invitations.')->group(function () {
            Route::get('/', [InvitationController::class, 'index'])->name('index');
            Route::post('/', [InvitationController::class, 'store'])->name('store');
            Route::post('/accept', [InvitationController::class, 'accept'])->name('accept_by_token');
            Route::get('/{invitation}', [InvitationController::class, 'show'])->name('show');
            Route::delete('/{invitation}', [InvitationController::class, 'destroy'])->name('destroy');
        });

        /*
        |--------------------------------------------------------------------------
        | 🚨 Reports
        |--------------------------------------------------------------------------
        */
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::post('/', [ReportController::class, 'store'])->name('store');
            Route::get('/{report}', [ReportController::class, 'show'])->name('show');
        });

        /*
        |--------------------------------------------------------------------------
        | 💰 Transactions = seule API financière publique
        |--------------------------------------------------------------------------
        */
        Route::middleware(['verified_user'])->group(function () {
            Route::apiResource('transactions', TransactionController::class)
                ->only(['index', 'show', 'store']);
        });

        Route::middleware(['verified_user', 'kyc', 'throttle.sensitive:finance,5,1'])->group(function () {
            Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
            Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
            Route::post('transactions/{transaction}/refund', [TransactionController::class, 'refund'])
                ->name('transactions.refund');
        });
    });

/*
|--------------------------------------------------------------------------
| 🚨 Fallback JSON
|--------------------------------------------------------------------------
*/
Route::fallback(function () {
    return response()->json([
        'message' => '🔍 Route introuvable.',
        'status'  => 404,
    ], 404);
});
