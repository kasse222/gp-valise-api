<?php

namespace App\Actions\Plan;

use App\Models\Plan;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final class SubscribeUserToPlan
{
    public function execute(User $user, Plan $plan): User
    {
        if (! $plan->isAvailable()) {
            throw new DomainException('Ce plan n’est pas disponible.');
        }

        if ($user->plan_id === $plan->id && $user->plan_expires_at?->isFuture()) {
            throw new DomainException('L’utilisateur a déjà un abonnement actif sur ce plan.');
        }

        return DB::transaction(function () use ($user, $plan) {
            $user->forceFill([
                'plan_id' => $plan->id,
                'plan_expires_at' => now()->addDays($plan->duration_days),
            ])->save();

            return $user->refresh()->load('plan');
        });
    }
}
