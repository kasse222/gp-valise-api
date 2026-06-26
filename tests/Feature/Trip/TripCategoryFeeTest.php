<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Enums\LuggageCategoryEnum;
use App\Enums\LuggageStatusEnum;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\TripCategoryFee;
use App\Models\User;
use Database\Seeders\LedgerAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LedgerAccountSeeder::class);
    $this->traveler = User::factory()->traveler()->create(['kyc_passed_at' => now()]);
    $this->sender   = User::factory()->sender()->create();
});

// ── Création trajet avec fees ──────────────────────────────────────────────

it('crée un trajet avec des category_fees', function (): void {
    $this->actingAs($this->traveler)
        ->postJson('/api/v1/trips', [
            'departure'    => 'Dakar, SN',
            'destination'  => 'Paris, FR',
            'date'         => now()->addDays(10)->toDateString(),
            'capacity'     => 20000,
            'price_per_kg' => 2000,
            'currency'     => 'XOF',
            'type_trip'    => 'standard',
            'category_fees' => [
                ['category' => 'phone',    'fee' => 5000],
                ['category' => 'computer', 'fee' => 10000],
            ],
        ])
        ->assertCreated();

    $trip = Trip::latest()->first();

    expect($trip->categoryFees)->toHaveCount(2);

    $phoneFee = $trip->categoryFees->firstWhere('category', LuggageCategoryEnum::PHONE);
    expect($phoneFee->fee)->toBe(5000);
});

it('crée un trajet sans category_fees (optionnel)', function (): void {
    $this->actingAs($this->traveler)
        ->postJson('/api/v1/trips', [
            'departure'    => 'Dakar, SN',
            'destination'  => 'Paris, FR',
            'date'         => now()->addDays(10)->toDateString(),
            'capacity'     => 20000,
            'price_per_kg' => 2000,
            'currency'     => 'XOF',
            'type_trip'    => 'standard',
        ])
        ->assertCreated();

    $trip = Trip::latest()->first();
    expect($trip->categoryFees)->toHaveCount(0);
});

it('met à jour les category_fees d\'un trajet', function (): void {
    $trip = Trip::factory()->create(['user_id' => $this->traveler->id, 'currency' => 'XOF']);
    $trip->categoryFees()->create(['category' => 'phone', 'fee' => 3000]);

    $this->actingAs($this->traveler)
        ->putJson("/api/v1/trips/{$trip->id}", [
            'category_fees' => [
                ['category' => 'phone',    'fee' => 6000],
                ['category' => 'cosmetics', 'fee' => 2000],
            ],
        ])
        ->assertOk();

    $trip->refresh()->load('categoryFees');
    expect($trip->categoryFees)->toHaveCount(2);
    expect($trip->categoryFees->firstWhere('category', LuggageCategoryEnum::PHONE)->fee)->toBe(6000);
});

it('supprime les fees si category_fees vide envoyé', function (): void {
    $trip = Trip::factory()->create(['user_id' => $this->traveler->id, 'currency' => 'XOF']);
    $trip->categoryFees()->create(['category' => 'phone', 'fee' => 3000]);

    $this->actingAs($this->traveler)
        ->putJson("/api/v1/trips/{$trip->id}", ['category_fees' => []])
        ->assertOk();

    expect($trip->fresh()->categoryFees)->toHaveCount(0);
});

it('ne touche pas aux fees si category_fees absent du PUT', function (): void {
    $trip = Trip::factory()->create(['user_id' => $this->traveler->id, 'currency' => 'XOF']);
    $trip->categoryFees()->create(['category' => 'phone', 'fee' => 3000]);

    $this->actingAs($this->traveler)
        ->putJson("/api/v1/trips/{$trip->id}", ['price_per_kg' => 2500])
        ->assertOk();

    expect($trip->fresh()->categoryFees)->toHaveCount(1);
});

// ── Calcul du prix à la réservation ───────────────────────────────────────

it('calcule le prix sans fee si aucune catégorie définie', function (): void {
    $trip = Trip::factory()->create([
        'user_id'      => $this->traveler->id,
        'price_per_kg' => 2000,
        'currency'     => 'XOF',
        'capacity'     => 20000,
    ]);

    $luggage = Luggage::factory()->for($this->sender)->create([
        'status'        => LuggageStatusEnum::EN_ATTENTE,
        'content_items' => [['category' => 'phone', 'description' => 'iPhone']],
    ]);

    $booking = app(\App\Actions\Booking\ReserveBooking::class)->execute($this->sender, [
        'trip_id'         => $trip->id,
        'recipient_name'  => 'Test',
        'recipient_phone' => '+221700000000',
        'recipient_email' => 'test@test.com',
        'items'           => [['luggage_id' => $luggage->id, 'kg_reserved' => 5000]],
    ]);

    // 5kg × 2000 = 10000, aucun fee défini → 10000
    expect($booking->bookingItems->first()->price)->toBe(10000);
});

it('ajoute le fee de catégorie au prix de base', function (): void {
    $trip = Trip::factory()->create([
        'user_id'      => $this->traveler->id,
        'price_per_kg' => 2000,
        'currency'     => 'XOF',
        'capacity'     => 20000,
    ]);
    $trip->categoryFees()->create(['category' => 'phone', 'fee' => 5000]);

    $luggage = Luggage::factory()->for($this->sender)->create([
        'status'        => LuggageStatusEnum::EN_ATTENTE,
        'content_items' => [['category' => 'phone', 'description' => 'iPhone']],
    ]);

    $booking = app(\App\Actions\Booking\ReserveBooking::class)->execute($this->sender, [
        'trip_id'         => $trip->id,
        'recipient_name'  => 'Test',
        'recipient_phone' => '+221700000000',
        'recipient_email' => 'test@test.com',
        'items'           => [['luggage_id' => $luggage->id, 'kg_reserved' => 5000]],
    ]);

    // 5kg × 2000 = 10000 + 5000 (phone fee) = 15000
    expect($booking->bookingItems->first()->price)->toBe(15000);
});

it('cumule les fees de plusieurs catégories différentes', function (): void {
    $trip = Trip::factory()->create([
        'user_id'      => $this->traveler->id,
        'price_per_kg' => 2000,
        'currency'     => 'XOF',
        'capacity'     => 20000,
    ]);
    $trip->categoryFees()->create(['category' => 'phone',    'fee' => 5000]);
    $trip->categoryFees()->create(['category' => 'computer', 'fee' => 10000]);

    $luggage = Luggage::factory()->for($this->sender)->create([
        'status'        => LuggageStatusEnum::EN_ATTENTE,
        'content_items' => [
            ['category' => 'phone',    'description' => 'iPhone'],
            ['category' => 'computer', 'description' => 'MacBook'],
        ],
    ]);

    $booking = app(\App\Actions\Booking\ReserveBooking::class)->execute($this->sender, [
        'trip_id'         => $trip->id,
        'recipient_name'  => 'Test',
        'recipient_phone' => '+221700000000',
        'recipient_email' => 'test@test.com',
        'items'           => [['luggage_id' => $luggage->id, 'kg_reserved' => 5000]],
    ]);

    // 5kg × 2000 = 10000 + 5000 (phone) + 10000 (computer) = 25000
    expect($booking->bookingItems->first()->price)->toBe(25000);
});

it('applique le fee pour chaque article de même catégorie (cumulatif)', function (): void {
    $trip = Trip::factory()->create([
        'user_id'      => $this->traveler->id,
        'price_per_kg' => 2000,
        'currency'     => 'XOF',
        'capacity'     => 20000,
    ]);
    $trip->categoryFees()->create(['category' => 'phone', 'fee' => 5000]);

    $luggage = Luggage::factory()->for($this->sender)->create([
        'status'        => LuggageStatusEnum::EN_ATTENTE,
        'content_items' => [
            ['category' => 'phone', 'description' => 'iPhone'],
            ['category' => 'phone', 'description' => 'Samsung'],
        ],
    ]);

    $booking = app(\App\Actions\Booking\ReserveBooking::class)->execute($this->sender, [
        'trip_id'         => $trip->id,
        'recipient_name'  => 'Test',
        'recipient_phone' => '+221700000000',
        'recipient_email' => 'test@test.com',
        'items'           => [['luggage_id' => $luggage->id, 'kg_reserved' => 5000]],
    ]);

    // 5kg × 2000 = 10000 + 5000 (iPhone) + 5000 (Samsung) = 20000
    expect($booking->bookingItems->first()->price)->toBe(20000);
});
