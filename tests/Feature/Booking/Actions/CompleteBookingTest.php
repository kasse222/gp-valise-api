<?php

declare(strict_types=1);

use App\Actions\Booking\CompleteBooking;
use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Events\BookingDelivered;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('gpvalise.fee_percentage', 10);
    config()->set('gpvalise.payment_fee_percentage', 2);
});

it('livre une réservation avec succès', function (): void {
    $voyageur   = User::factory()->traveler()->verified()->create();
    $trip       = Trip::factory()->create(['user_id' => $voyageur->id]);
    $expediteur = User::factory()->sender()->verified()->create();

    $booking = Booking::factory()->create([
        'user_id'            => $expediteur->id,
        'trip_id'            => $trip->id,
        'status'             => BookingStatusEnum::EN_TRANSIT,
        'delivery_code'      => '123456',
        'delivery_qr_token'  => 'test-qr-token-uuid',
        'handed_over_at'     => now()->subHour(),
    ]);

    actingAs($voyageur);

    $result = app(CompleteBooking::class)->execute($booking, $voyageur, '123456');
    $result->load('statusHistories');

    expect($result->status)->toBe(BookingStatusEnum::LIVREE)
        ->and($result->statusHistories->last()->new_status)->toBe(BookingStatusEnum::LIVREE);
});

it('dispatch BookingDelivered lorsqu une réservation est livrée', function (): void {
    Event::fake();

    $voyageur   = User::factory()->traveler()->verified()->create();
    $trip       = Trip::factory()->create(['user_id' => $voyageur->id]);
    $expediteur = User::factory()->sender()->verified()->create();

    $booking = Booking::factory()->create([
        'user_id'            => $expediteur->id,
        'trip_id'            => $trip->id,
        'status'             => BookingStatusEnum::EN_TRANSIT,
        'delivery_code'      => '654321',
        'delivery_qr_token'  => 'test-qr-token-uuid-2',
        'handed_over_at'     => now()->subHour(),
    ]);

    actingAs($voyageur);

    $result = app(CompleteBooking::class)->execute($booking, $voyageur, '654321');

    Event::assertDispatched(BookingDelivered::class, function (BookingDelivered $event) use ($result): bool {
        return $event->booking->id === $result->id
            && $event->booking->status === BookingStatusEnum::LIVREE;
    });
});

it('crée automatiquement un payout pending et une commission lorsqu une réservation est livrée', function (): void {
    $voyageur   = User::factory()->traveler()->verified()->create();
    $trip       = Trip::factory()->create(['user_id' => $voyageur->id]);
    $expediteur = User::factory()->sender()->verified()->create();

    $booking = Booking::factory()->create([
        'user_id'            => $expediteur->id,
        'trip_id'            => $trip->id,
        'status'             => BookingStatusEnum::EN_TRANSIT,
        'delivery_code'      => '999888',
        'delivery_qr_token'  => 'test-qr-token-uuid-3',
        'handed_over_at'     => now()->subHour(),
    ]);

    Transaction::factory()->create([
        'user_id'      => $expediteur->id,
        'booking_id'   => $booking->id,
        'type'         => TransactionTypeEnum::CHARGE,
        'status'       => TransactionStatusEnum::COMPLETED,
        'amount'       => 12050,
        'processed_at' => now(),
    ]);

    actingAs($voyageur);

    $result = app(CompleteBooking::class)->execute($booking, $voyageur, '999888');
    $result->refresh();

    // Payout NON créé immédiatement — escrow en attente
    $payout = Transaction::query()
        ->where('booking_id', $booking->id)
        ->where('type', TransactionTypeEnum::PAYOUT)
        ->first();

    expect($payout)->toBeNull()
        ->and($result->status)->toBe(BookingStatusEnum::LIVREE)
        ->and($result->delivered_at)->not->toBeNull()
        ->and($result->escrow_releasable_at)->not->toBeNull()
        ->and($result->escrow_releasable_at->isAfter(now()))->toBeTrue()
        ->and($result->disputed_at)->toBeNull();
});

it('ne crée pas de deuxième payout si un payout existe déjà', function (): void {
    $voyageur   = User::factory()->traveler()->verified()->create();
    $trip       = Trip::factory()->create(['user_id' => $voyageur->id]);
    $expediteur = User::factory()->sender()->verified()->create();

    $booking = Booking::factory()->create([
        'user_id'              => $expediteur->id,
        'trip_id'              => $trip->id,
        'status'               => BookingStatusEnum::LIVREE,
        'delivered_at'         => now()->subHours(49),
        'escrow_releasable_at' => now()->subHours(1),
    ]);

    Transaction::factory()->create([
        'user_id'      => $expediteur->id,
        'booking_id'   => $booking->id,
        'type'         => TransactionTypeEnum::CHARGE,
        'status'       => TransactionStatusEnum::COMPLETED,
        'amount'       => 12050,
        'processed_at' => now(),
    ]);

    Transaction::factory()->create([
        'user_id'    => $voyageur->id,
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::PAYOUT,
        'status'     => TransactionStatusEnum::PENDING,
        'amount'     => 10845,
    ]);

    expect($booking->hasPayoutTransaction())->toBeTrue();
});
