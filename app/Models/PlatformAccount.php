<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CurrencyEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PlatformAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'currency',
        'country_code',
        'provider',
        'is_active',
        'balance',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'currency'   => CurrencyEnum::class,
            'is_active'  => 'boolean',
            'balance'    => 'integer',
            'metadata'   => 'array',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function credit(int $amount): void
    {
        $this->increment('balance', $amount);
    }

    public function debit(int $amount): void
    {
        if ($amount > $this->balance) {
            throw new \RuntimeException(
                "Insufficient balance on platform account [{$this->id}]."
            );
        }

        $this->decrement('balance', $amount);
    }
}
