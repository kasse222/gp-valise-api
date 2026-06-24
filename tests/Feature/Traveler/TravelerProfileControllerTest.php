<?php

declare(strict_types=1);

use App\Enums\TripStatusEnum;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->traveler = User::factory()->traveler()->create([
        'country'       => 'SN',
        'kyc_passed_at' => now(),
    ]);
    $this->sender = User::factory()->sender()->create();
});

it('retourne le profil public d\'un voyageur sans authentification', function (): void {
    $this->getJson("/api/v1/travelers/{$this->traveler->id}")
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'first_name',
                'country',
                'member_since',
                'kyc_verified',
                'active_trips_count',
                'total_trips_count',
                'active_trips',
            ],
        ]);
});

it('expose kyc_verified true si KYC validé', function (): void {
    $this->getJson("/api/v1/travelers/{$this->traveler->id}")
        ->assertOk()
        ->assertJsonPath('data.kyc_verified', true);
});

it('expose kyc_verified false si KYC absent', function (): void {
    $sanKyc = User::factory()->traveler()->create(['kyc_passed_at' => null]);

    $this->getJson("/api/v1/travelers/{$sanKyc->id}")
        ->assertOk()
        ->assertJsonPath('data.kyc_verified', false);
});

it('n\'expose pas l\'email ni le téléphone', function (): void {
    $data = $this->getJson("/api/v1/travelers/{$this->traveler->id}")
        ->assertOk()
        ->json('data');

    expect($data)->not->toHaveKey('email')
        ->and($data)->not->toHaveKey('phone')
        ->and($data)->not->toHaveKey('kyc_passed_at');
});

it('retourne 404 si le user est un sender', function (): void {
    $this->getJson("/api/v1/travelers/{$this->sender->id}")
        ->assertNotFound();
});

it('retourne 404 si user inexistant', function (): void {
    $this->getJson('/api/v1/travelers/99999')
        ->assertNotFound();
});

it('compte les trajets actifs correctement', function (): void {
    Trip::factory()->count(2)->create([
        'user_id' => $this->traveler->id,
        'status'  => TripStatusEnum::ACTIVE,
    ]);
    Trip::factory()->create([
        'user_id' => $this->traveler->id,
        'status'  => TripStatusEnum::PENDING,
    ]);

    $this->getJson("/api/v1/travelers/{$this->traveler->id}")
        ->assertOk()
        ->assertJsonPath('data.active_trips_count', 2)
        ->assertJsonPath('data.total_trips_count', 3);
});

it('liste uniquement les trajets actifs dans active_trips', function (): void {
    Trip::factory()->count(2)->create([
        'user_id' => $this->traveler->id,
        'status'  => TripStatusEnum::ACTIVE,
    ]);
    Trip::factory()->create([
        'user_id' => $this->traveler->id,
        'status'  => TripStatusEnum::PENDING,
    ]);

    $response = $this->getJson("/api/v1/travelers/{$this->traveler->id}")
        ->assertOk();

    expect($response->json('data.active_trips'))->toHaveCount(2);
});

it('est accessible avec authentification également', function (): void {
    $this->actingAs($this->sender)
        ->getJson("/api/v1/travelers/{$this->traveler->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $this->traveler->id);
});
