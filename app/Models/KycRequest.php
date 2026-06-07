<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\KycStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'id_front_path',
        'id_back_path',
        'admin_notes',
        'rejection_reason',
        'reviewed_by',
        'submitted_at',
        'reviewed_at',
    ];

    protected $casts = [
        'status'       => KycStatusEnum::class,
        'submitted_at' => 'datetime',
        'reviewed_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === KycStatusEnum::PENDING;
    }
    public function isApproved(): bool
    {
        return $this->status === KycStatusEnum::APPROVED;
    }
    public function isRejected(): bool
    {
        return $this->status === KycStatusEnum::REJECTED;
    }
    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }
}
