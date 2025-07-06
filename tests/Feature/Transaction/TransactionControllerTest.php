<?php

use App\Models\User;
use App\Models\Transaction;
use App\Enums\TransactionStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\CurrencyEnum;
use App\Models\Booking;

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

// ✅ it liste les transactions de l’utilisateur
it('liste les transactions de l’utilisateur connecté', function () {
    Transaction::factory()->count(3)->for($this->user)->create();
    Transaction::factory()->count(2)->create(); // autres utilisateurs

    $response = getJson('/api/v1/transactions');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

// ✅ it affiche une transaction de l’utilisateur
it('affiche une transaction appartenant à l’utilisateur', function () {
    $transaction = Transaction::factory()->for($this->user)->create();

    $response = getJson("/api/v1/transactions/{$transaction->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $transaction->id);
});

// ✅ it rejette l’accès à une transaction d’un autre utilisateur
it('rejette l’accès à une transaction d’un autre utilisateur', function () {
    $otherUser = User::factory()->verified()->create();
    $transaction = Transaction::factory()->for($otherUser)->create();

    $response = getJson("/api/v1/transactions/{$transaction->id}");

    $response->assertForbidden();
});

// ✅ it crée une transaction valide
it('crée une transaction avec des données valides', function () {
    $booking = Booking::factory()->for($this->user)->create(); // ✅ Ajout

    $payload = [
        'booking_id' => $booking->id, // ✅ Important
        'amount'     => 120.50,
        'currency'   => CurrencyEnum::EUR->value,
        'status'     => TransactionStatusEnum::PENDING->value,
        'method'     => PaymentMethodEnum::CARTE_BANCAIRE->value,

    ];

    $response = postJson('/api/v1/transactions', $payload);

    $data = $response->json('data');
    //  dump($response->json());
    // ✅ Comparaison explicite, plus fiable que assertJsonPath
    expect($data['amount'])->toBeFloat()->and($data['amount'])->toBe((float) $payload['amount']);
});

// ✅ it rejette une transaction invalide
it('rejette la création si les données sont invalides', function () {
    $response = postJson('/api/v1/transactions', []);

    $response->assertStatus(422);
});

// ✅ it rejette la création si non vérifié
it('rejette la création si l’utilisateur n’est pas vérifié', function () {
    $unverifiedUser = User::factory()->create(['verified_user' => false]);
    actingAs($unverifiedUser);

    $booking = Booking::factory()->for($unverifiedUser)->create(); // ✅ Ajout

    $response = postJson('/api/v1/transactions', [
        'booking_id' => $booking->id, // ✅ Ajouté
        'amount'     => 120.50,
        'currency'   => CurrencyEnum::EUR->value,
        'status'     => TransactionStatusEnum::PENDING->value,
        'method'     => PaymentMethodEnum::CARTE_BANCAIRE->value,
        'booking_id' => $booking->id, // ✅ obligatoire dans FormRequest

    ]);

    $response->assertForbidden(); // ✅ Devrait passer
});


// ✅ it refuse un remboursement non autorisé
it('rejette le remboursement par un utilisateur non autorisé', function () {
    $otherUser = User::factory()->verified()->create();
    $transaction = Transaction::factory()
        ->for($otherUser)
        ->state(['status' => TransactionStatusEnum::PROCESSING])
        ->create();

    $response = postJson("/api/v1/transactions/{$transaction->id}/refund");

    $response->assertForbidden();
});
