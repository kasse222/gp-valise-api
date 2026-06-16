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
use function Pest\Laravel\{actingAs, getJson, putJson, postJson};

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('affiche le profil de l\'utilisateur connecté', function () {
    actingAs($this->user)
        ->getJson("/api/v1/users/{$this->user->id}")
        ->assertOk()
        ->assertJsonFragment(['id' => $this->user->id]);
});

it('refuse l\'accès au profil d\'un autre utilisateur', function () {
    $other = User::factory()->create(['role' => UserRoleEnum::TRAVELER]);
    $this->user->update(['role' => UserRoleEnum::TRAVELER]);

    expect($this->user->isAdmin())->toBeFalse();
    expect($other->isAdmin())->toBeFalse();

    actingAs($this->user)
        ->getJson("/api/v1/users/{$other->id}")
        ->assertForbidden();
});

// ── F-001 : plan_id ignoré via profil public ──────────────────────────────

it('ignore plan_id soumis via le profil public (F-001)', function () {
    $plan = Plan::factory()->create(['type' => PlanTypeEnum::PREMIUM]);
    $originalPlanId = $this->user->plan_id;

    actingAs($this->user)
        ->putJson("/api/v1/users/{$this->user->id}", [
            'plan_id' => $plan->id,
        ])
        ->assertOk();

    // plan_id ne doit PAS avoir changé
    expect($this->user->fresh()->plan_id)->toBe($originalPlanId);
});

// ── F-001 : role ignoré via profil public ────────────────────────────────

it('ignore role soumis via le profil public (F-001)', function () {
    $this->user->update(['role' => UserRoleEnum::SENDER]);

    actingAs($this->user)
        ->putJson("/api/v1/users/{$this->user->id}", [
            'role' => UserRoleEnum::ADMIN->value,
        ])
        ->assertOk();

    expect($this->user->fresh()->role)->toBe(UserRoleEnum::SENDER);
});

it('met à jour les champs légitimes du profil', function () {
    actingAs($this->user)
        ->putJson("/api/v1/users/{$this->user->id}", [
            'first_name' => 'Lamine',
            'last_name'  => 'Kasse',
            'country'    => 'SN',
        ])
        ->assertOk();

    $fresh = $this->user->fresh();
    expect($fresh->first_name)->toBe('Lamine');
    expect($fresh->country)->toBe('SN');
});

it('change le mot de passe avec succès', function () {
    $this->user->update(['password' => Hash::make('ancien')]);

    actingAs($this->user)
        ->postJson("/api/v1/users/{$this->user->id}/change-password", [
            'current_password'          => 'ancien123',
            'new_password'              => 'nouveau123',
            'new_password_confirmation' => 'nouveau123',
        ])
        ->assertOk();

    expect(Hash::check('nouveau123', $this->user->fresh()->password))->toBeTrue();
});

it('vérifie le téléphone avec succès', function () {
    $this->user->update(['phone' => '+33612345678']);

    actingAs($this->user)
        ->postJson("/api/v1/users/{$this->user->id}/verify-phone", [
            'phone' => $this->user->phone,
            'code'  => '123456',
        ])
        ->assertOk()
        ->assertJsonFragment(['message' => 'Téléphone vérifié.']);

    $this->user->refresh();
    expect($this->user->phone_verified_at)->not()->toBeNull();
});

it('vérifie l\'email avec succès', function () {
    actingAs($this->user)
        ->postJson("/api/v1/users/{$this->user->id}/verify-email", [
            'email' => $this->user->email,
            'code'  => 'ABC123',
        ])
        ->assertOk()
        ->assertJsonFragment(['message' => 'Email vérifié.']);

    $this->user->refresh();
    expect($this->user->email_verified_at)->not()->toBeNull();
});

it('upgrade le plan via la route dédiée', function () {
    $plan = Plan::factory()->premium()->create();

    actingAs($this->user)
        ->postJson("/api/v1/users/{$this->user->id}/upgrade-plan", [
            'plan_id' => $plan->id,
        ])
        ->assertOk();

    expect($this->user->fresh()->plan_id)->toBe($plan->id);
});
