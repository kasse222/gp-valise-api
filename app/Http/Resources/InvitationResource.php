<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
{
    protected bool $canSeeToken = false;


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


            'sender_id'        => $this->sender_id,

            'recipient_id' => $this->recipient_id,

            'recipient_email'  => $this->recipient_email,

            'token'            => $this->when($this->canSeeToken, $this->token),

            'is_used'          => $this->used_at !== null,
            'used_at'          => optional($this->used_at)?->toDateTimeString(),
            'expires_at'       => optional($this->expires_at)?->toDateTimeString(),

            'status'        => optional($this->status)?->value,
            'status_label'  => optional($this->status)?->label(),
            'status_color'  => optional($this->status)?->color(),

            'message'          => $this->message,

            'created_at'       => $this->created_at->toDateTimeString(),
        ];
    }
}
