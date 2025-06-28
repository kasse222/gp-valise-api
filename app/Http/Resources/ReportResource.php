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
        $reportableType = class_basename($this->reportable_type);

        return [
            'id'              => $this->id,
            'user_id'         => $this->user_id,

            // Polymorphic cible signalée
            'reportable_type' => $reportableType,
            'reportable_id'   => $this->reportable_id,

            // 🎯 Raison du signalement enrichie
            'reason'          => $this->reason->value,
            'reason_label'    => $this->reason->label(),
            'reason_color'    => $this->reason->color(),
            'reason_description' => $this->reason->description(),

            // Détails supplémentaires
            'details'         => $this->details,

            // Auteur (si chargé)
            'user'            => new UserResource($this->whenLoaded('user')),

            // Cible polymorphe si chargée
            'reportable'      => $this->whenLoaded('reportable', function () use ($reportableType) {
                return match ($reportableType) {
                    'Trip'    => new TripResource($this->reportable),
                    'Booking' => new BookingResource($this->reportable),
                    'Luggage' => new LuggageResource($this->reportable),
                    default   => null, // fallback safe
                };
            }),

            'created_at'      => optional($this->created_at)?->toDateTimeString(),
        ];
    }
}
