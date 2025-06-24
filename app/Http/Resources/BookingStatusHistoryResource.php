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

            'old_status'   => $this->old_status->value,
            'old_label'    => $this->old_status->label(),
            'new_status'   => $this->new_status->value,
            'new_label'    => $this->new_status->label(),

            'changed_by'   => $this->changed_by,
            'reason'       => $this->reason,

            'user'         => new UserResource($this->whenLoaded('changedBy')),
            'changed_at'   => $this->created_at->toDateTimeString(),
        ];
    }
}
