<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DisputeDecisionEnum;
use App\Enums\DisputeStatusEnum;
use App\Events\DisputeStatusChanged;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

final class Dispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'status',
        'opened_by',
        'assigned_to',
        'resolved_by',
        'reason',
        'resolution',
        'decision',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'status'      => DisputeStatusEnum::class,
            'decision'    => DisputeDecisionEnum::class,
            'resolved_at' => 'datetime',
        ];
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DisputeMessage::class)->orderBy('created_at');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(DisputeStatusHistory::class)->orderBy('created_at');
    }

    // ── Transitions ───────────────────────────────────────────────────────────

    public function transitionTo(
        DisputeStatusEnum $newStatus,
        ?int $changedBy = null,
        ?string $reason = null,
    ): void {
        if (! $this->status->canTransitionTo($newStatus)) {
            throw ValidationException::withMessages([
                'status' => "Transition impossible : {$this->status->value} → {$newStatus->value}",
            ]);
        }

        $oldStatus = $this->status;

        $this->update(['status' => $newStatus]);

        DisputeStatusHistory::create([
            'dispute_id' => $this->id,
            'old_status' => $oldStatus->value,
            'new_status' => $newStatus->value,
            'changed_by' => $changedBy,
            'reason'     => $reason,
        ]);

        event(new DisputeStatusChanged(
            dispute: $this->fresh(),
            oldStatus: $oldStatus,
            newStatus: $newStatus,
            reason: $reason,
        ));
    }

    public function resolve(
        DisputeDecisionEnum $decision,
        string $resolution,
        int $resolvedBy,
    ): void {
        if ($this->isResolved()) {
            throw ValidationException::withMessages([
                'dispute' => 'Ce litige est déjà résolu.',
            ]);
        }

        $oldStatus = $this->status;

        $this->update([
            'status'      => DisputeStatusEnum::RESOLVED,
            'decision'    => $decision,
            'resolution'  => $resolution,
            'resolved_by' => $resolvedBy,
            'resolved_at' => now(),
        ]);

        DisputeStatusHistory::create([
            'dispute_id' => $this->id,
            'old_status' => $oldStatus->value,
            'new_status' => DisputeStatusEnum::RESOLVED->value,
            'changed_by' => $resolvedBy,
            'reason'     => $resolution,
        ]);

        event(new DisputeStatusChanged(
            dispute: $this->fresh(),
            oldStatus: $oldStatus,
            newStatus: DisputeStatusEnum::RESOLVED,
            reason: $resolution,
        ));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isResolved(): bool
    {
        return $this->status === DisputeStatusEnum::RESOLVED;
    }

    public function isActive(): bool
    {
        return ! $this->isResolved();
    }
}
