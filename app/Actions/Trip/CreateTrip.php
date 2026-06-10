<?php

declare(strict_types=1);

namespace App\Actions\Trip;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CreateTrip
{
    public static function execute(User $user, array $data): Trip
    {
        if ($user->isVoyageur() && ! $user->hasKyc()) {
            throw ValidationException::withMessages([
                'kyc' => ['Vous devez compléter votre vérification d\'identité (KYC) avant de publier un trajet.'],
            ]);
        }

        return $user->trips()->create($data);
    }
}
