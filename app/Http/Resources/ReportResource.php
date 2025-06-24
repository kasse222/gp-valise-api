<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    /**
     * Transform the report resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'user_id'          => $this->user_id,

            // Polymorphic cible signalée
            'reportable_type'  => class_basename($this->reportable_type),
            'reportable_id'    => $this->reportable_id,

            // Détails du signalement
            'reason'           => $this->reason,
            'details'          => $this->details,

            // Auteur (si chargé)
            'user'             => new UserResource($this->whenLoaded('user')),

            // Optionnel : résumé de l'objet signalé
            'reportable'       => $this->whenLoaded('reportable', function () {
                return match (class_basename($this->reportable_type)) {
                    'Trip'    => new TripResource($this->reportable),
                    'Booking' => new BookingResource($this->reportable),
                    'Luggage' => new LuggageResource($this->reportable),
                    default   => null,
                };
            }),

            'created_at'       => $this->created_at->toDateTimeString(),
        ];
    }
}
