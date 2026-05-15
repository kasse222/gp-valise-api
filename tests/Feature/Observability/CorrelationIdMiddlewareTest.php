<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('adds a correlation id header when missing', function (): void {
    $response = $this->getJson('/api/v1/trips');

    $response->assertOk();

    $correlationId = $response->headers->get('X-Correlation-ID');

    expect($correlationId)
        ->not->toBeNull()
        ->and(Str::isUuid($correlationId))->toBeTrue();
});

it('keeps the provided correlation id header', function (): void {
    $correlationId = (string) Str::uuid();

    $response = $this->withHeader('X-Correlation-ID', $correlationId)
        ->getJson('/api/v1/trips');

    $response->assertOk();

    expect($response->headers->get('X-Correlation-ID'))->toBe($correlationId);
});

it('adds correlation id even on unauthenticated responses', function (): void {
    $response = $this->getJson('/api/v1/bookings');

    $response->assertUnauthorized()
        ->assertHeader('X-Correlation-ID');
});
