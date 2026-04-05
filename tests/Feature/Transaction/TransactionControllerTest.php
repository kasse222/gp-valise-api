<?php

use App\Models\User;
use App\Models\Transaction;
use App\Models\Booking;
use App\Enums\TransactionStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\CurrencyEnum;
use App\Enums\TransactionTypeEnum;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

beforeEach(function () {
    $this->user = User::factory()->verified()->create([
        'role' => \App\Enums\UserRoleEnum::SENDER->value,
    ]);
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
    $user = User::factory()->verified()->create([
        'role' => \App\Enums\UserRoleEnum::SENDER->value,
    ]);

    // Crée une transaction avec un booking explicitement lié au user (méthode fiable)
    $transaction = Transaction::factory()->forUserWithBooking($user)->create();

    $response = actingAs($user)->getJson("/api/v1/transactions/{$transaction->id}");
    $response->assertOk()
        ->assertJsonPath('data.id', $transaction->id);
});

it('rejette l’accès à une transaction d’un autre utilisateur', function () {
    $owner = User::factory()->verified()->create([
        'role' => \App\Enums\UserRoleEnum::SENDER->value,
    ]);
    $other = User::factory()->verified()->create([
        'role' => \App\Enums\UserRoleEnum::SENDER->value,
    ]);

    $transaction = Transaction::factory()->forUserWithBooking($owner)->create();

    $response = actingAs($other)->getJson("/api/v1/transactions/{$transaction->id}");

    $response->assertForbidden();
});

it('crée une transaction avec des données valides', function () {
    $booking = Booking::factory()->for($this->user)->create([
        'status' => \App\Enums\BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->addMinutes(15),
    ]);

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
    /** @var \App\Models\User $unverifiedUser */
    $unverifiedUser = User::factory()->create([
        'verified_user' => false,
    ]);

    $booking = Booking::factory()->for($unverifiedUser)->create([
        'status' => \App\Enums\BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->addMinutes(15),
    ]);

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
    $owner = User::factory()->verified()->create([
        'role' => \App\Enums\UserRoleEnum::SENDER->value,
    ]);
    $other = User::factory()->verified()->create([
        'role' => \App\Enums\UserRoleEnum::SENDER->value,
    ]);

    $transaction = Transaction::factory()
        ->forUserWithBooking($owner)
        ->create([
            'type' => TransactionTypeEnum::CHARGE->value,
            'status' => TransactionStatusEnum::COMPLETED->value,
        ]);

    actingAs($other);

    postJson("/api/v1/transactions/{$transaction->id}/refund", [
        'reason' => 'Demande de remboursement complète'
    ])->assertForbidden();
});

it('autorise le remboursement par le propriétaire', function () {
    $user = User::factory()->verified()->create([
        'role' => \App\Enums\UserRoleEnum::SENDER->value,
    ]);

    $booking = Booking::factory()->for($user)->create([
        'status' => \App\Enums\BookingStatusEnum::EN_LITIGE,
    ]);

    $charge = Transaction::factory()->create([
        'user_id'    => $user->id,
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::CHARGE->value,
        'status'     => TransactionStatusEnum::COMPLETED->value,
        'amount'     => 150.00,
    ]);

    actingAs($user);

    $response = postJson("/api/v1/transactions/{$charge->id}/refund", [
        'reason' => 'Valide',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.type.code', TransactionTypeEnum::REFUND->value)
        ->assertJsonPath('data.status.code', TransactionStatusEnum::COMPLETED->value)
        ->assertJsonPath('data.amount', fn($amount) => (float) $amount === 150.0);

    $charge->refresh();

    expect($charge->type)->toBe(TransactionTypeEnum::CHARGE)
        ->and($charge->status)->toBe(TransactionStatusEnum::COMPLETED);

    $refund = Transaction::query()
        ->where('booking_id', $charge->booking_id)
        ->where('type', TransactionTypeEnum::REFUND)
        ->latest()
        ->first();

    expect($refund)->not->toBeNull()
        ->and($refund->status)->toBe(TransactionStatusEnum::COMPLETED)
        ->and((float) $refund->amount)->toBe(150.0)
        ->and($refund->user_id)->toBe($user->id);
});

it('rejette le refund si user non vérifié (403)', function () {
    $user = User::factory()->create([
        'role' => \App\Enums\UserRoleEnum::SENDER->value,
        'verified_user' => false,
        'kyc_passed_at' => now(),
    ]);
    expect($user->verified_user)->toBeFalse();

    $transaction = Transaction::factory()
        ->forUserWithBooking($user)
        ->create([
            'type' => TransactionTypeEnum::CHARGE->value,
            'status' => TransactionStatusEnum::COMPLETED->value,
        ]);

    actingAs($user);

    postJson("/api/v1/transactions/{$transaction->id}/refund", [
        'reason' => 'Test',
    ])->assertForbidden();
});

it('rejette le refund si user sans KYC (403)', function () {
    $user = User::factory()->create([
        'role' => \App\Enums\UserRoleEnum::SENDER->value,
        'verified_user' => true,
        'kyc_passed_at' => null,
    ]);
    expect($user->kyc_passed_at)->toBeNull();

    $transaction = Transaction::factory()
        ->forUserWithBooking($user)
        ->create([
            'type' => TransactionTypeEnum::CHARGE->value,
            'status' => TransactionStatusEnum::COMPLETED->value,
        ]);

    actingAs($user);

    postJson("/api/v1/transactions/{$transaction->id}/refund", [
        'reason' => 'Test',
    ])->assertForbidden();
});

it('throttle refund: 6 appels => 429 au 6e', function () {
    $user = User::factory()->create([
        'role' => \App\Enums\UserRoleEnum::SENDER->value,
        'verified_user' => true,
        'kyc_passed_at' => now(),
    ]);

    actingAs($user);

    $transactions = collect();

    foreach (range(1, 6) as $i) {
        $booking = Booking::factory()->for($user)->create([
            'status' => \App\Enums\BookingStatusEnum::EN_LITIGE,
        ]);

        $transactions->push(
            Transaction::factory()->create([
                'user_id' => $user->id,
                'booking_id' => $booking->id,
                'type' => TransactionTypeEnum::CHARGE->value,
                'status' => TransactionStatusEnum::COMPLETED->value,
                'amount' => 150.00,
            ])
        );
    }

    foreach ($transactions->take(5) as $transaction) {
        postJson("/api/v1/transactions/{$transaction->id}/refund", [
            'reason' => 'Throttle test',
        ])->assertOk();
    }

    postJson("/api/v1/transactions/{$transactions->last()->id}/refund", [
        'reason' => 'Throttle test',
    ])->assertStatus(429);
});
