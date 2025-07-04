<?php

use App\Actions\Plan\CreatePlan;
use App\Enums\PlanTypeEnum;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);


it('crée un plan avec des données valides', function () {
    $data = [
        'name' => 'Plan Pro',
        'price' => 49.99,
        'type' => PlanTypeEnum::PREMIUM,
        'features' => ['support', 'priorité', 'accès VIP'],
        'duration_days' => 30,
        'discount_percent' => 10,
        'discount_expires_at' => now()->addWeek(),
        'is_active' => true,
    ];

    $plan = CreatePlan::execute($data);

    expect($plan)->toBeInstanceOf(Plan::class)
        ->and($plan->name)->toBe('Plan Pro')
        ->and($plan->type)->toBe(PlanTypeEnum::PREMIUM)
        ->and($plan->features)->toBeArray()
        ->and($plan->features)->toContain('support')
        ->and($plan->is_active)->toBeTrue();

    $this->assertDatabaseHas('plans', ['name' => 'Plan Pro']);
});
