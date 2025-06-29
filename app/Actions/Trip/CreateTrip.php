<?php

namespace App\Actions\Trip;

use App\Models\Trip;
use App\Models\User;

class CreateTrip
{
    public static function execute(User $user, array $data): Trip
    {
        return $user->trips()->create($data);
    }
}
