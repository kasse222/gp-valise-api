<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RegisterUser
{
    public function execute(array $data): User
    {
        return User::create([
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'email'         => $data['email'],
            'password'      => Hash::make($data['password']),
            'role'          => UserRoleEnum::from($data['role']),
            'phone'         => $data['phone'],
            'country'       => $data['country'] ?? null,
            'verified_user' => false,
            'kyc_passed_at' => null,
        ]);
    }
}
