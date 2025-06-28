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
            'id'          => $this->id,
            'name'        => $this->name,

            'type' => [
                'code'        => $this->type->value,
                'label'       => $this->type->label(),
                'is_paid'     => $this->type->isPaid(),
                'is_giftable' => $this->type->isGiftable(),
            ],

            'price'         => round($this->price, 2),
            'duration_days' => $this->duration_days,

            // Avantages dynamiques
            'features'      => $this->features,

            // Promotions actives
            'discount_percent'     => $this->discount_percent,
            'discount_expires_at'  => optional($this->discount_expires_at)?->toDateTimeString(),
            'has_active_discount'  => $this->hasActiveDiscount(),
            'commission_percent'   => $this->getCommissionPercent(),

            // État
            'is_active'     => $this->is_active,
            'is_available'  => $this->isAvailable(),
            'is_premium'    => $this->isPremium(),

            // Dates
            'created_at'    => $this->created_at?->toDateTimeString(),
            'updated_at'    => $this->updated_at?->toDateTimeString(),
        ];
    }
}
