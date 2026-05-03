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

it('bloque l\'inscription avec le rôle ADMIN (1)', function () {
    $payload = [
        'first_name'            => 'Test',
        'last_name'             => 'Admin',
        'email'                 => 'role-admin@test.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
        'phone'                 => '+212600000011',
        'country'               => 'MA',
        'role'                  => 1,
    ];

    $this->postJson('/api/v1/register', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors('role');
});

it('bloque l\'inscription avec le rôle SUPER_ADMIN (6)', function () {
    $payload = [
        'first_name'            => 'Test',
        'last_name'             => 'SuperAdmin',
        'email'                 => 'role-superadmin@test.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
        'phone'                 => '+212600000012',
        'country'               => 'MA',
        'role'                  => 6,
    ];

    $this->postJson('/api/v1/register', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors('role');
});

it('bloque l\'inscription avec le rôle MODERATOR (4)', function () {
    $payload = [
        'first_name'            => 'Test',
        'last_name'             => 'Moderator',
        'email'                 => 'role-moderator@test.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
        'phone'                 => '+212600000013',
        'country'               => 'MA',
        'role'                  => 4,
    ];

    $this->postJson('/api/v1/register', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors('role');
});

it('autorise l\'inscription avec le rôle TRAVELER (2)', function () {
    $payload = [
        'first_name'            => 'Test',
        'last_name'             => 'Traveler',
        'email'                 => 'role-traveler@test.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
        'phone'                 => '+212600000014',
        'country'               => 'MA',
        'role'                  => 2,
    ];

    $this->postJson('/api/v1/register', $payload)
        ->assertCreated();
});

it('autorise l\'inscription avec le rôle SENDER (3)', function () {
    $payload = [
        'first_name'            => 'Test',
        'last_name'             => 'Sender',
        'email'                 => 'role-sender@test.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
        'phone'                 => '+212600000015',
        'country'               => 'MA',
        'role'                  => 3,
    ];

    $this->postJson('/api/v1/register', $payload)
        ->assertCreated();
});
