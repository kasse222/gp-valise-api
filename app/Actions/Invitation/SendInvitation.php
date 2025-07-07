<?php

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
        return Invitation::create([
            'sender_id'       => $sender->id,
            'recipient_email' => $recipientEmail,
            'token'           => Str::uuid(),
            'status'          => InvitationStatusEnum::PENDING,
            'expires_at'      => now()->addDays(7), // 💡 configurable ?
            'message'         => $message,
        ]);
    }
}
