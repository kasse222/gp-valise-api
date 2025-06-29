<?php

namespace App\Actions\Plan;

use App\Models\Plan;

class CreatePlan
{
    public static function execute(array $data): Plan
    {
        return Plan::create($data);
    }
}
