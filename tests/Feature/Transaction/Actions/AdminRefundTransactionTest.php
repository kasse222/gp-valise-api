<?php

declare(strict_types=1);

use App\Actions\Transaction\AdminRefundTransaction;
use App\Contracts\Payments\PaymentProvider;
use App\Data\Payments\PaymentResponseData;
use App\Enums\BookingStatusEnum;
use App\Enums\CurrencyEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentProviderEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use App\Services\AuditLogIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('gpvalise.fee_percentage', 10);
    config()->set('gpvalise.payment_fee_percentage', 2);

    $this->admin    = User::factory()->admin()->verified()->create();
    $this->sender   = User::factory()->sender()->verified()->create();
    $this->traveler = User::factory()->traveler()->verified()->create();
    $this->trip     = Trip::factory()->create(['user_id' => $this->traveler->id]);

    $this->provider = mock(PaymentProvider::class);
    $this->provider
        ->shouldReceive('refund')
        ->with(\Mockery::type(\App\Data\Payments\RefundRequestData::class))
        ->andReturn(new PaymentResponseData(
            provider: PaymentProviderEnum::FAKE,
            providerTransactionId: 'admin_refund_123',
            providerStatus: 'completed',
            amount: 9000,
            currency: CurrencyEnum::EUR,
            checkoutUrl: null,
            eventId: null,
            rawPayload: [],
        ));

    app()->forgetInstance(PaymentProvider::class);
    app()->instance(PaymentProvider::class, $this->provider);

    $this->action = app(AdminRefundTransaction::class);
});

function createDisputedBookingForAdminRefund(User $sender, Trip $trip): Booking
{
    return Booking::factory()->create([
        'user_id' => $sender->id,
        'trip_id' => $trip->id,
        'status'  => BookingStatusEnum::EN_LITIGE,
    ]);
}

function createCompletedChargeForAdminRefund(Booking $booking, User $sender, int $amount = 10000): Transaction
{
    return Transaction::factory()->create([
        'user_id'      => $sender->id,
        'booking_id'   => $booking->id,
        'type'         => TransactionTypeEnum::CHARGE,
        'status'       => TransactionStatusEnum::COMPLETED,
        'amount'       => $amount, // ← centimes
        'currency'     => CurrencyEnum::EUR,
        'method'       => PaymentMethodEnum::CARD,
        'processed_at' => now(),
    ]);
}

it('permet à un admin de forcer un refund sur un booking en litige sans payout', function (): void {
    $booking = createDisputedBookingForAdminRefund($this->sender, $this->trip);
    $charge  = createCompletedChargeForAdminRefund($booking, $this->sender, 10000);

    $refund = $this->action->execute($this->admin, $charge, 'Litige validé par le support');

    expect($refund)
        ->toBeInstanceOf(Transaction::class)
        ->and($refund->type)->toBe(TransactionTypeEnum::REFUND)
        ->and($refund->status)->toBe(TransactionStatusEnum::COMPLETED)
        ->and($refund->amount)->toBe(9000)  // ← 90.00€
        ->and($refund->provider_transaction_id)->toBe('admin_refund_123');

    $this->assertDatabaseHas('audit_logs', [
        'actor_id'       => $this->admin->id,
        'action'         => 'admin_refund_override',
        'auditable_type' => Transaction::class,
        'auditable_id'   => $refund->id,
    ]);
});

it('crée un audit log avec snapshot financier complet', function (): void {
    $booking = createDisputedBookingForAdminRefund($this->sender, $this->trip);
    $charge  = createCompletedChargeForAdminRefund($booking, $this->sender, 10000);

    Transaction::factory()->create([
        'user_id'    => $this->traveler->id,
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::FEE,
        'status'     => TransactionStatusEnum::COMPLETED,
        'amount'     => 1000, // ← 10.00€
    ]);

    $refund = $this->action->execute($this->admin, $charge, 'Bagage déclaré perdu après litige');

    $audit = AuditLog::query()
        ->where('action', 'admin_refund_override')
        ->where('auditable_id', $refund->id)
        ->firstOrFail();

    expect($audit->metadata)
        ->toHaveKey('reason')
        ->toHaveKey('admin')
        ->toHaveKey('booking')
        ->toHaveKey('charge')
        ->toHaveKey('transactions')
        ->toHaveKey('refund')
        ->toHaveKey('hash')
        ->and($audit->metadata['reason'])->toBe('Bagage déclaré perdu après litige')
        ->and($audit->metadata['booking']['id'])->toBe($booking->id)
        ->and($audit->metadata['booking']['status'])->toBe(BookingStatusEnum::EN_LITIGE->value)
        ->and($audit->metadata['charge']['id'])->toBe($charge->id)
        ->and($audit->metadata['refund']['id'])->toBe($refund->id);
});

it('refuse un refund admin si utilisateur non admin', function (): void {
    $booking = createDisputedBookingForAdminRefund($this->sender, $this->trip);
    $charge  = createCompletedChargeForAdminRefund($booking, $this->sender);

    $this->action->execute($this->sender, $charge, 'Tentative non autorisée');
})->throws(ValidationException::class);

it('refuse un refund admin si raison vide', function (): void {
    $booking = createDisputedBookingForAdminRefund($this->sender, $this->trip);
    $charge  = createCompletedChargeForAdminRefund($booking, $this->sender);

    $this->action->execute($this->admin, $charge, '   ');
})->throws(ValidationException::class);

it('refuse un refund admin si le booking nest pas en litige', function (): void {
    $booking = Booking::factory()->create([
        'user_id' => $this->sender->id,
        'trip_id' => $this->trip->id,
        'status'  => BookingStatusEnum::LIVREE,
    ]);

    $charge = createCompletedChargeForAdminRefund($booking, $this->sender);
    $this->action->execute($this->admin, $charge, 'Refund demandé sans litige');
})->throws(ValidationException::class);

it('refuse un refund admin si un payout existe déjà', function (): void {
    $booking = createDisputedBookingForAdminRefund($this->sender, $this->trip);
    $charge  = createCompletedChargeForAdminRefund($booking, $this->sender);

    Transaction::factory()->create([
        'user_id'    => $this->traveler->id,
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::PAYOUT,
        'status'     => TransactionStatusEnum::COMPLETED,
        'amount'     => 9000, // ← centimes
    ]);

    $this->action->execute($this->admin, $charge, 'Refund impossible après payout');
})->throws(ValidationException::class);

it('seal() est appelé : integrity_hash renseigné et verifyLog retourne true', function (): void {
    $booking = createDisputedBookingForAdminRefund($this->sender, $this->trip);
    $charge  = createCompletedChargeForAdminRefund($booking, $this->sender, 10000);

    $refund = $this->action->execute($this->admin, $charge, 'Litige validé');

    $auditLog = AuditLog::query()
        ->where('action', 'admin_refund_override')
        ->where('auditable_id', $refund->id)
        ->firstOrFail();

    $integrity = app(AuditLogIntegrityService::class);

    expect($auditLog->integrity_hash)->not->toBeNull()
        ->and($auditLog->previous_hash)->toBeNull()
        ->and($integrity->verifyLog($auditLog))->toBeTrue();
});

it('chaîne d\'intégrité valide sur deux refunds successifs', function (): void {
    $booking1 = createDisputedBookingForAdminRefund($this->sender, $this->trip);
    $charge1  = createCompletedChargeForAdminRefund($booking1, $this->sender, 10000);

    $trip2    = Trip::factory()->create(['user_id' => $this->traveler->id]);
    $booking2 = createDisputedBookingForAdminRefund($this->sender, $trip2);
    $charge2  = createCompletedChargeForAdminRefund($booking2, $this->sender, 20000);

    $refund1 = $this->action->execute($this->admin, $charge1, 'Premier litige');
    $refund2 = $this->action->execute($this->admin, $charge2, 'Deuxième litige');

    [$log1, $log2] = AuditLog::query()
        ->where('action', 'admin_refund_override')
        ->orderBy('id')
        ->get()
        ->all();

    $integrity = app(AuditLogIntegrityService::class);

    expect($log2->previous_hash)->toBe($log1->integrity_hash)
        ->and($integrity->verifyChainFrom())->toBeTrue();
});

it('persiste le correlationId dans l\'AuditLog', function (): void {
    $booking       = createDisputedBookingForAdminRefund($this->sender, $this->trip);
    $charge        = createCompletedChargeForAdminRefund($booking, $this->sender, 10000);
    $correlationId = 'test-cid-audit-001';

    $refund = $this->action->execute($this->admin, $charge, 'Litige validé', $correlationId);

    $auditLog = AuditLog::query()
        ->where('action', 'admin_refund_override')
        ->where('auditable_id', $refund->id)
        ->firstOrFail();

    expect($auditLog->correlation_id)->toBe($correlationId);
});

it('est idempotent si un refund admin existe déjà', function (): void {
    $booking = createDisputedBookingForAdminRefund($this->sender, $this->trip);
    $charge  = createCompletedChargeForAdminRefund($booking, $this->sender);

    $existingRefund = Transaction::factory()->create([
        'user_id'                 => $this->sender->id,
        'booking_id'              => $booking->id,
        'type'                    => TransactionTypeEnum::REFUND,
        'status'                  => TransactionStatusEnum::COMPLETED,
        'amount'                  => 9000, // ← centimes
        'provider_transaction_id' => 'existing_refund_123',
    ]);

    $refund = $this->action->execute($this->admin, $charge, 'Retry admin refund');

    expect($refund->id)->toBe($existingRefund->id)
        ->and(
            Transaction::query()
                ->where('booking_id', $booking->id)
                ->where('type', TransactionTypeEnum::REFUND)
                ->count()
        )->toBe(1);
});
