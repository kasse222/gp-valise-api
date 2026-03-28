<?php

namespace App\Actions\Plan;

use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final class CancelUserPlan
{
    public function execute(User $user): User
    {
        if ($user->plan_id === null) {
            throw new DomainException('L’utilisateur n’a aucun plan à annuler.');
        }

        return DB::transaction(function () use ($user) {
            $user->forceFill([
                'plan_id' => null,
                'plan_expires_at' => null,
            ])->save();

            return $user->refresh()->load('plan');
        });
    }
}
