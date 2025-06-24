<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the payment resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,

            // Montant
            'amount'     => round($this->amount, 2),
            'currency'   => 'EUR', // ou configurable selon app()

            // Statut enrichi
            'status' => [
                'code'   => $this->status->value,
                'label'  => $this->status->label(),
                'color'  => $this->status->color(),
                'is_final' => $this->status->isFinal(),
                'is_success' => $this->status->isSuccess(),
            ],

            // Méthode de paiement
            'method' => [
                'code'   => $this->method->value,
                'label'  => $this->method->label(),
            ],

            // Dates
            'paid_at'    => optional($this->paid_at)->toDateTimeString(),
            'created_at' => $this->created_at->toDateTimeString(),

            // Relations
            'user_id'    => $this->user_id,
            'booking_id' => $this->booking_id,
        ];
    }
}
