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
        $items = $this->whenLoaded('items');

        return [
            'id'           => $this->id,
            'trip_id'      => $this->trip_id,
            'user_id'      => $this->user_id,

            // ✅ Statut enrichi (Enum métier)
            'status'       => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'is_final'     => $this->status->isFinal(),

            // 💬 Détail utilisateur
            'comment'      => $this->comment,
            'kg_reserved'  => $this->whenLoaded('items', fn() => collect($this->items)->filter()->sum('kg_reserved')),

            // 🕓 Dates de transition
            'confirmed_at' => optional($this->confirmed_at)?->toDateTimeString(),
            'completed_at' => optional($this->completed_at)?->toDateTimeString(),
            'cancelled_at' => optional($this->cancelled_at)?->toDateTimeString(),

            // 🔐 Sécurité (est-ce ma réservation ?)
            // 'is_mine'      => $this->user_id === auth()?->id(),

            // 🔗 Relations
            'trip'         => new TripResource($this->whenLoaded('trip')),
            'user'         => new UserResource($this->whenLoaded('user')),
            'booking_items' => BookingItemResource::collection($this->whenLoaded('bookingItems')),
            'items'           => BookingItemResource::collection($this->whenLoaded('items')),
            'status_history' => BookingStatusHistoryResource::collection($this->whenLoaded('statusHistories')),

            // 📅 Timestamps
            'created_at'   => optional($this->created_at)?->toDateTimeString(),
            'updated_at'   => optional($this->updated_at)?->toDateTimeString(),
        ];
    }
}
