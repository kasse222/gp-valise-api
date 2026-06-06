<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Enums\DisputeStatusEnum;
use App\Models\Booking;
use App\Models\Dispute;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->sender   = User::factory()->sender()->verified()->create();
    $this->traveler = User::factory()->traveler()->verified()->create();
    $this->trip     = Trip::factory()->create(['user_id' => $this->traveler->id]);
    $this->booking  = Booking::factory()->create([
        'user_id' => $this->sender->id,
        'trip_id' => $this->trip->id,
        'status'  => BookingStatusEnum::CONFIRMEE,
    ]);
});

it('sender peut ouvrir un litige sur son booking', function (): void {
    Sanctum::actingAs($this->sender);

    $this->postJson("/api/v1/bookings/{$this->booking->id}/dispute", [
        'reason' => 'Mon colis est arrivé endommagé.',
    ])->assertCreated()
        ->assertJsonPath('data.status.code', DisputeStatusEnum::OPEN->value);

    $this->assertDatabaseHas('disputes', [
        'booking_id' => $this->booking->id,
        'status'     => DisputeStatusEnum::OPEN->value,
    ]);
});

it('refuse si raison trop courte', function (): void {
    Sanctum::actingAs($this->sender);

    $this->postJson("/api/v1/bookings/{$this->booking->id}/dispute", [
        'reason' => 'court',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['reason']);
});

it('traveler ne peut pas ouvrir un litige via cet endpoint', function (): void {
    Sanctum::actingAs($this->traveler);

    $this->postJson("/api/v1/bookings/{$this->booking->id}/dispute", [
        'reason' => 'Mon colis est arrivé endommagé.',
    ])->assertForbidden();
});

it('sender peut voir le litige', function (): void {
    Sanctum::actingAs($this->sender);

    $dispute = Dispute::factory()->create([
        'booking_id' => $this->booking->id,
        'opened_by'  => $this->sender->id,
        'status'     => DisputeStatusEnum::OPEN,
    ]);

    $this->getJson("/api/v1/disputes/{$dispute->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $dispute->id);
});

it('traveler peut voir le litige de son trip', function (): void {
    Sanctum::actingAs($this->traveler);

    $dispute = Dispute::factory()->create([
        'booking_id' => $this->booking->id,
        'opened_by'  => $this->sender->id,
        'status'     => DisputeStatusEnum::OPEN,
    ]);

    $this->getJson("/api/v1/disputes/{$dispute->id}")
        ->assertOk();
});

it('autre user ne peut pas voir le litige', function (): void {
    $autreUser = User::factory()->sender()->create();
    Sanctum::actingAs($autreUser);

    $dispute = Dispute::factory()->create([
        'booking_id' => $this->booking->id,
        'opened_by'  => $this->sender->id,
        'status'     => DisputeStatusEnum::OPEN,
    ]);

    $this->getJson("/api/v1/disputes/{$dispute->id}")
        ->assertForbidden();
});

it('sender peut ajouter un message', function (): void {
    Sanctum::actingAs($this->sender);

    $dispute = Dispute::factory()->create([
        'booking_id' => $this->booking->id,
        'opened_by'  => $this->sender->id,
        'status'     => DisputeStatusEnum::OPEN,
    ]);

    $this->postJson("/api/v1/disputes/{$dispute->id}/messages", [
        'body' => 'Voici les photos du colis endommagé.',
    ])->assertCreated()
        ->assertJsonPath('data.body', 'Voici les photos du colis endommagé.');
});

it('traveler peut ajouter un message', function (): void {
    Sanctum::actingAs($this->traveler);

    $dispute = Dispute::factory()->create([
        'booking_id' => $this->booking->id,
        'opened_by'  => $this->sender->id,
        'status'     => DisputeStatusEnum::OPEN,
    ]);

    $this->postJson("/api/v1/disputes/{$dispute->id}/messages", [
        'body' => 'Le colis était intact à la livraison.',
    ])->assertCreated();
});

it('refuse message vide', function (): void {
    Sanctum::actingAs($this->sender);

    $dispute = Dispute::factory()->create([
        'booking_id' => $this->booking->id,
        'opened_by'  => $this->sender->id,
        'status'     => DisputeStatusEnum::OPEN,
    ]);

    $this->postJson("/api/v1/disputes/{$dispute->id}/messages", [
        'body' => '',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['body']);
});

it('sender peut lister les messages', function (): void {
    Sanctum::actingAs($this->sender);

    $dispute = Dispute::factory()->create([
        'booking_id' => $this->booking->id,
        'opened_by'  => $this->sender->id,
        'status'     => DisputeStatusEnum::OPEN,
    ]);

    $dispute->messages()->createMany([
        ['author_id' => $this->sender->id, 'body' => 'Message 1'],
        ['author_id' => $this->traveler->id, 'body' => 'Message 2'],
    ]);

    $this->getJson("/api/v1/disputes/{$dispute->id}/messages")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});
