<?php

namespace App\Actions\BookingItem;

use App\Models\BookingItem;
use App\Validators\BookingItemValidator;

class UpdateBookingItem
{
    public static function execute(BookingItem $item, array $data): BookingItem
    {
        // 1. Vérification métier (si nécessaire)
        app(BookingItemValidator::class)->validateUpdate($item, $data);

        // 2. Mise à jour de l’item
        $item->update($data);

        return $item;
    }
}
