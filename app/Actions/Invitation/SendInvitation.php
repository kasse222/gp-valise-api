<?php

declare(strict_types=1);

namespace App\Actions\Invitation;

use App\Models\Invitation;
use App\Models\User;
use App\Enums\InvitationStatusEnum;
use Illuminate\Support\Str;

class SendInvitation
{
    /**
     * Crée une invitation pour un email donné
     */
    public static function execute(User $sender, string $recipientEmail, ?string $message = null): Invitation
    {
        $recipient = User::where('email', $recipientEmail)->first();

        return Invitation::create([
            'sender_id'       => $sender->id,
            'recipient_email' => $recipientEmail,
            'recipient_id'    => $recipient?->id, // Null si non inscrit
            'token'           => Str::uuid(),
            'status'          => InvitationStatusEnum::PENDING,
            'expires_at'      => now()->addDays(7),
            'message'         => $message,
        ]);
    }
}
