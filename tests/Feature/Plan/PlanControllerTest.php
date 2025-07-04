<?php

use App\Models\Plan;
use App\Models\User;
use App\Enums\UserRoleEnum;
use App\Enums\PlanTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\deleteJson;

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRoleEnum::ADMIN]);
    $this->user = User::factory()->create(['role' => UserRoleEnum::SENDER, 'email_verified_at' => now()]);
});

it('liste uniquement les plans actifs', function () {
    actingAs($this->user); // ou $this->admin si tu veux

    Plan::factory()->count(2)->create(['is_active' => true]);
    Plan::factory()->create(['is_active' => false]);

    $response = getJson('/api/v1/plans');
    $response->assertOk()->assertJsonCount(2, 'data');
});


it('un admin peut créer un plan', function () {
    actingAs($this->admin);

    $payload = [
        'name' => 'Plan Pro',
        'price' => 99.99,
        'type' => PlanTypeEnum::PREMIUM->value,
        'features' => ['Support prioritaire', '5 valises'],
        'duration_days' => 30,
        'discount_percent' => 20,
        'discount_expires_at' => now()->addWeek()->toDateString(),
        'is_active' => true,
    ];

    postJson('/api/v1/plans', $payload)
        ->assertCreated()
        ->assertJsonFragment(['name' => 'Plan Pro']);
});

it('rejette la création de plan par un user non admin', function () {
    actingAs($this->user);

    postJson('/api/v1/plans', [])->assertForbidden();
});

it('un admin peut modifier un plan', function () {
    actingAs($this->admin);
    $plan = Plan::factory()->create(['name' => 'Initial']);

    putJson("/api/v1/plans/{$plan->id}", ['name' => 'Modifié'])
        ->assertOk()
        ->assertJsonFragment(['name' => 'Modifié']);
});

it('un admin peut supprimer un plan', function () {
    actingAs($this->admin);
    $plan = Plan::factory()->create();

    deleteJson("/api/v1/plans/{$plan->id}")
        ->assertOk()
        ->assertJsonFragment(['message' => 'Plan supprimé.']);
});
