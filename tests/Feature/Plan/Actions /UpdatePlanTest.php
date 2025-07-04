<?php

use App\Actions\Plan\UpdatePlan;
use App\Enums\PlanTypeEnum;
use App\Models\Plan;
use Faker\Factory as FakerFactory;

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);


it('met à jour un plan existant', function () {
    $faker = FakerFactory::create();

    $plan = Plan::factory()->create([
        'name'  => 'Initial',
        'price' => 20,
        'type'  => PlanTypeEnum::BASIC,
    ]);

    $newType = $faker->randomElement(PlanTypeEnum::cases());

    $data = [
        'name'  => 'Plan Modifié',
        'price' => 35.0,
        'type'  => $newType,
    ];

    $updated = UpdatePlan::execute($plan, $data);

    expect($updated->name)->toBe('Plan Modifié')
        ->and($updated->price)->toBe(35.0)
        ->and($updated->type)->toBe($newType);

    $this->assertDatabaseHas('plans', ['name' => 'Plan Modifié']);
});
