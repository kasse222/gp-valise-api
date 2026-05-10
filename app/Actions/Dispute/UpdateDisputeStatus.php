<?php

declare(strict_types=1);

namespace App\Actions\Dispute;

use App\Enums\DisputeStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\Dispute;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateDisputeStatus
{
    public function execute(
        Dispute $dispute,
        User $admin,
        DisputeStatusEnum $newStatus,
        string $reason,
    ): Dispute {
        return DB::transaction(function () use ($dispute, $admin, $newStatus, $reason): Dispute {
            $dispute = Dispute::query()
                ->lockForUpdate()
                ->findOrFail($dispute->id);

            $this->validate($dispute, $admin, $newStatus, $reason);

            $dispute->transitionTo(
                newStatus: $newStatus,
                changedBy: $admin->id,
                reason: $reason,
            );

            // Assigner automatiquement à l'admin qui prend en charge
            if (
                $newStatus === DisputeStatusEnum::UNDER_REVIEW
                && $dispute->assigned_to === null
            ) {
                $dispute->update(['assigned_to' => $admin->id]);
            }

            return $dispute->fresh();
        });
    }

    private function validate(
        Dispute $dispute,
        User $admin,
        DisputeStatusEnum $newStatus,
        string $reason,
    ): void {
        // Acteur
        if (! in_array($admin->role, [UserRoleEnum::ADMIN, UserRoleEnum::SUPER_ADMIN], true)) {
            throw ValidationException::withMessages([
                'actor' => 'Seul un admin peut modifier le statut d\'un litige.',
            ]);
        }

        // Dispute déjà résolue
        if ($dispute->isResolved()) {
            throw ValidationException::withMessages([
                'dispute' => 'Ce litige est déjà résolu — statut immuable.',
            ]);
        }

        // Transition autorisée
        if (! $dispute->status->canTransitionTo($newStatus)) {
            throw ValidationException::withMessages([
                'status' => "Transition non autorisée : {$dispute->status->value} → {$newStatus->value}",
            ]);
        }

        // Raison obligatoire
        if (blank($reason)) {
            throw ValidationException::withMessages([
                'reason' => 'La raison du changement de statut est obligatoire.',
            ]);
        }
    }
}
