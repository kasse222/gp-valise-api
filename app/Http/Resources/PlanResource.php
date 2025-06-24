<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    /**
     * Transform the plan resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,

            // Type du plan (avec label)
            'type' => [
                'code'  => $this->type->value,
                'label' => $this->type->label(),
                'is_paid' => $this->type->isPaid(),
                'is_giftable' => $this->type->isGiftable(),
            ],

            // Tarifs
            'price'          => round($this->price, 2),
            'duration_days'  => $this->duration_days,

            // Avantages inclus
            'features'       => $this->features, // tableau JSON

            // Promotions (si existantes)
            'discount_percent'    => $this->discount_percent,
            'discount_expires_at' => optional($this->discount_expires_at)->toDateTimeString(),

            // État
            'is_active'     => $this->is_active,

            // Dates
            'created_at'    => $this->created_at->toDateTimeString(),
            'updated_at'           => $this->updated_at?->toDateTimeString(),

        ];
    }
}
