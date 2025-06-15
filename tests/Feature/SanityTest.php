<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

it('starts Laravel and has empty users', function () {
    expect(User::count())->toBe(0);
});

it('can create a user', function () {
    $user = User::factory()->create();
    expect($user)->toBeInstanceOf(User::class);
});
