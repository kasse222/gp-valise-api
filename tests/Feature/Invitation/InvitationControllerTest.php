<?php

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\deleteJson;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->verified()->create();
    actingAs($this->user);
});

// ✅ Liste les invitations envoyées par l’utilisateur connecté
it('liste les invitations envoyées par l’utilisateur connecté', function () {
    Invitation::factory()
        ->count(3)
        ->for($this->user, 'sender')
        ->create();

    getJson('/api/v1/invitations')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

// ✅ Envoie une invitation valide
it('envoie une invitation valide', function () {
    $recipient = User::factory()->create();

    postJson('/api/v1/invitations', [
        'recipient_email' => $recipient->email,
    ])
        ->assertCreated()
        ->assertJsonPath('data.recipient_email', $recipient->email)
        ->assertJsonStructure(['data' => ['token']]);
});

// ❌ Refuse l’auto-invitation
it('rejette une invitation envoyée à soi-même', function () {
    postJson('/api/v1/invitations', [
        'recipient_email' => $this->user->email,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['recipient_email']);
});

// ✅ Accepte une invitation avec un token valide
it('accepte une invitation avec un token valide', function () {
    $invitation = Invitation::factory()->create([
        'token'           => (string) Str::uuid(),
        'recipient_email' => 'test@example.com',
    ]);

    postJson('/api/v1/invitations/accept', [
        'token' => $invitation->token,
    ])
        ->assertOk()
        ->assertJson(['message' => 'Invitation acceptée.']);


    expect(Invitation::find($invitation->id)->used_at)->not->toBeNull();
});

// ✅ Rejette une invitation avec un token invalide
it('rejette une invitation avec un token invalide', function () {
    postJson("/api/v1/invitations/accept", [
        'token' => (string) Str::uuid(), // Token invalide
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['token']);
});


// ✅ Supprime une invitation valide
it('supprime une invitation valide', function () {
    $invitation = Invitation::factory()
        ->for($this->user, 'sender')
        ->create();

    deleteJson("/api/v1/invitations/{$invitation->id}")
        ->assertOk()
        ->assertJson(['message' => 'Invitation supprimée avec succès.']);

    expect(Invitation::find($invitation->id))->toBeNull();
});

// ❌ Refuse de supprimer une invitation déjà utilisée
it('rejette la suppression d’une invitation utilisée', function () {
    $invitation = Invitation::factory()
        ->for($this->user, 'sender')
        ->used()
        ->create();

    deleteJson("/api/v1/invitations/{$invitation->id}")
        ->assertStatus(400)
        ->assertJson(['message' => 'Impossible de supprimer une invitation déjà utilisée.']);
});
