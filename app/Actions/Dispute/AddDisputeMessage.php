<?php

declare(strict_types=1);

namespace App\Actions\Dispute;

use App\Enums\UserRoleEnum;
use App\Events\DisputeMessageAdded;
use App\Models\Dispute;
use App\Models\DisputeMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AddDisputeMessage
{
    public function execute(
        Dispute $dispute,
        User $author,
        string $body,
        array $attachments = [],
    ): DisputeMessage {
        return DB::transaction(function () use ($dispute, $author, $body, $attachments): DisputeMessage {
            $dispute = Dispute::query()
                ->with('booking.trip')
                ->lockForUpdate()
                ->findOrFail($dispute->id);

            $this->validate($dispute, $author, $body);

            $message = DisputeMessage::create([
                'dispute_id'  => $dispute->id,
                'author_id'   => $author->id,
                'body'        => $body,
                'attachments' => empty($attachments) ? null : $attachments,
            ]);

            event(new DisputeMessageAdded($message));

            return $message->fresh();
        });
    }

    private function validate(Dispute $dispute, User $author, string $body): void
    {
        // Dispute résolue — plus de messages
        if ($dispute->isResolved()) {
            throw ValidationException::withMessages([
                'dispute' => 'Impossible d\'ajouter un message à un litige résolu.',
            ]);
        }

        // Body obligatoire
        if (blank($body)) {
            throw ValidationException::withMessages([
                'body' => 'Le message ne peut pas être vide.',
            ]);
        }

        // Acteurs autorisés :
        // - expéditeur du booking
        // - voyageur du trip
        // - admin / super_admin
        $isAdmin     = in_array($author->role, [UserRoleEnum::ADMIN, UserRoleEnum::SUPER_ADMIN], true);
        $isExpediteur = $dispute->booking?->user_id === $author->id;
        $isVoyageur  = $dispute->booking?->trip?->user_id === $author->id;

        if (! $isAdmin && ! $isExpediteur && ! $isVoyageur) {
            throw ValidationException::withMessages([
                'author' => 'Vous n\'êtes pas autorisé à participer à ce litige.',
            ]);
        }
    }
}
