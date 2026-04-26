<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{

    public function toArray(Request $request): array
    {

        return [
            'id'               => $this->id,

            'user_id'          => $this->user_id,

            'booking_id'       => $this->booking_id,

            'method'           => $this->method?->value,
            'method_label'     => $this->method?->label(),

            'status'           => $this->status?->value,
            'status_label'     => $this->status?->label(),

            'amount' => (float) $this->amount,
            'currency' => $this->currency?->value,

            'payment_reference' => $this->payment_reference,

            'paid_at'          => optional($this->paid_at)?->toDateTimeString(),

            'created_at'       => optional($this->created_at)?->toDateTimeString(),
            'updated_at'       => optional($this->updated_at)?->toDateTimeString(),
        ];
    }
}
