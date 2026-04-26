<?php

namespace App\Actions\BookingItem;

use App\Models\BookingItem;
use App\Validators\BookingItemValidator;

class UpdateBookingItem
{
    public static function execute(BookingItem $item, array $data): BookingItem
    {

        app(BookingItemValidator::class)->validateUpdate($item, $data);


        $item->update($data);

        return $item;
    }
}
