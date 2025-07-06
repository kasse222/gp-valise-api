<?php

use App\Models\User;
use App\Models\Transaction;
use App\Models\Booking;
use App\Enums\TransactionStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\CurrencyEnum;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

beforeEach(function () {
    $this->user = User::factory()->verified()->create();
    actingAs($this->user);
});

it('liste les transactions de l’utilisateur connecté', function () {
    $user = User::factory()->verified()->create();
    actingAs($user);

    Transaction::factory()->count(3)
        ->forUserWithBooking($user)
        ->create();

    Transaction::factory()->count(2)
        ->forUserWithBooking(User::factory()->verified()->create())
        ->create();

    $response = getJson('/api/v1/transactions');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('affiche une transaction appartenant à l’utilisateur', function () {
    $user = User::factory()->verified()->create();

    // Crée une transaction avec un booking explicitement lié au user (méthode fiable)
    $transaction = Transaction::factory()->forUserWithBooking($user)->create();

    $response = actingAs($user)->getJson("/api/v1/transactions/{$transaction->id}");
    $response->assertOk()
        ->assertJsonPath('data.id', $transaction->id);
});

it('rejette l’accès à une transaction d’un autre utilisateur', function () {
    $owner = User::factory()->verified()->create();
    $other = User::factory()->verified()->create();

    $transaction = Transaction::factory()->forUserWithBooking($owner)->create();

    $response = actingAs($other)->getJson("/api/v1/transactions/{$transaction->id}");

    $response->assertForbidden();
});

it('crée une transaction avec des données valides', function () {
    $booking = Booking::factory()->for($this->user)->create();

    $payload = [
        'booking_id' => $booking->id,
        'amount'     => 120.50,
        'currency'   => CurrencyEnum::EUR->value,
        'status'     => TransactionStatusEnum::PENDING->value,
        'method'     => PaymentMethodEnum::CARTE_BANCAIRE->value,
    ];

    $response = postJson('/api/v1/transactions', $payload);

    $response->assertCreated();
    $data = $response->json('data');

    expect($data['amount'])->toBeFloat()
        ->and($data['amount'])->toBe((float) $payload['amount']);
});

it('rejette la création si les données sont invalides', function () {
    $response = postJson('/api/v1/transactions', []);
    $response->assertStatus(422);
});

it('rejette la création si l’utilisateur n’est pas vérifié', function () {
    $unverifiedUser = User::factory()->create(['verified_user' => false]);
    $booking = Booking::factory()->for($unverifiedUser)->create();

    actingAs($unverifiedUser);

    $response = postJson('/api/v1/transactions', [
        'booking_id' => $booking->id,
        'amount'     => 120.50,
        'currency'   => CurrencyEnum::EUR->value,
        'status'     => TransactionStatusEnum::PENDING->value,
        'method'     => PaymentMethodEnum::CARTE_BANCAIRE->value,
    ]);

    $response->assertForbidden();
});

it('rejette le remboursement par un utilisateur non autorisé', function () {
    $owner = User::factory()->verified()->create();
    $other = User::factory()->verified()->create();

    $transaction = Transaction::factory()
        ->forUserWithBooking($owner)
        ->create();

    actingAs($other);
    $response = postJson("/api/v1/transactions/{$transaction->id}/refund", [
        'reason' => 'Demande de remboursement complète'
    ]);

    $response->assertForbidden();
});

it('autorise le remboursement par le propriétaire', function () {
    $user = User::factory()->verified()->create();
    $transaction = Transaction::factory()->forUserWithBooking($user)->create();

    actingAs($user);
    $response = postJson("/api/v1/transactions/{$transaction->id}/refund", [
        'reason' => 'Valide'
    ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Transaction remboursée avec succès.');
});

it('valide que le lien user-booking-transaction est cohérent', function () {
    $user = User::factory()->verified()->create();
    $transaction = Transaction::factory()->forUserWithBooking($user)->create();
    $booking = $transaction->booking;

    expect($transaction->user_id)->toBe($user->id)
        ->and($booking->user_id)->toBe($user->id)
        ->and($booking->id)->toBe($transaction->booking_id);
});
