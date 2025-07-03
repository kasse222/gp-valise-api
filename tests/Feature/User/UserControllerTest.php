<?php

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);


use App\Enums\PlanTypeEnum;
use App\Enums\UserRoleEnum;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Bus;
use App\Services\PlanService;
use function Pest\Laravel\{actingAs, getJson, putJson, postJson};

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('affiche le profil de l’utilisateur connecté', function () {
    actingAs($this->user)
        ->getJson("/api/v1/users/{$this->user->id}")
        ->assertOk()
        ->assertJsonFragment(['id' => $this->user->id]);
});

it('refuse l’accès au profil d’un autre utilisateur', function () {
    $other = User::factory()->create(['role' => UserRoleEnum::TRAVELER]); //example traveler
    $this->user->update(['role' => UserRoleEnum::TRAVELER]);

    expect($this->user->isAdmin())->toBeFalse();
    expect($other->isAdmin())->toBeFalse();

    actingAs($this->user)
        ->getJson("/api/v1/users/{$other->id}")
        ->assertForbidden();
});

it('met à jour le plan de l’utilisateur', function () {
    $plan = Plan::factory()->create(['type' => PlanTypeEnum::PREMIUM]);

    actingAs($this->user)
        ->putJson("/api/v1/users/{$this->user->id}", [
            'plan_id' => $plan->id,
        ])
        ->assertOk()
        ->assertJsonFragment(['plan_id' => $plan->id]);
});

it('change le mot de passe avec succès', function () {
    $this->user->update(['password' => Hash::make('ancien')]);

    actingAs($this->user)
        ->postJson("/api/v1/users/{$this->user->id}/change-password", [
            'current_password' => 'ancien123',
            'new_password'              => 'nouveau123',
            'new_password_confirmation' => 'nouveau123',
        ])
        ->assertOk();

    expect(Hash::check('nouveau123', $this->user->fresh()->password))->toBeTrue();
});

it('vérifie le téléphone avec succès', function () {
    // on donne un numéro de téléphone réel pour éviter l’erreur de validation
    $this->user->update(['phone' => '+33612345678']);

    actingAs($this->user)
        ->postJson("/api/v1/users/{$this->user->id}/verify-phone", [
            'phone' => $this->user->phone,
            'code'  => '123456',
        ])
        ->assertOk()
        ->assertJsonFragment(['message' => 'Téléphone vérifié.']);

    $this->user->refresh(); // ← essentiel !
    expect($this->user->phone_verified_at)->not()->toBeNull();
});


it('vérifie l’email avec succès', function () {
    actingAs($this->user)
        ->postJson("/api/v1/users/{$this->user->id}/verify-email", [
            'email' => $this->user->email,
            'code'  => 'ABC123',
        ])
        ->assertOk()
        ->assertJsonFragment(['message' => 'Email vérifié.']);

    $this->user->refresh(); // ← important ici aussi
    expect($this->user->email_verified_at)->not()->toBeNull();
});


it('upgrade le plan via PlanService', function () {
    $plan = Plan::factory()->premium()->create();

    actingAs($this->user)
        ->postJson("/api/v1/users/{$this->user->id}/upgrade-plan", [
            'plan_id' => $plan->id,
        ])
        ->assertOk();

    expect($this->user->fresh()->plan_id)->toBe($plan->id);
});
