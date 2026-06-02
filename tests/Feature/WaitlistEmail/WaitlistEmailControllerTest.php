<?php

declare(strict_types=1);

use App\Models\WaitlistEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('peut s\'inscrire sur la waitlist', function (): void {
    $response = $this->postJson('/api/v1/waitlist', [
        'email'   => 'test@example.com',
        'name'    => 'John Doe',
        'role'    => 'sender',
        'message' => 'Je veux tester la plateforme.',
    ]);

    $response->assertStatus(201)
        ->assertJson(['message' => 'Inscription enregistrée avec succès.']);

    $this->assertDatabaseHas('waitlist_emails', [
        'email' => 'test@example.com',
        'role'  => 'sender',
    ]);
});

it('refuse une inscription avec un email déjà existant', function (): void {
    WaitlistEmail::factory()->create(['email' => 'test@example.com']);

    $this->postJson('/api/v1/waitlist', [
        'email' => 'test@example.com',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('refuse une inscription sans email', function (): void {
    $this->postJson('/api/v1/waitlist', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('refuse un rôle invalide', function (): void {
    $this->postJson('/api/v1/waitlist', [
        'email' => 'test@example.com',
        'role'  => 'invalid_role',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['role']);
});

it('persiste l\'ip et le user_agent', function (): void {
    $this->postJson('/api/v1/waitlist', [
        'email' => 'test@example.com',
    ]);

    $entry = WaitlistEmail::where('email', 'test@example.com')->first();

    expect($entry->ip_address)->not->toBeNull();
});
