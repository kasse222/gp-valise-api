<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LedgerAccountTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class LedgerAccount extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'type',
        'currency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type'      => LedgerAccountTypeEnum::class,
            'is_active' => 'boolean',
        ];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class, 'account_id');
    }

    public function balance(): int
    {
        return (int) $this->entries()
            ->selectRaw("SUM(CASE WHEN direction = 'CREDIT' THEN amount ELSE -amount END) as balance")
            ->value('balance');
    }
}
