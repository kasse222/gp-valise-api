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


            'old_status'   => optional($this->old_status)?->value,
            'old_label'    => optional($this->old_status)?->label(),
            'new_status'   => optional($this->new_status)?->value,
            'new_label'    => optional($this->new_status)?->label(),

            'reason'       => $this->reason,
            'changed_by'   => $this->changed_by,

            'user'         => new UserResource($this->whenLoaded('changedBy')),


            'changed_at'   => optional($this->created_at)?->toDateTimeString(),
        ];
    }
}
