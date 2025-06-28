<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingStatusHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'booking_id'   => $this->booking_id,

            // 🧠 Statuts transition
            'old_status'   => $this->old_status->value,
            'old_label'    => $this->old_status->label(),
            'new_status'   => $this->new_status->value,
            'new_label'    => $this->new_status->label(),

            // 🔍 Informations de changement
            'reason'       => $this->reason,
            'changed_by'   => $this->changed_by,

            // 👤 Auteur du changement
            'user'         => new UserResource($this->whenLoaded('changedBy')),

            // 🕓 Timestamp de la transition
            'changed_at'   => optional($this->created_at)?->toDateTimeString(),
        ];
    }
}
