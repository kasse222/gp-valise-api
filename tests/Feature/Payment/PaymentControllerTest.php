<?php

use App\Models\User;
use App\Models\Payment;
use App\Models\Booking;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\CurrencyEnum;
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

// 🧱 Initialisation stable : utilisateur sender non admin
beforeEach(function () {
    // IP stable pour le throttle
    $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1']);

    // reset throttle (sinon accumulation entre tests)
    RateLimiter::clear('finance:127.0.0.1');

    $this->user = User::factory()->sender()->create([
        'role' => UserRoleEnum::SENDER->value,
        'verified_user' => true,
        'kyc_passed_at' => now(),
    ]);

    actingAs($this->user);
});


// ✅ Liste ses propres paiements
it('liste les paiements de l’utilisateur connecté', function () {
    Payment::factory()->count(3)->for($this->user)->create();
    Payment::factory()->count(2)->create(); // autres utilisateurs

    $response = getJson('/api/v1/payments');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

// ✅ Création d’un paiement valide
it('crée un paiement avec des données valides', function () {
    $booking = Booking::factory()->for($this->user)->create();

    $payload = [
        'booking_id' => $booking->id,
        'amount'     => 125.5,
        'method'     => PaymentMethodEnum::CARTE_BANCAIRE->value,
        'currency'   => CurrencyEnum::EUR->value,
        'paid_at'    => now()->toDateTimeString(),
    ];

    $response = postJson('/api/v1/payments', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.amount', 125.5)
        ->assertJsonPath('data.method', 'carte')
        ->assertJsonPath('data.currency', 'EUR')
        ->assertJsonPath('data.booking_id', $booking->id)
        ->assertJsonPath('data.user_id', $this->user->id)
        ->assertJsonPath('data.status', PaymentStatusEnum::EN_ATTENTE->value);
});

// ❌ Données invalides rejetées
it('rejette la création si les données sont invalides', function () {
    $response = postJson('/api/v1/payments', [
        'booking_id' => 999,
        'amount'     => -20,
        'method'     => 'bitcoin',
        'currency'   => 'btc',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['booking_id', 'amount', 'method', 'currency']);
});

// ✅ Lecture d’un paiement personnel
it('affiche un paiement appartenant à l’utilisateur', function () {
    $payment = Payment::factory()->for($this->user)->create();

    $response = getJson("/api/v1/payments/{$payment->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $payment->id);
});

// ❌ Lecture refusée à un autre utilisateur
it('rejette l’accès à un paiement appartenant à un autre utilisateur', function () {
    $owner = User::factory()->sender()->create();
    $payment = Payment::factory()->for($owner)->create();

    $otherUser = User::factory()->sender()->create();
    expect($otherUser->id)->not()->toBe($owner->id);

    actingAs($otherUser);
    $response = getJson("/api/v1/payments/{$payment->id}");

    $response->assertForbidden();
});

// ✅ Modification autorisée
it('modifie un paiement si autorisé', function () {
    $payment = Payment::factory()->for($this->user)->create();

    $response = patchJson("/api/v1/payments/{$payment->id}", [
        'status' => PaymentStatusEnum::SUCCES->value,
        'method' => PaymentMethodEnum::VIREMENT->value,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.status', PaymentStatusEnum::SUCCES->value)
        ->assertJsonPath('data.method', 'virement');
});

// ❌ Modification interdite à un autre utilisateur
it('rejette la modification par un autre utilisateur', function () {
    $owner = User::factory()->sender()->create();
    $payment = Payment::factory()->for($owner)->create();

    $otherUser = User::factory()->sender()->create();
    actingAs($otherUser);

    $response = patchJson("/api/v1/payments/{$payment->id}", [
        'status' => PaymentStatusEnum::ECHEC->value,
    ]);

    $response->assertForbidden();
});

// ✅ Suppression autorisée
it('supprime un paiement appartenant à l’utilisateur', function () {
    $payment = Payment::factory()->for($this->user)->create();

    $response = deleteJson("/api/v1/payments/{$payment->id}");

    $response->assertOk()
        ->assertJson(['message' => 'Paiement supprimé.']);
});

// ❌ Suppression interdite
it('rejette la suppression par un utilisateur non autorisé', function () {
    $payment = Payment::factory()->create();

    $otherUser = User::factory()->sender()->create();
    actingAs($otherUser);

    $response = deleteJson("/api/v1/payments/{$payment->id}");

    $response->assertForbidden();
});
