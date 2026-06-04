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
            'id'                 => $this->id,
            'status'             => [
                'code'  => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],
            'id_photo_path'      => $this->id_photo_path,
            'parcel_photo_path'  => $this->parcel_photo_path,
            'admin_notes'        => $this->admin_notes,
            'rejection_reason'   => $this->rejection_reason,
            'submitted_at'       => $this->submitted_at?->toISOString(),
            'reviewed_at'        => $this->reviewed_at?->toISOString(),
            'reviewer'           => $this->whenLoaded('reviewer', fn() => [
                'id'   => $this->reviewer->id,
                'name' => $this->reviewer->getFilamentName(),
            ]),
        ];
    }
}
