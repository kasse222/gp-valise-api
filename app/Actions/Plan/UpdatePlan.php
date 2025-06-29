<?php

namespace App\Actions\Plan;

use App\Models\Plan;

class UpdatePlan
{
    /**
     * Met à jour un plan existant avec les données validées.
     *
     * @param  Plan  $plan
     * @param  array $data
     * @return Plan
     */
    public static function execute(Plan $plan, array $data): Plan
    {
        $plan->update($data);
        return $plan;
    }
}
