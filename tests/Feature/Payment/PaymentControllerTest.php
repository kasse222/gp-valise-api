<?php

use App\Models\User;
use App\Models\Payment;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;
use Illuminate\Support\Facades\RateLimiter;
use App\Enums\UserRoleEnum;

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

beforeEach(function () {
    $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1']);
    RateLimiter::clear('finance:127.0.0.1');

    $this->user = User::factory()->sender()->create([
        'role' => UserRoleEnum::SENDER->value,
        'verified_user' => true,
        'kyc_passed_at' => now(),
    ]);

    actingAs($this->user);
});

it('liste les paiements de l’utilisateur connecté', function () {
    Payment::factory()->count(3)->for($this->user)->create();
    Payment::factory()->count(2)->create();

    $response = getJson('/api/v1/payments');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('affiche un paiement appartenant à l’utilisateur', function () {
    $payment = Payment::factory()->for($this->user)->create();

    $response = getJson("/api/v1/payments/{$payment->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $payment->id);
});

it('rejette l’accès à un paiement appartenant à un autre utilisateur', function () {
    $owner = User::factory()->sender()->create();
    $payment = Payment::factory()->for($owner)->create();

    $otherUser = User::factory()->sender()->create([
        'verified_user' => true,
        'kyc_passed_at' => now(),
    ]);
    actingAs($otherUser);

    $response = getJson("/api/v1/payments/{$payment->id}");

    $response->assertForbidden();
});

it('retourne 405 sur la création de paiement car seule la lecture est autorisée', function () {
    $response = postJson('/api/v1/payments', [
        'booking_id' => 999,
        'amount' => 125.5,
        'method' => 'carte',
        'currency' => 'EUR',
    ]);

    $response->assertStatus(405);
});

it('retourne 405 sur la modification de paiement car seule la lecture est autorisée', function () {
    $payment = Payment::factory()->for($this->user)->create();

    $response = patchJson("/api/v1/payments/{$payment->id}", [
        'status' => 2,
    ]);

    $response->assertStatus(405);
});

it('retourne 405 sur la suppression de paiement car seule la lecture est autorisée', function () {
    $payment = Payment::factory()->for($this->user)->create();

    $response = deleteJson("/api/v1/payments/{$payment->id}");

    $response->assertStatus(405);
});
