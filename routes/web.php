<?php

use App\Enums\UserRoleEnum;
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
