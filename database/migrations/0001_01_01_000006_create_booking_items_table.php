<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();

            // 🔗 Référence à la réservation
            $table->foreignId('booking_id')
                ->constrained()
                ->cascadeOnDelete();

            // 🔗 Valise réservée dans cette réservation
            $table->foreignId('luggage_id')
                ->constrained('luggages')
                ->cascadeOnDelete();

            // 🔗 Trajet concerné par cette valise (peut différer du trip du booking principal dans certains cas)
            $table->foreignId('trip_id')
                ->constrained()
                ->cascadeOnDelete();

            // ⚖️ Quantité réservée (en kg) + prix calculé (à la réservation)
            $table->float('kg_reserved')
                ->nullable()
                ->comment('Poids réservé en kg pour cette valise dans le trajet');

            $table->decimal('price', 8, 2)
                ->nullable()
                ->comment('Montant total en € pour cet envoi');

            $table->timestamps();

            // 🔐 Assure qu’une même valise ne peut être associée deux fois à une même réservation
            $table->unique(['booking_id', 'luggage_id'], 'booking_luggage_unique');
        });

        // 🧪 (Optionnel) Contraintes SQL directes pour validation côté base
        // DB::statement('ALTER TABLE booking_items ADD CONSTRAINT chk_kg_reserved CHECK (kg_reserved >= 0)');
        // DB::statement('ALTER TABLE booking_items ADD CONSTRAINT chk_price CHECK (price >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};
