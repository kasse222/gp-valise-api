<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BookingStatusEnum;
use App\Enums\CurrencyEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\TripStatusEnum;
use App\Enums\TripTypeEnum;
use App\Enums\UserRoleEnum;
use App\Enums\LocationPositionEnum;
use App\Enums\LocationTypeEnum;
use App\Enums\LuggageStatusEnum;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Luggage;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use App\Models\WebhookLog;
use App\Enums\WebhookLogStatusEnum;
use App\Services\AuditLogIntegrityService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * DemoSeeder — GP-Valise
 *
 * Crée un flow complet traçable pour la démo LinkedIn :
 *
 * FLOW A — Refund standard via webhook (correlation_id visible partout)
 *   SENDER → booking → CHARGE completed → CONFIRMEE
 *   → webhook refund.completed → Transaction COMPLETED → Booking REMBOURSEE
 *   → WebhookLog avec correlation_id
 *
 * FLOW B — Admin refund override sur booking EN_LITIGE
 *   booking EN_LITIGE → AdminRefund → AuditLog sellé (integrity_hash)
 *   → AuditLog avec correlation_id
 *
 * Credentials fixes pour la démo :
 *   admin@gpvalise.demo     / Demo1234!
 *   voyageur@gpvalise.demo  / Demo1234!
 *   expediteur@gpvalise.demo / Demo1234!
 */
class DemoSeeder extends Seeder
{
    public function __construct(
        private readonly AuditLogIntegrityService $integrityService,
    ) {}

    public function run(): void
    {
        $this->command->info('🚀 DemoSeeder — GP-Valise');

        $plan = $this->ensurePlan();
        [$admin, $traveler, $sender] = $this->createUsers($plan);
        $trip = $this->createTrip($traveler);

        // ── FLOW A : refund standard via webhook ──────────────────────────
        $correlationIdA = (string) Str::uuid();
        $bookingA       = $this->createBookingConfirmee($sender, $trip);
        $chargeA        = $this->createCharge($sender, $bookingA);
        $this->simulateWebhookRefund($bookingA, $chargeA, $correlationIdA);

        // ── FLOW B : admin refund override sur EN_LITIGE ──────────────────
        $correlationIdB = (string) Str::uuid();
        $bookingB       = $this->createBookingEnLitige($sender, $trip);
        $chargeB        = $this->createCharge($sender, $bookingB);
        $this->createAdminRefundWithAudit($admin, $bookingB, $chargeB, $correlationIdB);

        $this->printSummary($correlationIdA, $correlationIdB, $admin, $traveler, $sender);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function ensurePlan(): Plan
    {
        return Plan::firstOrCreate(
            ['type' => 'free'],
            [
                'name'          => 'Plan Gratuit',
                'price'         => 0,
                'features'      => ['Accès de base'],
                'duration_days' => 0,
                'is_active'     => true,
            ]
        );
    }

    private function createUsers(Plan $plan): array
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@gpvalise.demo'],
            [
                'first_name'      => 'Lamine',
                'last_name'       => 'Admin',
                'phone'           => '+212600000010',
                'country'         => 'MA',
                'password'        => Hash::make('Demo1234!'),
                'role'            => UserRoleEnum::ADMIN->value,
                'verified_user'   => true,
                'plan_id'         => $plan->id,
                'plan_expires_at' => now()->addYear(),
            ]
        );

        $traveler = User::updateOrCreate(
            ['email' => 'voyageur@gpvalise.demo'],
            [
                'first_name'      => 'Marie',
                'last_name'       => 'Dupont',
                'phone'           => '+212600000011',
                'country'         => 'FR',
                'password'        => Hash::make('Demo1234!'),
                'role'            => UserRoleEnum::TRAVELER->value,
                'verified_user'   => true,
                'plan_id'         => $plan->id,
                'plan_expires_at' => now()->addYear(),
            ]
        );

        $sender = User::updateOrCreate(
            ['email' => 'expediteur@gpvalise.demo'],
            [
                'first_name'      => 'Karim',
                'last_name'       => 'Expéditeur',
                'phone'           => '+212600000012',
                'country'         => 'MA',
                'password'        => Hash::make('Demo1234!'),
                'role'            => UserRoleEnum::SENDER->value,
                'verified_user'   => true,
                'plan_id'         => $plan->id,
                'plan_expires_at' => now()->addYear(),
            ]
        );

        $this->command->info('✔ Utilisateurs créés');

        return [$admin, $traveler, $sender];
    }

    private function createTrip(User $traveler): Trip
    {
        $trip = Trip::create([
            'user_id'       => $traveler->id,
            'departure'     => 'Paris, FR',
            'destination'   => 'Casablanca, MA',
            'date'          => now()->addDays(15),
            'capacity'      => 30,
            'status'        => TripStatusEnum::ACTIVE->value,
            'type_trip'     => TripTypeEnum::STANDARD->value,
            'flight_number' => 'AT201',
            'price_per_kg'  => 8.50,
        ]);

        $trip->locations()->createMany([
            [
                'city'        => 'Paris',
                'latitude'    => 48.8566,
                'longitude'   => 2.3522,
                'position'    => LocationPositionEnum::DEPART->value,
                'type'        => LocationTypeEnum::AEROPORT->value,
                'order_index' => 0,
            ],
            [
                'city'        => 'Casablanca',
                'latitude'    => 33.5731,
                'longitude'   => -7.5898,
                'position'    => LocationPositionEnum::ARRIVEE->value,
                'type'        => LocationTypeEnum::AEROPORT->value,
                'order_index' => 1,
            ],
        ]);

        $this->command->info('✔ Trip Paris → Casablanca créé (id: ' . $trip->id . ')');

        return $trip;
    }

    private function createBookingConfirmee(User $sender, Trip $trip): Booking
    {
        Booking::disableAutoStatusCreation();

        $booking = Booking::create([
            'user_id'  => $sender->id,
            'trip_id'  => $trip->id,
            'status'   => BookingStatusEnum::CONFIRMEE->value,
            'comment'  => 'Démo — flow refund standard webhook',
            'confirmed_at' => now()->subDay(),
        ]);

        Booking::disableAutoStatusCreation();

        $luggage = Luggage::create([
            'user_id'             => $sender->id,
            'trip_id'             => $trip->id,
            'description'         => 'Valise demo refund',
            'weight_kg'           => 10,
            'length_cm'           => 60,
            'width_cm'            => 40,
            'height_cm'           => 25,
            'pickup_city'         => 'Paris',
            'delivery_city'       => 'Casablanca',
            'pickup_date'         => now()->addDays(14),
            'delivery_date'       => now()->addDays(16),
            'status'              => LuggageStatusEnum::RESERVEE->value,
            'tracking_id'         => (string) Str::uuid(),
            'is_fragile'          => false,
            'insurance_requested' => false,
        ]);

        $booking->bookingItems()->create([
            'luggage_id'  => $luggage->id,
            'trip_id'     => $trip->id,
            'kg_reserved' => 10,
        ]);

        $this->command->info('✔ Booking A CONFIRMEE créé (id: ' . $booking->id . ')');

        return $booking;
    }

    private function createBookingEnLitige(User $sender, Trip $trip): Booking
    {
        Booking::disableAutoStatusCreation();
        $booking = Booking::create([
            'user_id'  => $sender->id,
            'trip_id'  => $trip->id,
            'status'   => BookingStatusEnum::EN_LITIGE->value,
            'comment'  => 'Démo — flow admin refund override',
        ]);

        Booking::disableAutoStatusCreation();

        $luggage = Luggage::create([
            'user_id'             => $sender->id,
            'trip_id'             => $trip->id,
            'description'         => 'Valise demo admin refund',
            'weight_kg'           => 5,
            'length_cm'           => 40,
            'width_cm'            => 30,
            'height_cm'           => 20,
            'pickup_city'         => 'Paris',
            'delivery_city'       => 'Casablanca',
            'pickup_date'         => now()->addDays(14),
            'delivery_date'       => now()->addDays(16),
            'status'              => LuggageStatusEnum::RESERVEE->value,
            'tracking_id'         => (string) Str::uuid(),
            'is_fragile'          => true,
            'insurance_requested' => true,
        ]);

        $booking->bookingItems()->create([
            'luggage_id'  => $luggage->id,
            'trip_id'     => $trip->id,
            'kg_reserved' => 5,
        ]);

        $this->command->info('✔ Booking B EN_LITIGE créé (id: ' . $booking->id . ')');

        return $booking;
    }

    private function createCharge(User $sender, Booking $booking): Transaction
    {
        $charge = Transaction::create([
            'user_id'                => $sender->id,
            'booking_id'             => $booking->id,
            'type'                   => TransactionTypeEnum::CHARGE->value,
            'amount'                 => 100.00,
            'currency'               => CurrencyEnum::EUR->value,
            'method'                 => PaymentMethodEnum::CARD->value,
            'status'                 => TransactionStatusEnum::COMPLETED->value,
            'provider_transaction_id' => 'demo-charge-' . Str::random(8),
            'processed_at'           => now()->subHours(2),
        ]);

        $this->command->info('  → CHARGE 100.00 EUR créée (id: ' . $charge->id . ')');

        return $charge;
    }

    private function simulateWebhookRefund(
        Booking $booking,
        Transaction $charge,
        string $correlationId
    ): void {
        // Créer la transaction REFUND PENDING
        $refund = Transaction::create([
            'user_id'                => $charge->user_id,
            'booking_id'             => $booking->id,
            'type'                   => TransactionTypeEnum::REFUND->value,
            'amount'                 => 90.00,
            'currency'               => CurrencyEnum::EUR->value,
            'method'                 => PaymentMethodEnum::CARD->value,
            'status'                 => TransactionStatusEnum::COMPLETED->value,
            'provider_transaction_id' => 'demo-refund-' . Str::random(8),
            'processed_at'           => now(),
            'correlation_id'         => $correlationId,
        ]);

        // Passer le booking à REMBOURSEE
        Booking::disableAutoStatusCreation();

        $booking->update(['status' => BookingStatusEnum::REMBOURSEE->value]);
        Booking::disableAutoStatusCreation();

        // Créer le WebhookLog avec correlation_id
        WebhookLog::create([
            'event_id'               => 'demo-evt-' . Str::random(10),
            'event'                  => 'refund.completed',
            'provider_transaction_id' => $refund->provider_transaction_id,
            'status'                 => WebhookLogStatusEnum::PROCESSED->value,
            'payload'                => [
                'event'                  => 'refund.completed',
                'provider_transaction_id' => $refund->provider_transaction_id,
                'amount'                 => 90.00,
                'currency'               => 'EUR',
            ],
            'processed_at'   => now(),
            'correlation_id' => $correlationId,
        ]);

        $this->command->info('✔ Flow A — Refund webhook simulé');
        $this->command->info('  correlation_id: ' . $correlationId);
        $this->command->info('  → Transaction REFUND COMPLETED (id: ' . $refund->id . ')');
        $this->command->info('  → Booking REMBOURSEE (id: ' . $booking->id . ')');
        $this->command->info('  → WebhookLog STATUS_PROCESSED');
    }

    private function createAdminRefundWithAudit(
        User $admin,
        Booking $booking,
        Transaction $charge,
        string $correlationId
    ): void {
        $refund = Transaction::create([
            'user_id'                => $charge->user_id,
            'booking_id'             => $booking->id,
            'type'                   => TransactionTypeEnum::REFUND->value,
            'amount'                 => 90.00,
            'currency'               => CurrencyEnum::EUR->value,
            'method'                 => PaymentMethodEnum::CARD->value,
            'status'                 => TransactionStatusEnum::COMPLETED->value,
            'provider_transaction_id' => 'demo-admin-refund-' . Str::random(8),
            'processed_at'           => now(),
            'correlation_id'         => $correlationId,
        ]);

        // Passer le booking à REMBOURSEE
        Booking::disableAutoStatusCreation();

        $booking->update(['status' => BookingStatusEnum::REMBOURSEE->value]);
        Booking::disableAutoStatusCreation();

        $snapshot = [
            'reason'         => 'Démo — remboursement admin override. Bagage endommagé confirmé.',
            'correlation_id' => $correlationId,
            'admin'          => ['id' => $admin->id, 'email' => $admin->email],
            'booking'        => ['id' => $booking->id, 'status' => 'en_litige'],
            'charge'         => ['id' => $charge->id, 'amount' => 100.00, 'currency' => 'EUR'],
            'refund'         => ['id' => $refund->id, 'amount' => 90.00, 'status' => 'completed'],
            'created_at'     => now()->toISOString(),
        ];
        $snapshot['hash'] = hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR));

        $auditLog = AuditLog::query()->create([
            'actor_id'       => $admin->id,
            'action'         => 'admin_refund_override',
            'auditable_type' => Transaction::class,
            'auditable_id'   => $refund->id,
            'metadata'       => $snapshot,
            'correlation_id' => $correlationId,
        ]);

        $this->integrityService->seal($auditLog);

        $auditLog->refresh();

        $this->command->info('✔ Flow B — Admin refund override créé');
        $this->command->info('  correlation_id: ' . $correlationId);
        $this->command->info('  → Transaction REFUND COMPLETED (id: ' . $refund->id . ')');
        $this->command->info('  → AuditLog sellé (id: ' . $auditLog->id . ')');
        $this->command->info('  → integrity_hash: ' . substr($auditLog->integrity_hash, 0, 20) . '...');
    }

    private function printSummary(
        string $correlationIdA,
        string $correlationIdB,
        User $admin,
        User $traveler,
        User $sender
    ): void {
        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info('  GP-VALISE DEMO — RÉSUMÉ');
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->newLine();
        $this->command->info('CREDENTIALS');
        $this->command->line('  admin@gpvalise.demo      / Demo1234!  (ADMIN)');
        $this->command->line('  voyageur@gpvalise.demo   / Demo1234!  (TRAVELER)');
        $this->command->line('  expediteur@gpvalise.demo / Demo1234!  (SENDER)');
        $this->command->newLine();
        $this->command->info('CORRELATION IDs À RETROUVER EN DB');
        $this->command->line('  Flow A (webhook): ' . $correlationIdA);
        $this->command->line('  Flow B (admin):   ' . $correlationIdB);
        $this->command->newLine();
        $this->command->info('REQUÊTES SQL DE DÉMO');
        $this->command->line("  SELECT correlation_id, status FROM webhook_logs ORDER BY id DESC LIMIT 3;");
        $this->command->line("  SELECT correlation_id, type, status, amount FROM transactions ORDER BY id DESC LIMIT 5;");
        $this->command->line("  SELECT correlation_id, action, integrity_hash FROM audit_logs ORDER BY id DESC LIMIT 3;");
        $this->command->newLine();
        $this->command->info('INVARIANTS À VÉRIFIER EN LIVE');
        $this->command->line('  php artisan tinker');
        $this->command->line('  >>> app(App\Services\AuditLogIntegrityService::class)->verifyChainFrom(0)');
        $this->command->info('═══════════════════════════════════════════════════');
    }
}
