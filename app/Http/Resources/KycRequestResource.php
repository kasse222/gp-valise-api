<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KycRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'status' => [
                'code'  => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],
            'id_front_path' => $this->when($request->user()?->isAdmin(), $this->id_front_path),
            'id_back_path'  => $this->when($request->user()?->isAdmin(), $this->id_back_path),
            'admin_notes'   => $this->when($request->user()?->isAdmin(), $this->admin_notes),
            'rejection_reason' => $this->rejection_reason,
            'submitted_at'     => $this->submitted_at?->toISOString(),
            'reviewed_at'      => $this->reviewed_at?->toISOString(),
            'reviewer'         => $this->whenLoaded('reviewer', fn() => [
                'id'   => $this->reviewer->id,
                'name' => $this->reviewer->getFilamentName(),
            ]),
        ];
    }
}
