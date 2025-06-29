<?php

namespace App\Actions\Luggage;

use App\Models\User;
use App\Models\Luggage;
use App\Enums\LuggageStatusEnum;

class CreateLuggage
{
    public static function execute(User $user, array $data): Luggage
    {
        return $user->luggages()->create([
            ...$data,
            'status' => LuggageStatusEnum::EN_ATTENTE,
        ]);
    }
}
