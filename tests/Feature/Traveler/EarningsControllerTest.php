<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Enums\CurrencyEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\LedgerAccountSeeder::class);

    $this->traveler = User::factory()->traveler()->create();
    $this->sender   = User::factory()->sender()->create();
    $this->trip     = Trip::factory()->create(['user_id' => $this->traveler->id]);
});

function earnsBooking(BookingStatusEnum $status): Booking
{
    return Booking::factory()
        ->for(test()->sender)
        ->for(test()->trip)
        ->create(['status' => $status]);
}

function earnsCharge(Booking $booking, int $amount, CurrencyEnum $currency = CurrencyEnum::EUR): Transaction
{
    return Transaction::factory()->create([
        'user_id'    => test()->sender->id,
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::CHARGE,
        'status'     => TransactionStatusEnum::COMPLETED,
        'amount'     => $amount,
        'currency'   => $currency->value,
    ]);
}

function earnsPayout(int $amount, TransactionStatusEnum $status, CurrencyEnum $currency = CurrencyEnum::EUR): Transaction
{
    return Transaction::factory()->create([
        'user_id'  => test()->traveler->id,
        'type'     => TransactionTypeEnum::PAYOUT,
        'status'   => $status,
        'amount'   => $amount,
        'currency' => $currency->value,
    ]);
}

it('retourne 401 si non authentifié', function (): void {
    $this->getJson('/api/v1/me/earnings')->assertStatus(401);
});

it('retourne 403 si rôle sender', function (): void {
    $this->actingAs($this->sender)
        ->getJson('/api/v1/me/earnings')
        ->assertStatus(403);
});

it('retourne data vide si aucune transaction', function (): void {
    $this->actingAs($this->traveler)
        ->getJson('/api/v1/me/earnings')
        ->assertOk()
        ->assertJson(['data' => []]);
});

it('compte les charges COMPLETED sur bookings CONFIRMEE comme escrow', function (): void {
    earnsCharge(earnsBooking(BookingStatusEnum::CONFIRMEE), 5000);

    $this->actingAs($this->traveler)
        ->getJson('/api/v1/me/earnings')
        ->assertOk()
        ->assertJsonFragment(['currency' => 'EUR', 'escrow' => 5000, 'pending' => 0, 'paid' => 0]);
});

it('compte les charges COMPLETED sur bookings EN_TRANSIT comme escrow', function (): void {
    earnsCharge(earnsBooking(BookingStatusEnum::EN_TRANSIT), 3000);

    $this->actingAs($this->traveler)
        ->getJson('/api/v1/me/earnings')
        ->assertOk()
        ->assertJsonFragment(['currency' => 'EUR', 'escrow' => 3000, 'pending' => 0, 'paid' => 0]);
});

it('ne compte pas les charges sur bookings LIVREE dans escrow', function (): void {
    earnsCharge(earnsBooking(BookingStatusEnum::LIVREE), 5000);

    $data = collect(
        $this->actingAs($this->traveler)
            ->getJson('/api/v1/me/earnings')
            ->assertOk()
            ->json('data')
    );

    expect($data->firstWhere('currency', 'EUR'))->toBeNull();
});

it('compte les payouts PENDING dans pending', function (): void {
    earnsPayout(8000, TransactionStatusEnum::PENDING);

    $this->actingAs($this->traveler)
        ->getJson('/api/v1/me/earnings')
        ->assertOk()
        ->assertJsonFragment(['currency' => 'EUR', 'escrow' => 0, 'pending' => 8000, 'paid' => 0]);
});

it('compte les payouts PROCESSING dans pending', function (): void {
    earnsPayout(4000, TransactionStatusEnum::PROCESSING);

    $this->actingAs($this->traveler)
        ->getJson('/api/v1/me/earnings')
        ->assertOk()
        ->assertJsonFragment(['pending' => 4000]);
});

it('compte les payouts COMPLETED dans paid', function (): void {
    earnsPayout(12000, TransactionStatusEnum::COMPLETED);

    $this->actingAs($this->traveler)
        ->getJson('/api/v1/me/earnings')
        ->assertOk()
        ->assertJsonFragment(['currency' => 'EUR', 'escrow' => 0, 'pending' => 0, 'paid' => 12000]);
});

it('agrège plusieurs transactions dans le même bucket devise', function (): void {
    earnsCharge(earnsBooking(BookingStatusEnum::CONFIRMEE), 3000);
    earnsCharge(earnsBooking(BookingStatusEnum::EN_TRANSIT), 2000);
    earnsPayout(5000, TransactionStatusEnum::PENDING);
    earnsPayout(7000, TransactionStatusEnum::COMPLETED);

    $this->actingAs($this->traveler)
        ->getJson('/api/v1/me/earnings')
        ->assertOk()
        ->assertJsonFragment(['currency' => 'EUR', 'escrow' => 5000, 'pending' => 5000, 'paid' => 7000]);
});

it('sépare XOF et EUR sans jamais les sommer', function (): void {
    earnsCharge(earnsBooking(BookingStatusEnum::CONFIRMEE), 40000, CurrencyEnum::XOF);
    earnsPayout(30000, TransactionStatusEnum::COMPLETED, CurrencyEnum::XOF);
    earnsPayout(4500, TransactionStatusEnum::PENDING, CurrencyEnum::EUR);

    $data = collect(
        $this->actingAs($this->traveler)
            ->getJson('/api/v1/me/earnings')
            ->assertOk()
            ->json('data')
    );

    $xof = $data->firstWhere('currency', 'XOF');
    $eur = $data->firstWhere('currency', 'EUR');

    expect($xof['escrow'])->toBe(40000)
        ->and($xof['pending'])->toBe(0)
        ->and($xof['paid'])->toBe(30000)
        ->and($eur['escrow'])->toBe(0)
        ->and($eur['pending'])->toBe(4500)
        ->and($eur['paid'])->toBe(0);
});

it("n'inclut pas les escrows des trajets d'un autre voyageur", function (): void {
    $autreVoyageur = User::factory()->traveler()->create();
    $autreTrip     = Trip::factory()->create(['user_id' => $autreVoyageur->id]);
    $booking       = Booking::factory()
        ->for($this->sender)
        ->for($autreTrip)
        ->create(['status' => BookingStatusEnum::CONFIRMEE]);
    earnsCharge($booking, 9000);

    expect(
        $this->actingAs($this->traveler)
            ->getJson('/api/v1/me/earnings')
            ->assertOk()
            ->json('data')
    )->toBeEmpty();
});
