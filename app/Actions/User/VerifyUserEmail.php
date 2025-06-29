<?php

namespace App\Actions\User;

use App\Models\User;

class VerifyUserEmail
{
    public static function execute(User $user): void
    {
        $user->update([
            'email_verified_at' => now(),
        ]);
    }
}
