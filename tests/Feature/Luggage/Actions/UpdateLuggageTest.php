<?php

declare(strict_types=1);

use App\Actions\Luggage\CreateLuggage;
use App\Enums\LuggageStatusEnum;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('crée une valise avec les données valides', function (): void {
    $user = User::factory()->sender()->create();
    $trip = Trip::factory()->for($user)->create();

    $data = [
        'weight_kg'     => 250,        // ← kg×10 : 250 = 25kg
        'length_cm'     => 60,
        'width_cm'      => 40,
        'height_cm'     => 30,
        'pickup_city'   => 'Toulouse',
        'delivery_city' => 'Bamako',
        'pickup_date'   => now()->addDays(2)->toDateString(),
        'delivery_date' => now()->addDays(5)->toDateString(),
        'trip_id'       => $trip->id,
    ];

    $luggage = CreateLuggage::execute($user, $data);

    expect($luggage)
        ->user_id->toBe($user->id)
        ->trip_id->toBe($trip->id)
        ->status->toBe(LuggageStatusEnum::EN_ATTENTE)
        ->pickup_city->toBe('Toulouse')
        ->delivery_city->toBe('Bamako');
});

it('calcule automatiquement le volume du colis à la sauvegarde', function (): void {
    $luggage = Luggage::factory()->create([
        'length_cm' => 10,
        'width_cm'  => 20,
        'height_cm' => 30,
    ]);

    expect($luggage->fresh()->volume_cm3)->toBe(6000); // ← float → integer
});
