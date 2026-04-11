<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('autorise un admin à accéder à Horizon', function () {
    $admin = User::factory()->create([
        'role' => \App\Enums\UserRoleEnum::ADMIN,
    ]);

    expect(Gate::forUser($admin)->allows('viewHorizon'))->toBeTrue();
});

it('refuse un utilisateur non admin pour Horizon', function () {
    $user = User::factory()->create([
        'role' => \App\Enums\UserRoleEnum::SENDER,
    ]);

    expect(Gate::forUser($user)->allows('viewHorizon'))->toBeFalse();
});

it('refuse un invité non authentifié pour Horizon', function () {
    expect(Gate::allows('viewHorizon'))->toBeFalse();
});
