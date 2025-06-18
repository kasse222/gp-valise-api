<?php

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);


use App\Models\Trip;
use App\Models\User;
use function Pest\Laravel\actingAs;


beforeEach(function () {
    $this->user = User::factory()->create([
        'password' => \Illuminate\Support\Facades\Hash::make('password'),
    ]);
    actingAs($this->user);
    $this->trip = Trip::factory()->create(['user_id' => $this->user->id]);
});

it('refuse les rôles interdits à l’inscription', function () {
    $response = $this->postJson('/api/v1/register', [
        'email' => 'admin@example.com',
        'password' => 'password123',
        'role' => 'admin', // ⛔️
        // ...
    ]);

    $response->assertStatus(403);
});
