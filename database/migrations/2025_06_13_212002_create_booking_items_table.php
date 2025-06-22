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

            $table->foreignId('booking_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('luggage_id')
                ->constrained('luggages')
                ->cascadeOnDelete();

            $table->foreignId('trip_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->float('kg_reserved')->nullable(); // ✅ validation métier côté FormRequest
            $table->decimal('price', 8, 2)->nullable();

            $table->timestamps();

            // ✅ Unicité logique (une valise unique par réservation)
            $table->unique(['booking_id', 'luggage_id']);
        });

        // 🧠 (Optionnel) Ajout manuel de contraintes SQL si tu veux quand même les check()
        // DB::statement('ALTER TABLE booking_items ADD CONSTRAINT chk_kg_reserved CHECK (kg_reserved >= 0)');
        // DB::statement('ALTER TABLE booking_items ADD CONSTRAINT chk_price CHECK (price >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};
