<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisputeMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'dispute_id'  => $this->dispute_id,
            'body'        => $this->body,
            'attachments' => $this->attachments,
            'author'      => $this->whenLoaded('author', fn() => [
                'id'   => $this->author->id,
                'name' => trim("{$this->author->first_name} {$this->author->last_name}"),
                'role' => $this->author->role->value,
            ]),
            'created_at'  => $this->created_at?->toISOString(),
        ];
    }
}
