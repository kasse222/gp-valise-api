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
            'id'       => $this->id,

            // 💰 Montant + devise
            'amount'   => round($this->amount, 2),
            'currency' => [
                'code'  => $this->currency->value,
                'label' => $this->currency->label(),
            ],

            // 🧾 Méthode de paiement
            'method' => [
                'code'  => $this->method->value,
                'label' => $this->method->label(),
            ],

            // 📊 Statut enrichi
            'status' => [
                'code'       => $this->status->value,
                'label'      => $this->status->label(),
                'color'      => $this->status->color(),
                'is_final'   => $this->status->isFinal(),
                'is_success' => $this->status->isSuccess(),
            ],

            // 🕓 Dates
            'processed_at' => optional($this->processed_at)?->toDateTimeString(),
            'created_at'   => optional($this->created_at)?->toDateTimeString(),

            // 🔗 Relations
            'user_id'      => $this->user_id,
            'booking_id'   => $this->booking_id,
            'user'         => new UserResource($this->whenLoaded('user')),
            'booking'      => new BookingResource($this->whenLoaded('booking')),
        ];
    }
}
