<?php

declare(strict_types=1);

use App\Enums\UserRoleEnum;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(Tests\TestCase::class, RefreshDatabase::class);

function createTestUser(UserRoleEnum $role): User
{
    return User::create([
        'first_name' => 'Test',
        'last_name' => $role->value,
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


it('allows admin to list audit logs', function (): void {
    $admin = createTestUser(UserRoleEnum::ADMIN);
    $booking = Booking::factory()->create();

    AuditLog::create([
        'actor_id' => $admin->id,
        'action' => 'admin_refund_override',
        'auditable_type' => Booking::class,
        'auditable_id' => $booking->id,
        'metadata' => ['amount' => 100],
        'reason' => 'Litige confirmé',
    ]);

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/admin/audit-logs')
        ->assertOk()
        ->assertJsonPath('data.0.action', 'admin_refund_override')
        ->assertJsonPath('data.0.reason', 'Litige confirmé');
});

it('forbids non admin from listing audit logs', function (): void {
    $user = createTestUser(UserRoleEnum::SENDER);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/admin/audit-logs')
        ->assertForbidden();
});

it('allows admin to show one audit log', function (): void {
    $admin = createTestUser(UserRoleEnum::ADMIN);
    $booking = Booking::factory()->create();

    $auditLog = AuditLog::create([
        'actor_id' => $admin->id,
        'action' => 'admin_refund_override',
        'auditable_type' => Booking::class,
        'auditable_id' => $booking->id,
        'metadata' => ['booking_id' => $booking->id],
        'reason' => 'Fraude confirmée',
    ]);

    Sanctum::actingAs($admin);

    $this->getJson("/api/v1/admin/audit-logs/{$auditLog->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $auditLog->id)
        ->assertJsonPath('data.action', 'admin_refund_override');
});

it('filters audit logs by actor id action and auditable id', function (): void {
    $admin = createTestUser(UserRoleEnum::ADMIN);
    $otherAdmin = createTestUser(UserRoleEnum::ADMIN);

    $booking = Booking::factory()->create();
    $otherBooking = Booking::factory()->create();

    AuditLog::create([
        'actor_id' => $admin->id,
        'action' => 'admin_refund_override',
        'auditable_type' => Booking::class,
        'auditable_id' => $booking->id,
        'metadata' => ['match' => true],
        'reason' => 'Litige validé',
    ]);

    AuditLog::create([
        'actor_id' => $otherAdmin->id,
        'action' => 'other_action',
        'auditable_type' => Booking::class,
        'auditable_id' => $otherBooking->id,
        'metadata' => ['match' => false],
        'reason' => 'Autre log',
    ]);

    Sanctum::actingAs($admin);

    $this->getJson("/api/v1/admin/audit-logs?actor_id={$admin->id}&action=admin_refund_override&auditable_id={$booking->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.actor.id', $admin->id)
        ->assertJsonPath('data.0.action', 'admin_refund_override')
        ->assertJsonPath('data.0.auditable.type', Booking::class)
        ->assertJsonPath('data.0.auditable.id', $booking->id);
});

it('paginates audit logs', function (): void {
    $admin = createTestUser(UserRoleEnum::ADMIN);
    $booking = Booking::factory()->create();

    foreach (range(1, 3) as $index) {
        AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'admin_refund_override',
            'auditable_type' => Booking::class,
            'auditable_id' => $booking->id,
            'metadata' => ['index' => $index],
            'reason' => 'Test pagination',
        ]);
    }

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/admin/audit-logs?per_page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', 3);
});
