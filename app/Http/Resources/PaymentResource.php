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

        //  dd($this->resource);
        return [
            'id'               => $this->id,

            // 👤 Utilisateur associé
            'user_id'          => $this->user_id,

            // 🔄 Réservation liée
            'booking_id'       => $this->booking_id,

            // 💳 Méthode de paiement
            'method'           => $this->method?->value,
            'method_label'     => $this->method?->label(),

            // 📊 Statut du paiement
            'status'           => $this->status?->value,
            'status_label'     => $this->status?->label(),

            // 💰 Montant et devise
            'amount' => (float) $this->amount,
            'currency' => $this->currency?->value,

            // 🔐 Référence unique
            'payment_reference' => $this->payment_reference,

            // 📅 Date de paiement
            'paid_at'          => optional($this->paid_at)?->toDateTimeString(),

            // 🕓 Timestamps
            'created_at'       => optional($this->created_at)?->toDateTimeString(),
            'updated_at'       => optional($this->updated_at)?->toDateTimeString(),
        ];
    }
}
