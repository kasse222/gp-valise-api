<?php

namespace App\Actions\Invitation;

use App\Models\Invitation;
use App\Enums\InvitationStatusEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AcceptInvitation
{
    /**
     * Marque une invitation comme utilisée
     */
    public static function execute(string $token): Invitation
    {
        $invitation = Invitation::available()
            ->where('token', $token)
            ->firstOrFail();

        DB::transaction(function () use ($invitation) {
            // 🧠 Logique métier additionnelle possible ici (création de compte, attribution plan...)

            $invitation->update([
                'used_at' => Carbon::now(),
                'status'  => InvitationStatusEnum::USED,
            ]);
        });

        return $invitation->refresh(); // ✅ pour avoir le modèle à jour
    }
}
