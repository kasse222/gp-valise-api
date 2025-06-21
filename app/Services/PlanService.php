<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Carbon;

class PlanService
{
    public function subscribe(User $user, Plan $plan): void
    {
        $duration = $plan->duration_days ?? 30;

        $user->update([
            'plan_id'         => $plan->id,
            'plan_expires_at' => now()->addDays($duration),
        ]);
    }

    public function cancel(User $user): void
    {
        $user->update([
            'plan_id' => null,
            'plan_expires_at' => null,
        ]);
    }

    public function hasFeature(User $user, string $feature): bool
    {
        return in_array($feature, $user->plan?->features ?? []);
    }
}
