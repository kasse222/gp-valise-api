<?php

use App\Models\Report;
use App\Models\Booking;
use App\Models\User;
use App\Enums\ReportReasonEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\{actingAs, getJson, postJson, get};

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

beforeEach(function () {
    /** @var \App\Models\User $user */
    $this->user = User::factory()->create();

    actingAs($this->user);
});
it('liste les reports de l’utilisateur connecté', function () {
    Report::factory()->count(2)->create(['user_id' => $this->user->id]);
    Report::factory()->create(); // autre user

    $response = getJson('/api/v1/reports');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('crée un nouveau report avec des données valides', function () {
    $booking = Booking::factory()->create();

    $payload = [
        'reportable_id'   => $booking->id,
        'reportable_type' => Booking::class,
        'reason'          => ReportReasonEnum::SCAM_SUSPECT->value,
        'details'         => 'Réservation suspecte, risque de fraude.',
    ];

    $response = postJson('/api/v1/reports', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.reason', ReportReasonEnum::SCAM_SUSPECT->value);

    $this->assertDatabaseHas('reports', [
        'user_id'         => $this->user->id,
        'reportable_id'   => $booking->id,
        'reportable_type' => Booking::class,
        'reason'          => ReportReasonEnum::SCAM_SUSPECT->value,
    ]);
});

it('rejette la création si le motif est invalide', function () {
    $booking = Booking::factory()->create();

    $payload = [
        'reportable_id'   => $booking->id,
        'reportable_type' => Booking::class,
        'reason'          => 'fraude', // ❌ invalide
    ];

    $response = postJson('/api/v1/reports', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['reason']);
});

it('affiche un report appartenant à l’utilisateur', function () {
    $report = Report::factory()->create(['user_id' => $this->user->id])->refresh();

    $response = getJson("/api/v1/reports/{$report->id}");
    $response->assertOk()
        ->assertJsonPath('data.id', $report->id);
});

it('rejette l’accès à un report appartenant à un autre utilisateur', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // Vérifie qu’ils ont bien des id différents
    expect($user->id)->not()->toBe($otherUser->id);

    $otherReport = Report::factory()->create([
        'user_id' => $otherUser->id,
    ]);
    /** @var \App\Models\User $user */

    $response = actingAs($user)->getJson("/api/v1/reports/{$otherReport->id}");

    $response->assertForbidden();
});
