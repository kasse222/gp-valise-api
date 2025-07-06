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
        // dd($this->amount, gettype($this->amount));

        return [
            'id'       => $this->id,

            // 💰 Montant + devise
            'amount' => is_numeric($this->amount) ? (float) $this->amount : null,
            'currency' => [
                'code'  => optional($this->currency)?->value,
                'label' => optional($this->currency)?->label(),
            ],

            // 🧾 Méthode de paiement
            'method' => [
                'code'  => optional($this->method)?->value,
                'label' => optional($this->method)?->label(),
            ],

            // 📊 Statut enrichi
            'status' => [
                'code'       => optional($this->status)?->value,
                'label'      => optional($this->status)?->label(),
                'color'      => optional($this->status)?->color(),
                'is_final'   => optional($this->status)?->isFinal(),
                'is_success' => optional($this->status)?->isSuccess(),
            ],

            // 🕓 Dates
            'processed_at' => optional($this->processed_at)?->toDateTimeString(),
            'created_at'   => optional($this->created_at)?->toDateTimeString(),

            // 🔗 Relations
            'user_id'    => $this->user_id,
            'booking_id' => $this->booking_id,
            'user'       => new UserResource($this->whenLoaded('user')),
            'booking'    => new BookingResource($this->whenLoaded('booking')),
        ];
    }
}
