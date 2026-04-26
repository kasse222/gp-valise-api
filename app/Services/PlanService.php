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
    public function upgrade(User $user, int $planId): void
    {
        $plan = Plan::findOrFail($planId);

        // ⏱ Calcul d’une date d’expiration si nécessaire
        $expiresAt = now()->addDays($plan->duration_days);

        //  Mise à jour de l’utilisateur
        $user->update([
            'plan_id'        => $plan->id,
            'plan_expires_at' => $expiresAt,
            'is_premium'     => true, // si applicable
        ]);

        //  Event, notification ou log éventuel
        // event(new PlanUpgraded($user, $plan)); (optionnel)
    }
}
