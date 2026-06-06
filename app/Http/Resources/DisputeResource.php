<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisputeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'booking_id' => $this->booking_id,
            'status'     => [
                'code'  => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],
            'reason'      => $this->reason,
            'resolution'  => $this->resolution,
            'decision'    => $this->decision?->value,
            'opened_by'   => $this->opened_by,
            'assigned_to' => $this->assigned_to,
            'resolved_at' => $this->resolved_at?->toISOString(),
            'created_at'  => $this->created_at?->toISOString(),
            'messages'    => DisputeMessageResource::collection(
                $this->whenLoaded('messages')
            ),
            'status_histories' => $this->whenLoaded(
                'statusHistories',
                fn() =>
                $this->statusHistories->map(fn($h) => [
                    'old_status' => $h->old_status?->value,
                    'new_status' => $h->new_status->value,
                    'reason'     => $h->reason,
                    'created_at' => $h->created_at?->toISOString(),
                ])
            ),
        ];
    }
}
