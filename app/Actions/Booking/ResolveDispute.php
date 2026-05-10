<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Actions\Transaction\AdminRefundTransaction;
use App\Actions\Transaction\MarkPayoutCompleted;
use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\UserRoleEnum;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\User;
use App\Services\AuditLogIntegrityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ResolveDispute
{
    public const DECISION_REFUND = 'refund';
    public const DECISION_PAYOUT = 'payout';

    public function __construct(
        private readonly AdminRefundTransaction    $adminRefund,
        private readonly MarkPayoutCompleted       $markPayoutCompleted,
        private readonly AuditLogIntegrityService  $integrity,
    ) {}

    public function execute(
        Booking $booking,
        User $admin,
        string $decision,
        string $reason,
    ): Booking {
        return DB::transaction(function () use ($booking, $admin, $decision, $reason): Booking {
            $booking = Booking::query()
                ->with('transactions')
                ->lockForUpdate()
                ->findOrFail($booking->id);

            $this->validate($booking, $admin, $decision, $reason);

            if ($decision === self::DECISION_REFUND) {
                $this->resolveWithRefund($booking, $admin, $reason);
            } else {
                $this->resolveWithPayout($booking, $admin, $reason);
            }

            // ── Dispute v2 — marquer RESOLVED ────────────────────────────────────
            // ── Dispute v2 — marquer RESOLVED ─────────────────────────────────
            $dispute = $booking->dispute;
            if ($dispute && ! $dispute->isResolved()) {
                $dispute->resolve(
                    decision: \App\Enums\DisputeDecisionEnum::from($decision),
                    resolution: $reason,
                    resolvedBy: $admin->id,
                );
            }
            // ─────────────────────────────────────────────────────────────────
            // ─────────────────────────────────────────────────────────────────────
            // ── AuditLog dispute_resolved ─────────────────────────────────────

            $auditLog = AuditLog::query()->create([
                'actor_id'      => $admin->id,
                'action'        => 'dispute_resolved',
                'auditable_type' => Booking::class,
                'auditable_id'  => $booking->id,
                'metadata'      => [
                    'decision'        => $decision,
                    'reason'          => $reason,
                    'booking_status'  => $booking->fresh()->status->value,
                    'resolved_by'     => $admin->email,
                    'resolved_at'     => now()->toIso8601String(),
                ],
            ]);

            $this->integrity->seal($auditLog);
            // ─────────────────────────────────────────────────────────────────

            return $booking->fresh();
        });
    }

    // ── private ───────────────────────────────────────────────────────────────

    private function validate(
        Booking $booking,
        User $admin,
        string $decision,
        string $reason,
    ): void {
        // Acteur
        if (! in_array($admin->role, [UserRoleEnum::ADMIN, UserRoleEnum::SUPER_ADMIN], true)) {
            throw ValidationException::withMessages([
                'actor' => 'Seul un admin peut résoudre un litige.',
            ]);
        }

        // Statut
        if ($booking->status !== BookingStatusEnum::EN_LITIGE) {
            throw ValidationException::withMessages([
                'booking' => 'Ce booking n\'est pas en litige.',
            ]);
        }

        // Raison
        if (blank($reason)) {
            throw ValidationException::withMessages([
                'reason' => 'La raison de résolution est obligatoire.',
            ]);
        }

        // Decision
        if (! in_array($decision, [self::DECISION_REFUND, self::DECISION_PAYOUT], true)) {
            throw ValidationException::withMessages([
                'decision' => 'Décision invalide — refund ou payout uniquement.',
            ]);
        }

        // Payout sans PAYOUT PENDING
        if ($decision === self::DECISION_PAYOUT) {
            $hasPayout = $booking->transactions()
                ->where('type', TransactionTypeEnum::PAYOUT)
                ->where('status', TransactionStatusEnum::PENDING)
                ->exists();

            if (! $hasPayout) {
                throw ValidationException::withMessages([
                    'decision' => 'Aucun payout disponible — ce booking n\'a pas été livré avant le litige.',
                ]);
            }
        }

        // Idempotence — déjà résolu
        if (in_array($booking->status, [
            BookingStatusEnum::REMBOURSEE,
            BookingStatusEnum::TERMINE,
        ], true)) {
            throw ValidationException::withMessages([
                'booking' => 'Ce litige est déjà résolu.',
            ]);
        }
    }

    private function resolveWithRefund(Booking $booking, User $admin, string $reason): void
    {
        // PAYOUT PENDING → FAILED pour libérer l'éligibilité refund
        $booking->transactions()
            ->where('type', TransactionTypeEnum::PAYOUT)
            ->where('status', TransactionStatusEnum::PENDING)
            ->update([
                'status'       => TransactionStatusEnum::FAILED,
                'processed_at' => now(),
            ]);

        $charge = $booking->transactions()
            ->where('type', TransactionTypeEnum::CHARGE)
            ->where('status', TransactionStatusEnum::COMPLETED)
            ->latest()
            ->firstOrFail();

        $this->adminRefund->execute($admin, $charge, $reason);
    }

    private function resolveWithPayout(Booking $booking, User $admin, string $reason): void
    {
        $payout = $booking->transactions()
            ->where('type', TransactionTypeEnum::PAYOUT)
            ->where('status', TransactionStatusEnum::PENDING)
            ->latest()
            ->firstOrFail();

        // MarkPayoutCompleted : PAYOUT PENDING → COMPLETED + writePayoutPaid + LIVREE → TERMINE
        // Mais le booking est EN_LITIGE, pas LIVREE — on force la transition
        $payout->update([
            'status'       => TransactionStatusEnum::COMPLETED,
            'processed_at' => now(),
        ]);

        app(\App\Services\LedgerWriter::class)->writePayoutPaid($payout->fresh());

        $booking->transitionTo(
            BookingStatusEnum::TERMINE,
            $admin,
            "Litige résolu — payout accordé : {$reason}"
        );
    }
}
