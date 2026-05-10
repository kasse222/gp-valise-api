<?php

declare(strict_types=1);

use App\Actions\Dispute\UpdateDisputeStatus;
use App\Enums\BookingStatusEnum;
use App\Enums\DisputeStatusEnum;
use App\Events\DisputeStatusChanged;
use App\Models\Booking;
use App\Models\Dispute;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin      = User::factory()->admin()->create();
    $this->expediteur = User::factory()->sender()->create();
    $this->traveler   = User::factory()->traveler()->create();
    $this->trip       = Trip::factory()->create(['user_id' => $this->traveler->id]);
    $this->action     = app(UpdateDisputeStatus::class);
});

// ── helpers ───────────────────────────────────────────────────────────────────

function openDispute(User $expediteur, Trip $trip, DisputeStatusEnum $status = DisputeStatusEnum::OPEN): Dispute
{
    $booking = Booking::factory()->for($expediteur)->for($trip)->create([
        'status'      => BookingStatusEnum::EN_LITIGE,
        'disputed_at' => now(),
    ]);

    return Dispute::create([
        'booking_id' => $booking->id,
        'status'     => $status,
        'opened_by'  => $expediteur->id,
        'reason'     => 'Colis non reçu',
    ]);
}

// ── transitions autorisées ────────────────────────────────────────────────────

it('OPEN → UNDER_REVIEW', function (): void {
    $dispute = openDispute($this->expediteur, $this->trip);

    $result = $this->action->execute($dispute, $this->admin, DisputeStatusEnum::UNDER_REVIEW, 'Analyse démarrée');

    expect($result->status)->toBe(DisputeStatusEnum::UNDER_REVIEW);
});

it('OPEN → ESCALATED', function (): void {
    $dispute = openDispute($this->expediteur, $this->trip);

    $result = $this->action->execute($dispute, $this->admin, DisputeStatusEnum::ESCALATED, 'Cas urgent');

    expect($result->status)->toBe(DisputeStatusEnum::ESCALATED);
});

it('UNDER_REVIEW → WAITING_CUSTOMER', function (): void {
    $dispute = openDispute($this->expediteur, $this->trip, DisputeStatusEnum::UNDER_REVIEW);

    $result = $this->action->execute($dispute, $this->admin, DisputeStatusEnum::WAITING_CUSTOMER, 'En attente preuves expéditeur');

    expect($result->status)->toBe(DisputeStatusEnum::WAITING_CUSTOMER);
});

it('UNDER_REVIEW → WAITING_TRAVELER', function (): void {
    $dispute = openDispute($this->expediteur, $this->trip, DisputeStatusEnum::UNDER_REVIEW);

    $result = $this->action->execute($dispute, $this->admin, DisputeStatusEnum::WAITING_TRAVELER, 'En attente confirmation voyageur');

    expect($result->status)->toBe(DisputeStatusEnum::WAITING_TRAVELER);
});

it('WAITING_CUSTOMER → UNDER_REVIEW', function (): void {
    $dispute = openDispute($this->expediteur, $this->trip, DisputeStatusEnum::WAITING_CUSTOMER);

    $result = $this->action->execute($dispute, $this->admin, DisputeStatusEnum::UNDER_REVIEW, 'Preuves reçues');

    expect($result->status)->toBe(DisputeStatusEnum::UNDER_REVIEW);
});

it('ESCALATED → UNDER_REVIEW', function (): void {
    $dispute = openDispute($this->expediteur, $this->trip, DisputeStatusEnum::ESCALATED);

    $result = $this->action->execute($dispute, $this->admin, DisputeStatusEnum::UNDER_REVIEW, 'Reprise en charge');

    expect($result->status)->toBe(DisputeStatusEnum::UNDER_REVIEW);
});

// ── historique ────────────────────────────────────────────────────────────────

it('crée un DisputeStatusHistory à chaque transition', function (): void {
    $dispute = openDispute($this->expediteur, $this->trip);

    $this->action->execute($dispute, $this->admin, DisputeStatusEnum::UNDER_REVIEW, 'Analyse démarrée');

    $history = $dispute->fresh()->statusHistories()->latest()->first();

    expect($history->old_status)->toBe(DisputeStatusEnum::OPEN)
        ->and($history->new_status)->toBe(DisputeStatusEnum::UNDER_REVIEW)
        ->and($history->changed_by)->toBe($this->admin->id)
        ->and($history->reason)->toBe('Analyse démarrée');
});

it('dispatch DisputeStatusChanged', function (): void {
    Event::fake([DisputeStatusChanged::class]);

    $dispute = openDispute($this->expediteur, $this->trip);

    $this->action->execute($dispute, $this->admin, DisputeStatusEnum::UNDER_REVIEW, 'Analyse démarrée');

    Event::assertDispatched(DisputeStatusChanged::class, function (DisputeStatusChanged $event): bool {
        return $event->oldStatus === DisputeStatusEnum::OPEN
            && $event->newStatus === DisputeStatusEnum::UNDER_REVIEW;
    });
});

it('assigne automatiquement l\'admin qui prend UNDER_REVIEW', function (): void {
    $dispute = openDispute($this->expediteur, $this->trip);

    expect($dispute->assigned_to)->toBeNull();

    $result = $this->action->execute($dispute, $this->admin, DisputeStatusEnum::UNDER_REVIEW, 'Je prends en charge');

    expect($result->assigned_to)->toBe($this->admin->id);
});

it('ne réassigne pas si déjà assigné', function (): void {
    $otherAdmin = User::factory()->admin()->create();
    $dispute    = openDispute($this->expediteur, $this->trip);
    $dispute->update(['assigned_to' => $otherAdmin->id]);

    $result = $this->action->execute($dispute, $this->admin, DisputeStatusEnum::UNDER_REVIEW, 'Reprise');

    expect($result->assigned_to)->toBe($otherAdmin->id); // inchangé
});

// ── invariants ────────────────────────────────────────────────────────────────

it('refuse si acteur non admin', function (): void {
    $dispute = openDispute($this->expediteur, $this->trip);

    $this->action->execute($dispute, $this->expediteur, DisputeStatusEnum::UNDER_REVIEW, 'raison');
})->throws(ValidationException::class);

it('refuse si dispute déjà RESOLVED', function (): void {
    $dispute = openDispute($this->expediteur, $this->trip, DisputeStatusEnum::RESOLVED);

    $this->action->execute($dispute, $this->admin, DisputeStatusEnum::UNDER_REVIEW, 'raison');
})->throws(ValidationException::class);

it('refuse si transition non autorisée', function (): void {
    $dispute = openDispute($this->expediteur, $this->trip, DisputeStatusEnum::OPEN);

    // OPEN → WAITING_CUSTOMER non autorisé
    $this->action->execute($dispute, $this->admin, DisputeStatusEnum::WAITING_CUSTOMER, 'raison');
})->throws(ValidationException::class);

it('refuse si raison vide', function (): void {
    $dispute = openDispute($this->expediteur, $this->trip);

    $this->action->execute($dispute, $this->admin, DisputeStatusEnum::UNDER_REVIEW, '');
})->throws(ValidationException::class);
