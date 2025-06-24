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
            'id'             => $this->id,
            'first_name'     => $this->first_name,
            'last_name'      => $this->last_name,
            'full_name'      => "{$this->first_name} {$this->last_name}",

            'email'           => $this->email,
            'phone'           => $this->phone,
            'country'        => $this->country,

            'role'           => $this->role->value,
            'role_label'     => $this->role->label(),

            'verified_user'  => $this->verified_user,
            'kyc_passed_at'  => optional($this->kyc_passed_at)->toDateTimeString(),

            'plan_id'        => $this->plan_id,
            'plan_expires_at' => optional($this->plan_expires_at)->toDateTimeString(),

            // Dates
            'created_at'     => $this->created_at->toDateTimeString(),
        ];
    }
}
