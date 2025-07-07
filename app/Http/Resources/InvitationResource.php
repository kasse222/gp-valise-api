<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
{
    protected bool $canSeeToken = false;

    /**
     * Active l’affichage du token dans la Resource
     */
    public function withCanSeeToken(): static
    {
        $this->canSeeToken = true;
        return $this;
    }

    /**
     * Transforme la ressource Invitation en tableau JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,

            // 👤 Expéditeur
            'sender_id'        => $this->sender_id,

            'recipient_id' => $this->recipient_id,

            // 📧 Destinataire
            'recipient_email'  => $this->recipient_email,

            // 🔐 Token affiché uniquement si `withCanSeeToken()` a été appelé
            'token'            => $this->when($this->canSeeToken, $this->token),

            // 🕓 Dates et statut
            'is_used'          => $this->used_at !== null,
            'used_at'          => optional($this->used_at)?->toDateTimeString(),
            'expires_at'       => optional($this->expires_at)?->toDateTimeString(),

            // 🧠 Enum enrichi
            'status'        => optional($this->status)?->value,
            'status_label'  => optional($this->status)?->label(),
            'status_color'  => optional($this->status)?->color(),


            // 💬 Message
            'message'          => $this->message,

            // 📅 Timestamps
            'created_at'       => $this->created_at->toDateTimeString(),
        ];
    }
}
