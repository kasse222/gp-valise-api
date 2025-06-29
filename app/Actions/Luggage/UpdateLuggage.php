<?php

namespace App\Actions\Luggage;

use App\Models\Luggage;

class UpdateLuggage
{
    public static function execute(Luggage $luggage, array $data): Luggage
    {
        $luggage->update($data);

        return $luggage;
    }
}
