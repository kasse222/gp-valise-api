<?php

namespace App\Actions\Invitation;

use App\Models\Invitation;
use Illuminate\Support\Facades\DB;

class AcceptInvitation
{
    public static function execute(string $token): Invitation
    {
        $invitation = Invitation::where('token', $token)
            ->whereNull('used_at')
            ->firstOrFail();

        DB::transaction(function () use ($invitation) {
            // Logique métier : création d’utilisateur, association...
            $invitation->update(['used_at' => now()]);
        });

        return $invitation;
    }
}
