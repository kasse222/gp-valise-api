<?php

declare(strict_types=1);

use App\Enums\UserRoleEnum;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\User;
use App\Services\AuditLogIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

function createAuditTestUser(UserRoleEnum $role = UserRoleEnum::ADMIN): User
{
    return User::create([
        'first_name' => 'Audit',
        'last_name' => 'Admin',
        'email' => fake()->unique()->safeEmail(),
        'phone' => fake()->unique()->e164PhoneNumber(),
        'country' => 'MA',
        'password' => password_hash('password', PASSWORD_BCRYPT),
        'role' => $role,
        'verified_user' => true,
        'kyc_passed_at' => now(),
        'plan_id' => null,
        'plan_expires_at' => null,
    ]);
}

it('seals an audit log with an integrity hash', function (): void {
    $admin = createAuditTestUser();
    $booking = Booking::factory()->create();

    $log = AuditLog::create([
        'actor_id' => $admin->id,
        'action' => 'admin_refund_override',
        'auditable_type' => Booking::class,
        'auditable_id' => $booking->id,
        'metadata' => ['amount' => 100],
        'reason' => 'Litige confirmé',
    ]);

    app(AuditLogIntegrityService::class)->seal($log);

    $log->refresh();

    expect($log->integrity_hash)->not->toBeNull()
        ->and(strlen($log->integrity_hash))->toBe(64)
        ->and($log->previous_hash)->toBeNull();
});

it('verifies a valid sealed audit log', function (): void {
    $admin = createAuditTestUser();
    $booking = Booking::factory()->create();

    $log = AuditLog::create([
        'actor_id' => $admin->id,
        'action' => 'admin_refund_override',
        'auditable_type' => Booking::class,
        'auditable_id' => $booking->id,
        'metadata' => ['amount' => 100],
        'reason' => 'Litige confirmé',
    ]);

    $service = app(AuditLogIntegrityService::class);
    $service->seal($log);

    expect($service->verifyLog($log->fresh()))->toBeTrue();
});

it('detects modified metadata', function (): void {
    $admin = createAuditTestUser();
    $booking = Booking::factory()->create();

    $log = AuditLog::create([
        'actor_id' => $admin->id,
        'action' => 'admin_refund_override',
        'auditable_type' => Booking::class,
        'auditable_id' => $booking->id,
        'metadata' => ['amount' => 100],
        'reason' => 'Litige confirmé',
    ]);

    $service = app(AuditLogIntegrityService::class);
    $service->seal($log);

    AuditLog::query()
        ->whereKey($log->id)
        ->update([
            'metadata' => ['amount' => 999],
        ]);

    expect($service->verifyLog($log->fresh()))->toBeFalse();
});

it('links audit logs with previous hash', function (): void {
    $admin = createAuditTestUser();
    $booking = Booking::factory()->create();

    $firstLog = AuditLog::create([
        'actor_id' => $admin->id,
        'action' => 'first_action',
        'auditable_type' => Booking::class,
        'auditable_id' => $booking->id,
        'metadata' => ['step' => 1],
        'reason' => 'Premier log',
    ]);

    $secondLog = AuditLog::create([
        'actor_id' => $admin->id,
        'action' => 'second_action',
        'auditable_type' => Booking::class,
        'auditable_id' => $booking->id,
        'metadata' => ['step' => 2],
        'reason' => 'Deuxième log',
    ]);

    $service = app(AuditLogIntegrityService::class);
    $service->seal($firstLog);
    $service->seal($secondLog);

    $firstLog->refresh();
    $secondLog->refresh();

    expect($secondLog->previous_hash)->toBe($firstLog->integrity_hash);
});

it('verifies a valid audit chain', function (): void {
    $admin = createAuditTestUser();
    $booking = Booking::factory()->create();
    $service = app(AuditLogIntegrityService::class);

    foreach (range(1, 3) as $index) {
        $log = AuditLog::create([
            'actor_id' => $admin->id,
            'action' => "audit_action_{$index}",
            'auditable_type' => Booking::class,
            'auditable_id' => $booking->id,
            'metadata' => ['index' => $index],
            'reason' => 'Test chain',
        ]);

        $service->seal($log);
    }

    expect($service->verifyChainFrom())->toBeTrue();
});

it('detects a broken audit chain', function (): void {
    $admin = createAuditTestUser();
    $booking = Booking::factory()->create();
    $service = app(AuditLogIntegrityService::class);

    foreach (range(1, 3) as $index) {
        $log = AuditLog::create([
            'actor_id' => $admin->id,
            'action' => "audit_action_{$index}",
            'auditable_type' => Booking::class,
            'auditable_id' => $booking->id,
            'metadata' => ['index' => $index],
            'reason' => 'Test chain',
        ]);

        $service->seal($log);
    }

    $secondLog = AuditLog::query()->orderBy('id')->skip(1)->first();

    AuditLog::query()
        ->whereKey($secondLog->id)
        ->update([
            'previous_hash' => str_repeat('x', 64),
        ]);

    expect($service->verifyChainFrom())->toBeFalse();
});
