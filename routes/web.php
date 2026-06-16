<?php

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Admin\KycFileController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;

Route::get('/', function () {
    return view('welcome');
});

if (app()->environment('local')) {
    Route::get('/local-login-horizon', function () {
        $admin = User::firstOrCreate(
            ['email' => 'admin-horizon@gp-valise.local'],
            [
                'first_name' => 'Admin',
                'last_name' => 'Horizon',
                'password' => Hash::make('password'),
                'role' => UserRoleEnum::ADMIN,
                'verified_user' => true,
            ]
        );

        Auth::login($admin);

        return redirect('/horizon/dashboard');
    });
}

// ── KYC files — streaming sécurisé admin (F-028) ─────────────────────────
// Sert les pièces d'identité depuis le disque privé via un endpoint protégé.
// Accessible uniquement aux admins Filament authentifiés.
Route::middleware(['web'])
    ->prefix('admin/kyc-files')
    ->group(function () {
        Route::get('/{kycRequest}/{field}', [KycFileController::class, 'show'])
            ->name('admin.kyc-files.show');
    });
