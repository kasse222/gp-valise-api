<?php

declare(strict_types=1);

namespace App\Data\Luggage;

final class LuggageContentItem
{
    public function __construct(
        public readonly string  $category,
        public readonly string  $description,
        public readonly ?string $photo_path = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            category: $data['category']   ?? 'other',
            description: $data['description'] ?? '',
            photo_path: $data['photo_path']  ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'category'    => $this->category,
            'description' => $this->description,
            'photo_path'  => $this->photo_path,
        ], fn($v) => $v !== null);
    }
}
