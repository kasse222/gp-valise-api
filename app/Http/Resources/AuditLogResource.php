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

            'auditable' => [
                'type' => $this->auditable_type,
                'id'   => $this->auditable_id,
            ],

            'actor' => $this->whenLoaded('actor', fn(): array => [
                'id'   => $this->actor->id,
                'name' => $this->actor->name,
            ]),

            // 🔥 AJOUT CRITIQUE
            'integrity' => [
                'hash'          => $this->integrity_hash,
                'previous_hash' => $this->previous_hash,
            ],

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
