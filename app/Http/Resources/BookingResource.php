<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'user_id'         => $this->user_id,
            'trip_id'         => $this->trip_id,

            // ✅ Statut enrichi (enum)
            'status'          => $this->status->value,
            'status_label'    => $this->status->label(),
            'status_color'    => $this->status->color(),

            // 💬 Commentaire facultatif
            'comment'         => $this->comment,

            // 🕓 Dates liées au statut
            'confirmed_at'    => optional($this->confirmed_at)->toDateTimeString(),
            'completed_at'    => optional($this->completed_at)->toDateTimeString(),
            'cancelled_at'    => optional($this->cancelled_at)->toDateTimeString(),

            // 🔗 Relations
            'user'            => new UserResource($this->whenLoaded('user')),
            'trip'            => new TripResource($this->whenLoaded('trip')),
            'items'           => BookingItemResource::collection($this->whenLoaded('items')),
            'status_history'  => BookingStatusHistoryResource::collection($this->whenLoaded('statusHistories')),


            // 📅 Audit
            'created_at'      => $this->created_at->toDateTimeString(),
            'updated_at'      => $this->updated_at->toDateTimeString(),


        ];
    }
}
