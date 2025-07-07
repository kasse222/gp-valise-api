<?php

namespace App\Actions\Invitation;

use App\Models\Invitation;
use App\Enums\InvitationStatusEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AcceptInvitation
{
    /**
     * Accepte une invitation valide à l’aide du token fourni.
     */
    public static function execute(string $token): Invitation
    {
        $invitation = Invitation::available()
            ->where('token', $token)
            ->firstOrFail();

        DB::transaction(function () use ($invitation) {
            // Logique métier personnalisable : création de compte, attribution de plan, etc.
            $invitation->update([
                'used_at' => Carbon::now(),
                'status'  => InvitationStatusEnum::USED,
            ]);
        });

        return $invitation->refresh();
    }
}
