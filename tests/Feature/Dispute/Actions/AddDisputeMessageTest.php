<?php

declare(strict_types=1);

use App\Actions\Dispute\AddDisputeMessage;
use App\Enums\BookingStatusEnum;
use App\Enums\DisputeStatusEnum;
use App\Events\DisputeMessageAdded;
use App\Models\Booking;
use App\Models\Dispute;
use App\Models\DisputeMessage;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin      = User::factory()->admin()->create();
    $this->traveler   = User::factory()->traveler()->create();
    $this->expediteur = User::factory()->sender()->create();
    $this->trip       = Trip::factory()->create(['user_id' => $this->traveler->id]);
    $this->action     = app(AddDisputeMessage::class);

    $booking = Booking::factory()
        ->for($this->expediteur)
        ->for($this->trip)
        ->create([
            'status'      => BookingStatusEnum::EN_LITIGE,
            'disputed_at' => now(),
        ]);

    $this->dispute = Dispute::create([
        'booking_id' => $booking->id,
        'status'     => DisputeStatusEnum::UNDER_REVIEW,
        'opened_by'  => $this->expediteur->id,
        'reason'     => 'Colis non reçu',
    ]);
});

// ── cas nominaux ──────────────────────────────────────────────────────────────

it('expéditeur peut ajouter un message', function (): void {
    $message = $this->action->execute($this->dispute, $this->expediteur, 'Voici ma preuve de commande.');

    expect($message)->toBeInstanceOf(DisputeMessage::class)
        ->and($message->dispute_id)->toBe($this->dispute->id)
        ->and($message->author_id)->toBe($this->expediteur->id)
        ->and($message->body)->toBe('Voici ma preuve de commande.');
});

it('voyageur peut ajouter un message', function (): void {
    $message = $this->action->execute($this->dispute, $this->traveler, 'Le colis a bien été remis.');

    expect($message->author_id)->toBe($this->traveler->id);
});

it('admin peut ajouter un message', function (): void {
    $message = $this->action->execute($this->dispute, $this->admin, 'Nous avons examiné les preuves.');

    expect($message->author_id)->toBe($this->admin->id);
});

it('message avec pièces jointes', function (): void {
    $attachments = ['photos/colis_1.jpg', 'photos/colis_2.jpg'];

    $message = $this->action->execute(
        $this->dispute,
        $this->expediteur,
        'Photo du colis endommagé.',
        $attachments,
    );

    expect($message->attachments)->toBe($attachments);
});

it('message sans pièces jointes — attachments null', function (): void {
    $message = $this->action->execute($this->dispute, $this->expediteur, 'Message simple.');

    expect($message->attachments)->toBeNull();
});

it('dispatch DisputeMessageAdded', function (): void {
    Event::fake([DisputeMessageAdded::class]);

    $this->action->execute($this->dispute, $this->expediteur, 'Message test.');

    Event::assertDispatched(DisputeMessageAdded::class, function (DisputeMessageAdded $event): bool {
        return $event->message->dispute_id === $this->dispute->id;
    });
});

it('plusieurs messages conservés dans l\'ordre', function (): void {
    $this->action->execute($this->dispute, $this->expediteur, 'Premier message.');
    $this->action->execute($this->dispute, $this->traveler, 'Deuxième message.');
    $this->action->execute($this->dispute, $this->admin, 'Troisième message.');

    $messages = $this->dispute->fresh()->messages;

    expect($messages)->toHaveCount(3)
        ->and($messages->first()->body)->toBe('Premier message.')
        ->and($messages->last()->body)->toBe('Troisième message.');
});

// ── invariants ────────────────────────────────────────────────────────────────

it('refuse si dispute RESOLVED', function (): void {
    $this->dispute->update(['status' => DisputeStatusEnum::RESOLVED]);

    $this->action->execute($this->dispute, $this->expediteur, 'Message tardif.');
})->throws(ValidationException::class);

it('refuse si body vide', function (): void {
    $this->action->execute($this->dispute, $this->expediteur, '   ');
})->throws(ValidationException::class);

it('refuse si auteur non autorisé', function (): void {
    $stranger = User::factory()->sender()->create();

    $this->action->execute($this->dispute, $stranger, 'Message intrus.');
})->throws(ValidationException::class);

it('moderateur non autorisé', function (): void {
    $moderator = User::factory()->create(['role' => \App\Enums\UserRoleEnum::MODERATOR]);

    $this->action->execute($this->dispute, $moderator, 'Message modérateur.');
})->throws(ValidationException::class);
