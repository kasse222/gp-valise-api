<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
{
    /**
     * Transform the invitation resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            //	Pour relier au parrain
            'sender_id'        => $this->sender_id,

            // Email invité
            'recipient_email'  => $this->recipient_email,

            // Token (affichable uniquement en mode admin ou owner ?)
            'used_at'          => optional($this->used_at)->toDateTimeString(),

            // Statut d'utilisation
            'is_used'          => $this->used_at !== null,
            'used_at'          => optional($this->used_at)->toDateTimeString(),

            // Métadonnées
            'created_at'       => $this->created_at->toDateTimeString(),
        ];
    }
}
