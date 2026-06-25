<?php

use App\Contracts\Payments\PaymentProvider;
use App\Contracts\Payments\PaymentProviderResolverContract;
use App\Data\Payments\PaymentResponseData;
use App\Enums\BookingStatusEnum;
use App\Enums\CurrencyEnum;
use App\Enums\PaymentProviderEnum;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\LedgerAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LedgerAccountSeeder::class);

    $this->sender = User::factory()->sender()->create([
        'verified_user' => true,
        'kyc_passed_at' => now(),
    ]);
    $this->traveler = User::factory()->traveler()->create();

    $provider = mock(PaymentProvider::class);
    $provider->shouldReceive('charge')->andReturn(new PaymentResponseData(
        provider: PaymentProviderEnum::FAKE,
        providerTransactionId: 'txn_pay_1',
        providerStatus: 'completed',
        amount: 10000,
        currency: CurrencyEnum::XOF,
        checkoutUrl: null,
        eventId: null,
        rawPayload: [],
    ));

    $resolver = mock(PaymentProviderResolverContract::class);
    $resolver->shouldReceive('resolve')->andReturn($provider);
    $resolver->shouldReceive('resolveByKey')->andReturn($provider);
    app()->instance(PaymentProviderResolverContract::class, $resolver);
});

function payableBookingForTrip(User $sender, Trip $trip): Booking
{
    $booking = Booking::factory()->create([
        'user_id'            => $sender->id,
        'trip_id'            => $trip->id,
        'status'             => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->addMinutes(15),
    ]);

    $luggage = Luggage::factory()->for($sender)->create();

    BookingItem::factory()->create([
        'booking_id'  => $booking->id,
        'trip_id'     => $trip->id,
        'luggage_id'  => $luggage->id,
        'kg_reserved' => 5,
        'price'       => 8000,
    ]);

    return $booking->fresh(['bookingItems', 'trip']);
}

it('crée la charge dans la devise du trajet (XOF), pas celle du payeur', function (): void {
    $trip    = Trip::factory()->create(['user_id' => $this->traveler->id, 'currency' => 'XOF']);
    $booking = payableBookingForTrip($this->sender, $trip);

    $this->actingAs($this->sender)
        ->postJson("/api/v1/bookings/{$booking->id}/pay", [
            'method'  => 'mobile_money',
            'country' => 'FR', // payeur en France, mais le trajet est en XOF
        ])
        ->assertCreated();

    $this->assertDatabaseHas('transactions', [
        'booking_id' => $booking->id,
        'currency'   => CurrencyEnum::XOF->value,
    ]);

    $this->assertDatabaseMissing('transactions', [
        'booking_id' => $booking->id,
        'currency'   => CurrencyEnum::EUR->value,
    ]);
});

it('crée la charge en MAD si le trajet est en MAD', function (): void {
    $trip    = Trip::factory()->create(['user_id' => $this->traveler->id, 'currency' => 'MAD']);
    $booking = payableBookingForTrip($this->sender, $trip);

    $this->actingAs($this->sender)
        ->postJson("/api/v1/bookings/{$booking->id}/pay", ['method' => 'card', 'country' => 'MA'])
        ->assertCreated();

    $this->assertDatabaseHas('transactions', [
        'booking_id' => $booking->id,
        'currency'   => CurrencyEnum::MAD->value,
    ]);
});

it('refuse le paiement si le trajet n\'a pas de devise', function (): void {
    $trip    = Trip::factory()->create(['user_id' => $this->traveler->id, 'currency' => null]);
    $booking = payableBookingForTrip($this->sender, $trip);

    $this->actingAs($this->sender)
        ->postJson("/api/v1/bookings/{$booking->id}/pay", ['method' => 'card', 'country' => 'FR'])
        ->assertStatus(422);

    $this->assertDatabaseMissing('transactions', [
        'booking_id' => $booking->id,
    ]);
});
