<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'first_name'  => $this->first_name,
            'last_name'   => $this->last_name,
            'full_name'   => "{$this->first_name} {$this->last_name}",

            'email'       => $this->email,
            'phone'       => $this->phone,
            'country'     => $this->country,

            // 🎯 Rôle enrichi
            'role'        => $this->role->value,
            'role_label'  => $this->role->label(),

            // 🔐 Sécurité
            'verified_user' => $this->verified_user,
            'kyc_passed_at' => optional($this->kyc_passed_at)?->toDateTimeString(),
            'email_verified_at' => optional($this->email_verified_at)?->toDateTimeString(),

            // 💳 Abonnement
            'plan_id'         => $this->plan_id,
            'plan_expires_at' => optional($this->plan_expires_at)?->toDateTimeString(),
            'is_premium'      => $this->isPremium(),
            'plan'            => new PlanResource($this->whenLoaded('plan')),

            // 🕓 Création
            'created_at'      => $this->created_at->toDateTimeString(),
        ];
    }
}
