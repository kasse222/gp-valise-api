<?php

namespace App\Actions\Invitation;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Str;

class SendInvitation
{
    public static function execute(User $sender, string $recipientEmail): Invitation
    {
        return Invitation::create([
            'sender_id'       => $sender->id,
            'recipient_email' => $recipientEmail,
            'token'           => Str::uuid(),
        ]);
    }
}
