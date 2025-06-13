<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();

            //  Clé étrangère vers bookings
            $table->foreignId('booking_id')
                ->constrained('bookings') // 🔧 explicite
                ->onDelete('cascade');

            // Clé étrangère vers luggages
            $table->foreignId('luggage_id')
                ->constrained('luggages') // ✅ corrigé (évite l'erreur SQL)
                ->onDelete('cascade');

            // Clé étrangère vers trips
            $table->foreignId('trip_id')
                ->constrained('trips') // ✅ idem
                ->onDelete('cascade');

            //  Données métier
            $table->float('kg_reserved')->nullable();         // 🧳 Quantité réservée
            $table->decimal('price', 8, 2)->nullable();        // 💶 Prix associé

            $table->timestamps();

            // ✅ Unicité pour éviter les doublons valise + réservation
            $table->unique(['booking_id', 'luggage_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};
