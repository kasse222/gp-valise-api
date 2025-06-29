<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transforme un paiement en JSON pour l'API.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,

            // 🔄 Booking lié
            'booking_id'     => $this->booking_id,

            // 💳 Méthode de paiement
            'method'         => $this->method->value,
            'method_label'   => $this->method->label(),

            // 📊 Statut de paiement
            'status'         => $this->status->value,
            'status_label'   => $this->status->label(),

            // 💰 Montant et devise
            'amount'         => $this->amount,
            'currency'       => $this->currency,

            // 📅 Date de paiement (optionnelle)
            'paid_at'        => optional($this->paid_at)?->toDateTimeString(),

            // 🕓 Timestamps
            'created_at'     => $this->created_at->toDateTimeString(),
            'updated_at'     => $this->updated_at->toDateTimeString(),
        ];
    }
}
