<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

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

            // 👤 Expéditeur (parrain)
            'sender_id'        => $this->sender_id,

            // 📧 Destinataire
            'recipient_email'  => $this->recipient_email,

            // 🔐 Token visible uniquement si admin ou émetteur
            'token'           => $this->when($this->isAuthorized($request), $this->token),


            // 🕓 Statuts et dates
            'is_used'          => $this->used_at !== null,
            'used_at'          => optional($this->used_at)?->toDateTimeString(),
            'expires_at'       => optional($this->expires_at)?->toDateTimeString(),

            // 🧠 Enum enrichi
            'status'           => $this->status->value,
            'status_label'     => $this->status->label(),
            'status_color'     => $this->status->color(),

            // 💬 Message facultatif
            'message'          => $this->message,

            // 📅 Timestamps
            'created_at'       => $this->created_at->toDateTimeString(),
        ];
    }
}
