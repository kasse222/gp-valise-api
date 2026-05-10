<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DisputeStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DisputeStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'dispute_id',
        'old_status',
        'new_status',
        'changed_by',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'old_status' => DisputeStatusEnum::class,
            'new_status' => DisputeStatusEnum::class,
            'created_at' => 'datetime',
        ];
    }

    public function dispute(): BelongsTo
    {
        return $this->belongsTo(Dispute::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
