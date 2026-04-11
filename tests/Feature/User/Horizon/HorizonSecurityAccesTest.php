<?php

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('permet à un admin d’accéder à /horizon', function () {
    $admin = User::factory()->create([
        'role' => UserRoleEnum::ADMIN,
    ]);

    $this->actingAs($admin)
        ->get('/horizon')
        ->assertStatus(200);
});

it('refuse un utilisateur non admin sur /horizon', function () {
    $user = User::factory()->create([
        'role' => UserRoleEnum::SENDER,
    ]);

    $this->actingAs($user)
        ->get('/horizon')
        ->assertStatus(403);
});

it('refuse un invité non authentifié sur /horizon', function () {
    $this->get('/horizon')
        ->assertStatus(401);
});
