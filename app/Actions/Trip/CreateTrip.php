<?php

declare(strict_types=1);

namespace App\Actions\Trip;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
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

        return DB::transaction(function () use ($user, $data): Trip {
            $categoryFees = Arr::pull($data, 'category_fees', []);

            $trip = $user->trips()->create($data);

            foreach ($categoryFees as $feeData) {
                $trip->categoryFees()->create([
                    'category' => $feeData['category'],
                    'fee'      => (int) $feeData['fee'],
                ]);
            }

            return $trip;
        });
    }
}
