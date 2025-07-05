<?php

use App\Models\User;
use App\Models\Payment;
use App\Models\Booking;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\CurrencyEnum;
use function Pest\Laravel\actingAs;

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

// ✅ it liste les paiements de l’utilisateur
it('liste les paiements de l’utilisateur connecté', function () {
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    Payment::factory()->count(3)->for($user)->create();
    Payment::factory()->count(2)->create(); // autres utilisateurs

    $response = actingAs($user)->getJson('/api/v1/payments');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

// ✅ it crée un paiement valide
it('crée un paiement avec des données valides', function () {
    /** @var \App\Models\User $user */

    $user = User::factory()->create();
    $booking = Booking::factory()->for($user)->create();

    $payload = [
        'booking_id' => $booking->id,
        'amount'     => 125.5,
        'method'     => PaymentMethodEnum::CARTE_BANCAIRE->value,
        'currency'   => CurrencyEnum::EUR->value,
        'paid_at'    => now()->toDateTimeString(),
    ];

    $response = actingAs($user)->postJson('/api/v1/payments', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.amount', 125.5)
        ->assertJsonPath('data.method', 'carte')
        ->assertJsonPath('data.currency', 'EUR')
        ->assertJsonPath('data.booking_id', $booking->id)
        ->assertJsonPath('data.user_id', $user->id)
        ->assertJsonPath('data.status', PaymentStatusEnum::EN_ATTENTE->value);
});



// ❌ données invalides
it('rejette la création si les données sont invalides', function () {
    /** @var \App\Models\User $user */
    $user = User::factory()->create();

    $response = actingAs($user)->postJson('/api/v1/payments', [
        'booking_id' => 999,
        'amount'     => -20,
        'method'     => 'bitcoin',
        //   'status'     => 999,
        'currency'   => 'btc',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['booking_id', 'amount', 'method', 'currency']);
});

// ✅ show autorisé
it('affiche un paiement appartenant à l’utilisateur', function () {
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $payment = Payment::factory()->for($user)->create();

    $response = actingAs($user)->getJson("/api/v1/payments/{$payment->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $payment->id);
});

// ❌ show interdit
it('rejette l’accès à un paiement appartenant à un autre utilisateur', function () {
    $payment = Payment::factory()->create();
    /** @var \App\Models\User $user */
    $user = User::factory()->create();

    $response = actingAs($user)->getJson("/api/v1/payments/{$payment->id}");

    $response->assertForbidden();
});

// ✅ update autorisé
it('modifie un paiement si autorisé', function () {
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $payment = Payment::factory()->for($user)->create();

    $response = actingAs($user)->patchJson("/api/v1/payments/{$payment->id}", [
        'status' => PaymentStatusEnum::SUCCES->value,
        'method' => PaymentMethodEnum::VIREMENT->value,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.status', PaymentStatusEnum::SUCCES->value)
        ->assertJsonPath('data.method', 'virement');
});

// ❌ update interdit
it('rejette la modification par un autre utilisateur', function () {
    $payment = Payment::factory()->create();
    /** @var \App\Models\User $user */
    $user = User::factory()->create();

    $response = actingAs($user)->patchJson("/api/v1/payments/{$payment->id}", [
        'status' => PaymentStatusEnum::ECHEC->value,
    ]);

    $response->assertForbidden();
});

// ✅ delete autorisé
it('supprime un paiement appartenant à l’utilisateur', function () {
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $payment = Payment::factory()->for($user)->create();

    $response = actingAs($user)->deleteJson("/api/v1/payments/{$payment->id}");

    $response->assertOk()
        ->assertJson(['message' => 'Paiement supprimé.']);
});

// ❌ delete interdit
it('rejette la suppression par un utilisateur non autorisé', function () {
    $payment = Payment::factory()->create();
    /** @var \App\Models\User $user */
    $user = User::factory()->create();

    $response = actingAs($user)->deleteJson("/api/v1/payments/{$payment->id}");

    $response->assertForbidden();
});
