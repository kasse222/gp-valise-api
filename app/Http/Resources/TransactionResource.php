<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transforme la ressource Transaction en tableau.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,

            // Informations financières
            'amount'    => round($this->amount, 2),
            'currency'  => $this->currency,

            // Méthode de paiement
            'method' => [
                'code'  => $this->method->value,
                'label' => $this->method->label(),
            ],

            // Statut enrichi
            'status' => [
                'code'     => $this->status->value,
                'label'    => $this->status->label(),
                'color'    => $this->status->color(),
                'is_final' => $this->status->isFinal(),
            ],

            // Dates
            'processed_at' => optional($this->processed_at)->toDateTimeString(),
            'created_at'   => $this->created_at->toDateTimeString(),

            // Relations si chargées
            'user'          => new UserResource($this->whenLoaded('user')),
            'booking'       => new BookingResource($this->whenLoaded('booking')),

            // Liens utiles
            'user_id'      => $this->user_id,
            'booking_id'   => $this->booking_id,

        ];
    }
}
