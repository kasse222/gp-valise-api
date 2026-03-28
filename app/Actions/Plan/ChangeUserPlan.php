<?php

namespace App\Actions\Plan;

use App\Models\Plan;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final class ChangeUserPlan
{
    public function execute(User $user, Plan $targetPlan): User
    {
        if (! $targetPlan->isAvailable()) {
            throw new DomainException('Le plan cible n’est pas disponible.');
        }

        if ($user->plan_id === null || ! $user->plan_expires_at?->isFuture()) {
            throw new DomainException("L’utilisateur doit avoir un plan actif avant un upgrade.");
        }

        if ($user->plan_id === $targetPlan->id) {
            throw new DomainException('L’utilisateur est déjà sur ce plan.');
        }

        return DB::transaction(function () use ($user, $targetPlan) {
            $user->forceFill([
                'plan_id' => $targetPlan->id,
                'plan_expires_at' => now()->addDays($targetPlan->duration_days),
            ])->save();

            return $user->refresh()->load('plan');
        });
    }
}
