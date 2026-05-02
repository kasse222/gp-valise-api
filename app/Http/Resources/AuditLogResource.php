<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'action'         => $this->action,
            'reason'         => $this->reason,
            'metadata'       => $this->metadata,
            'auditable_type' => $this->auditable_type,
            'auditable_id'   => $this->auditable_id,
            'actor'          => $this->whenLoaded('actor', fn(): array => [
                'id'   => $this->actor->id,
                'name' => $this->actor->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
