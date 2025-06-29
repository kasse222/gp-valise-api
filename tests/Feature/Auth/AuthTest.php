<?php

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;



it('enregistre un nouvel utilisateur avec succès', function () {

    $payload = [
        'first_name' => 'Lamine',
        'last_name' => 'Kasse',
        'email' => 'lamine@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'phone' => '+212600000000',
        'country' => 'MA',
        'role' => UserRoleEnum::TRAVELER->value,
    ];
    dump($payload['role']);



    $response = $this->postJson('/api/v1/register', $payload);

    $response->assertCreated()
        ->assertJsonStructure([
            'message',
            'user' => ['id', 'email', 'role'],
            'token',
        ]);

    expect(User::where('email', $payload['email'])->exists())->toBeTrue();
});

it('rejette une tentative de connexion avec mot de passe invalide', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('correct-password'),
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('connecte un utilisateur et retourne un token', function () {
    $user = User::factory()->create([
        'email' => 'lamine@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['message', 'token', 'user']);
});

it('retourne les infos utilisateur via /me', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth_token')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/me');

    $response->assertOk()
        ->assertJsonStructure(['user' => ['id', 'email', 'role']]);
});

it('permet de se déconnecter (logout)', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth_token')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/v1/logout');

    $response->assertOk()
        ->assertJson(['message' => 'Déconnexion réussie.']);
});

it('rejette une inscription si email déjà utilisé', function () {
    $existingUser = User::factory()->create(['email' => 'lamine@example.com']);

    $payload = [
        'first_name' => 'Test',
        'last_name' => 'Dup',
        'email' => 'lamine@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'phone' => '+212600000000',
        'country' => 'MA',
        'role' => UserRoleEnum::SENDER->value,
    ];

    $response = $this->postJson('/api/v1/register', $payload);

    $response->assertStatus(422)->assertJsonValidationErrors('email');
});

it('rejette la route /me si non authentifié', function () {
    $response = $this->getJson('/api/v1/me');

    $response->assertUnauthorized();
});
