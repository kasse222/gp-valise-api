<?php

namespace App\Actions\Location;

use App\Models\Location;

class CreateLocation
{
    public static function execute(array $data): Location
    {
        return Location::create($data);
    }
}
