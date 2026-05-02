<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

class AuditLog extends Model
{
    public const UPDATED_AT = null; // audit log jamais modifié

    protected $fillable = [
        'actor_id',
        'action',
        'auditable_type',
        'auditable_id',
        'metadata',
        'reason',       // obligatoire pour admin override refund
    ];

    protected $casts = [
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    // -------------------------------------------------------
    // Immuabilité — un audit log ne peut jamais être modifié
    // -------------------------------------------------------

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new LogicException('AuditLog is immutable and cannot be updated.');
    }

    public function delete(): bool|null
    {
        throw new LogicException('AuditLog is immutable and cannot be deleted.');
    }

    // -------------------------------------------------------
    // Relations
    // -------------------------------------------------------

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new LogicException('AuditLog is immutable and cannot be saved after creation.');
        }

        return parent::save($options);
    }
}
