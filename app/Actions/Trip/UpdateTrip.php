<?php

declare(strict_types=1);

namespace App\Actions\Trip;

use App\Models\Trip;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateTrip
{
    public function execute(Trip $trip, array $validated): Trip
    {
        return DB::transaction(function () use ($trip, $validated): Trip {
            $categoryFees = Arr::pull($validated, 'category_fees', null);

            $trip->update($validated);

            // null = non fourni → on ne touche pas aux fees existants
            // [] = fourni vide → on supprime tous les fees
            // [...] = fourni avec données → on remplace (upsert)
            if ($categoryFees !== null) {
                $trip->categoryFees()->delete();

                foreach ($categoryFees as $feeData) {
                    $trip->categoryFees()->create([
                        'category' => $feeData['category'],
                        'fee'      => (int) $feeData['fee'],
                    ]);
                }
            }

            return $trip->fresh(['categoryFees']);
        });
    }
}
