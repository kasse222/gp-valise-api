<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('trip_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('status')
                ->default(BookingStatusEnum::EN_PAIEMENT->value)
                ->comment('Statut de réservation casté via BookingStatusEnum — Instant Booking : défaut EN_PAIEMENT');

            // Destinataire (obligatoire à la réservation — Instant Booking)
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->string('recipient_email')->nullable();

            // Timestamps financiers
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('payment_expires_at')->nullable();

            // Remise physique / livraison
            $table->timestamp('handed_over_at')->nullable();
            $table->string('delivery_code', 6)->nullable();
            $table->string('delivery_qr_token')->nullable()->unique();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('escrow_releasable_at')->nullable();

            // Annulation
            $table->string('cancel_reason')->nullable();
            $table->unsignedTinyInteger('refund_rate')->nullable()
                ->comment('Taux remboursement appliqué : 100 | 70 | 0');

            // Litige
            $table->timestamp('disputed_at')->nullable();

            $table->text('comment')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Index
            $table->index(['status', 'payment_expires_at'], 'bookings_status_payment_expires_at_index');
            $table->index(['user_id', 'trip_id', 'status'], 'bookings_user_trip_status_index');
            $table->index(['status', 'escrow_releasable_at'], 'bookings_status_escrow_index');
            $table->index(['status', 'handed_over_at'], 'bookings_status_handed_over_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
