<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LedgerDirectionEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LedgerEntry extends Model
{
    public const UPDATED_AT = null; // pas d'updated_at

    protected $fillable = [
        'account_id',
        'transaction_id',
        'direction',
        'amount',
        'currency',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'direction' => LedgerDirectionEnum::class,
            'amount'    => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'account_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
